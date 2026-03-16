<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScheduleEventRequest;
use App\Models\Patient;
use App\Models\ScheduleEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
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

    /**
     * Display the schedule calendar for a patient.
     */
    public function index(Request $request, Patient $patient): Response
    {
        $this->authorize('view', $patient);

        $patient->load([
            'scheduleEvents' => fn ($q) => $q->where('is_active', true),
            'medications' => fn ($q) => $q->where('is_optional', false),
            'appointments',
        ]);

        $medicationEvents = [];
        foreach ($patient->medications as $medication) {
            $times = self::FREQUENCY_TIME_MAP[$medication->frequency] ?? null;

            if (! $times) {
                // Custom time frequency (HH:MM format)
                $times = [$medication->frequency];
            }

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
            'title' => $apt->title,
            'date' => $apt->appointment_date->format('Y-m-d'),
            'time' => $apt->appointment_time,
            'type' => 'appointment',
            'status' => $apt->status,
            'location' => $apt->location,
            'notes' => $apt->notes,
        ]);

        return Inertia::render('patients/Schedule', [
            'patient' => $patient->only('id', 'name'),
            'scheduleEvents' => $patient->scheduleEvents,
            'medicationEvents' => $medicationEvents,
            'appointments' => $appointments,
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    /**
     * Store a new schedule event.
     */
    public function store(StoreScheduleEventRequest $request, Patient $patient): RedirectResponse
    {
        $patient->scheduleEvents()->create($request->validated());

        return back()->with('success', 'Schedule event created.');
    }

    /**
     * Update a schedule event.
     */
    public function update(StoreScheduleEventRequest $request, ScheduleEvent $scheduleEvent): RedirectResponse
    {
        $scheduleEvent->update($request->validated());

        return back()->with('success', 'Schedule event updated.');
    }

    /**
     * Delete a schedule event.
     */
    public function destroy(ScheduleEvent $scheduleEvent): RedirectResponse
    {
        $this->authorize('update', $scheduleEvent->patient);

        $scheduleEvent->delete();

        return back()->with('success', 'Schedule event deleted.');
    }
}
