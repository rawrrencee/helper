<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentNotesRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
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
        return Inertia::render('appointments/Index', [
            'upcomingAppointments' => Appointment::query()
                ->where('status', '!=', 'cancelled')
                ->where('appointment_date', '>=', now()->toDateString())
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get(),
            'completedAppointments' => Appointment::query()
                ->where('status', 'completed')
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get(),
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
