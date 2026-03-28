<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Claim;
use App\Models\Helper;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (config('demo.enabled')) {
            $this->seedDemoData();

            return;
        }

        User::factory()->admin()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Sample Helper',
            'username' => 'G1234567X',
            'email' => null,
        ]);
    }

    /**
     * Seed realistic demo data for public demonstrations.
     */
    private function seedDemoData(): void
    {
        // Admin user
        User::factory()->admin()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        // Helper user + profile
        $helperUser = User::factory()->create([
            'name' => 'Maria Santos',
            'username' => 'helper',
            'email' => null,
        ]);

        $helper = Helper::factory()->create([
            'user_id' => $helperUser->id,
            'name' => 'Maria Santos',
            'fin' => 'G1234567X',
            'passport_no' => 'EC1234567',
            'date_of_birth' => '1990-05-15',
            'nationality' => 'Filipino',
            'occupation' => 'Domestic Worker',
            'monthly_salary' => 600,
            'monthly_levy_rate' => 300,
            'rest_days_per_month' => 4,
            'round_up_rest_day_rate' => false,
            'bank_name' => 'DBS',
            'bank_account_no' => '012-3-45678',
            'paynow_enabled' => true,
        ]);

        // Patients
        $mrTan = Patient::factory()->create([
            'name' => 'Mr. Tan Ah Kow',
            'nric' => 'S1234567A',
            'phone' => '9123 4567',
            'address' => 'Blk 123 Ang Mo Kio Ave 6, #08-456, S(560123)',
            'date_of_birth' => '1948-03-12',
        ]);

        $mrsLee = Patient::factory()->create([
            'name' => 'Mrs. Lee Mei Ling',
            'nric' => 'S2345678B',
            'phone' => '8234 5678',
            'address' => 'Blk 123 Ang Mo Kio Ave 6, #08-456, S(560123)',
            'date_of_birth' => '1944-09-28',
        ]);

        $helper->patients()->attach([$mrTan->id, $mrsLee->id]);

        // Medications for Mr. Tan (elderly diabetic/hypertensive)
        $this->createMedications($mrTan, [
            ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => 'After Breakfast'],
            ['name' => 'Amlodipine', 'dosage' => '5mg', 'frequency' => 'After Breakfast'],
            ['name' => 'Atorvastatin', 'dosage' => '20mg', 'frequency' => 'Before Sleep'],
            ['name' => 'Aspirin', 'dosage' => '100mg', 'frequency' => 'After Lunch'],
        ]);

        // Medications for Mrs. Lee
        $this->createMedications($mrsLee, [
            ['name' => 'Omeprazole', 'dosage' => '20mg', 'frequency' => 'After Breakfast'],
            ['name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'After Dinner', 'is_optional' => true, 'notes' => 'Only when pain occurs'],
        ]);

        // Appointments — mix of completed, scheduled, and future
        Appointment::factory()->completed()->create([
            'patient_id' => $mrTan->id,
            'title' => 'Blood test & review',
            'doctor' => 'Dr. Wong',
            'location' => 'Tan Tock Seng Hospital',
            'appointment_date' => now()->subDays(14)->format('Y-m-d'),
            'appointment_time' => '09:30',
            'notes' => 'HbA1c results good. Continue current medication.',
        ]);

        Appointment::factory()->completed()->create([
            'patient_id' => $mrsLee->id,
            'title' => 'Gastroscopy follow-up',
            'doctor' => 'Dr. Lim',
            'location' => 'Singapore General Hospital',
            'appointment_date' => now()->subDays(7)->format('Y-m-d'),
            'appointment_time' => '14:00',
        ]);

        Appointment::factory()->create([
            'patient_id' => $mrTan->id,
            'title' => 'Diabetes review',
            'doctor' => 'Dr. Wong',
            'location' => 'Tan Tock Seng Hospital',
            'appointment_date' => now()->addDays(5)->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        Appointment::factory()->create([
            'patient_id' => $mrsLee->id,
            'title' => 'Eye check-up',
            'doctor' => 'Dr. Ng',
            'location' => 'SNEC (Singapore National Eye Centre)',
            'appointment_date' => now()->addDays(12)->format('Y-m-d'),
            'appointment_time' => '11:30',
        ]);

        Appointment::factory()->create([
            'patient_id' => $mrTan->id,
            'title' => 'Physiotherapy session',
            'location' => 'AMK Polyclinic',
            'appointment_date' => now()->addDays(20)->format('Y-m-d'),
            'appointment_time' => '15:00',
        ]);

        // Salary payments — past 6 months
        $this->createSalaryPayments($helper);

        // Claims — mix of statuses
        Claim::factory()->approved()->create([
            'helper_id' => $helper->id,
            'month' => now()->subMonth()->month,
            'year' => now()->subMonth()->year,
            'title' => 'Groceries',
            'amount' => 85.50,
            'notes' => 'Weekly groceries from NTUC FairPrice',
        ]);

        Claim::factory()->approved()->create([
            'helper_id' => $helper->id,
            'month' => now()->subMonth()->month,
            'year' => now()->subMonth()->year,
            'title' => 'Transport',
            'amount' => 24.00,
            'notes' => 'MRT fare to hospital appointments',
        ]);

        Claim::factory()->create([
            'helper_id' => $helper->id,
            'month' => now()->month,
            'year' => now()->year,
            'title' => 'Medical',
            'amount' => 35.00,
            'notes' => 'Clinic visit for flu',
            'status' => 'pending',
        ]);
    }

    /**
     * Create medications for a patient.
     *
     * @param  array<int, array<string, mixed>>  $medications
     */
    private function createMedications(Patient $patient, array $medications): void
    {
        foreach ($medications as $medication) {
            Medication::factory()->create([
                'patient_id' => $patient->id,
                ...$medication,
            ]);
        }
    }

    /**
     * Create salary payments for the past 6 months.
     */
    private function createSalaryPayments(Helper $helper): void
    {
        for ($i = 6; $i >= 1; $i--) {
            $date = now()->subMonths($i);
            $isPaid = $i > 1; // Most recent unpaid, rest paid

            $payment = SalaryPayment::factory()->create([
                'helper_id' => $helper->id,
                'month' => $date->month,
                'year' => $date->year,
                'base_salary' => 600,
                'total_calendar_days' => $date->daysInMonth,
                'sundays_in_period' => 4,
                'pro_rated_amount' => 600,
                'rest_day_rate' => round(600 / 26, 2),
                'extra_rest_days_worked' => 0,
                'extra_rest_day_pay' => 0,
                'ad_hoc_payments' => $i === 3 ? [
                    ['description' => 'Birthday bonus', 'amount' => 50.00],
                ] : null,
                'total_amount' => $i === 3 ? 650 : 600,
                'payment_method' => 'bank_transfer',
                'paid_at' => $isPaid ? $date->endOfMonth() : null,
            ]);
        }
    }
}
