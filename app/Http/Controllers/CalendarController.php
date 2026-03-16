<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Medication frequency to time mapping.
     *
     * @var array<string, list<string>>
     */
    private const FREQUENCY_TIME_MAP = [
        'After Breakfast' => ['08:00'],
        'After Lunch' => ['12:00'],
        'After Dinner' => ['18:00'],
        '6pm' => ['18:00'],
        'Before Sleep' => ['21:00'],
        'Night' => ['21:00'],
        '2 Times a Day' => ['08:00', '18:00'],
        '3 Times a Day' => ['08:00', '12:00', '18:00'],
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $patients = $user->isAdmin()
            ? Patient::query()->orderBy('name')->get()
            : ($user->helper?->patients()->orderBy('name')->get() ?? collect());

        $patientSchedules = self::buildPatientSchedules($patients);

        return Inertia::render('calendars/Index', [
            'patientSchedules' => $patientSchedules,
        ]);
    }

    /**
     * Build schedule data for the given patients.
     *
     * @param  Collection<int, Patient>  $patients
     * @return list<array<string, mixed>>
     */
    public static function buildPatientSchedules($patients): array
    {
        $patients->load([
            'scheduleEvents' => fn ($q) => $q->where('is_active', true),
            'medications' => fn ($q) => $q->where('is_optional', false),
            'appointments',
        ]);

        return $patients->map(function (Patient $patient) {
            $medicationEvents = [];
            foreach ($patient->medications as $medication) {
                $times = self::FREQUENCY_TIME_MAP[$medication->frequency] ?? [$medication->frequency];

                foreach ($times as $time) {
                    $medicationEvents[] = [
                        'id' => "med-{$medication->id}-{$time}",
                        'title' => $medication->name.($medication->dosage ? " ({$medication->dosage})" : ''),
                        'time' => $time,
                        'type' => 'medication',
                        'is_optional' => false,
                        'recurrence_type' => 'daily',
                        'recurrence_days' => null,
                    ];
                }
            }

            $appointments = $patient->appointments->map(fn ($apt) => [
                'id' => "apt-{$apt->id}",
                'appointment_id' => $apt->id,
                'title' => $apt->title,
                'date' => $apt->appointment_date->format('Y-m-d'),
                'time' => $apt->appointment_time,
                'type' => 'appointment',
                'status' => $apt->status,
                'location' => $apt->location,
                'doctor' => $apt->doctor,
                'notes' => $apt->notes,
            ])->all();

            return [
                'patient' => ['id' => $patient->id, 'name' => $patient->name],
                'scheduleEvents' => $patient->scheduleEvents->toArray(),
                'medicationEvents' => $medicationEvents,
                'appointments' => $appointments,
            ];
        })->values()->all();
    }
}
