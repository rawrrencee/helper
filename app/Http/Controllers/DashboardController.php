<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Helper;
use App\Models\SalaryPayment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->helperDashboard($user);
    }

    private function adminDashboard(): Response
    {
        $now = now();

        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'role' => 'admin',
                'totalHelpers' => Helper::count(),
                'recentPayments' => SalaryPayment::query()
                    ->with('helper:id,name')
                    ->latest()
                    ->limit(5)
                    ->get(),
                'unpaidCurrentMonth' => SalaryPayment::query()
                    ->where('month', $now->month)
                    ->where('year', $now->year)
                    ->whereNull('paid_at')
                    ->count(),
                'helpersWithoutPayment' => Helper::query()
                    ->whereDoesntHave('salaryPayments', function ($q) use ($now) {
                        $q->where('month', $now->month)->where('year', $now->year);
                    })
                    ->count(),
                'upcomingAppointments' => Appointment::query()->upcoming()->limit(5)->get(),
                'familyInformation' => Setting::getValue('family_information'),
            ],
        ]);
    }

    private function helperDashboard($user): Response
    {
        $helper = $user->helper;

        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'role' => 'helper',
                'helper' => $helper,
                'recentPayments' => $helper ? SalaryPayment::query()
                    ->where('helper_id', $helper->id)
                    ->latest('year')
                    ->latest('month')
                    ->limit(3)
                    ->get() : [],
                'documentsCount' => $helper?->documents()->count() ?? 0,
                'upcomingAppointments' => Appointment::query()->upcoming()->limit(5)->get(),
                'familyInformation' => Setting::getValue('family_information'),
                'patientMedications' => $helper ? $this->getPatientMedications($helper, optional: false) : [],
                'patientOptionalMedications' => $helper ? $this->getPatientMedications($helper, optional: true) : [],
                'patientSchedules' => $helper ? CalendarController::buildPatientSchedules(
                    $helper->patients()->orderBy('name')->get()
                ) : [],
            ],
        ]);
    }

    /**
     * @return list<array{patient_name: string, patient_id: int, medications: list<array<string, mixed>>}>
     */
    private function getPatientMedications(Helper $helper, bool $optional): array
    {
        $presetMinutes = [
            '2 Times a Day' => 419,
            '3 Times a Day' => 419.5,
            'After Breakfast' => 420.5,  // 07:00
            'After Lunch' => 720.5,      // 12:00
            'After Dinner' => 1080.5,    // 18:00
            'Before Sleep' => 1260.5,    // 21:00
        ];

        return $helper->patients()
            ->with(['medications' => fn ($q) => $q->where('is_optional', $optional)])
            ->orderBy('name')
            ->get(['patients.id', 'patients.name'])
            ->map(function ($patient) use ($presetMinutes) {
                $medications = $patient->medications
                    ->sortBy(function ($med) use ($presetMinutes) {
                        $freq = $med->frequency;

                        if (isset($presetMinutes[$freq])) {
                            return $presetMinutes[$freq];
                        }

                        if (preg_match('/^(\d{2}):(\d{2})$/', $freq, $m)) {
                            return (int) $m[1] * 60 + (int) $m[2];
                        }

                        return 9999;
                    })
                    ->values()
                    ->map(fn ($med) => [
                        'id' => $med->id,
                        'name' => $med->name,
                        'dosage' => $med->dosage,
                        'frequency' => $med->frequency,
                        'notes' => $med->notes,
                    ]);

                return [
                    'patient_name' => $patient->name,
                    'patient_id' => $patient->id,
                    'medications' => $medications->all(),
                ];
            })
            ->filter(fn ($p) => count($p['medications']) > 0)
            ->values()
            ->all();
    }
}
