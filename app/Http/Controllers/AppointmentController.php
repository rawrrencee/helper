<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentNotesRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    /**
     * Display the appointments page.
     */
    public function index(): Response
    {
        $user = request()->user();

        $upcomingQuery = Appointment::query()
            ->with('patient:id,name')
            ->where('status', '!=', 'cancelled')
            ->where('appointment_date', '>=', now()->toDateString());

        $completedQuery = Appointment::query()
            ->with('patient:id,name')
            ->where('status', 'completed');

        if ($user->isHelper() && $user->helper) {
            $assignedPatientIds = $user->helper->patients()->pluck('patients.id');

            $upcomingQuery->where(function ($q) use ($assignedPatientIds) {
                $q->whereIn('patient_id', $assignedPatientIds)
                    ->orWhereNull('patient_id');
            });

            $completedQuery->where(function ($q) use ($assignedPatientIds) {
                $q->whereIn('patient_id', $assignedPatientIds)
                    ->orWhereNull('patient_id');
            });
        }

        $patients = $user->isAdmin()
            ? Patient::query()->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('appointments/Index', [
            'upcomingAppointments' => $upcomingQuery
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get(),
            'completedAppointments' => $completedQuery
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get(),
            'patients' => $patients,
        ]);
    }

    /**
     * Store a newly created appointment.
     */
    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        Appointment::create($request->validated());

        return back()->with('success', 'Appointment created.');
    }

    /**
     * Update the specified appointment.
     */
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update($request->validated());

        return back()->with('success', 'Appointment updated.');
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment): RedirectResponse
    {
        if (! request()->user()->isAdmin()) {
            abort(403);
        }

        $appointment->delete();

        return back()->with('success', 'Appointment deleted.');
    }

    /**
     * Update the notes for the specified appointment.
     */
    public function updateNotes(UpdateAppointmentNotesRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update($request->validated());

        return back()->with('success', 'Notes saved.');
    }
}
