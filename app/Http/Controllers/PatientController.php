<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Helper;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Patient::class);

        $user = request()->user();

        $query = Patient::query()->with('helpers:id,name');

        if ($user->isHelper()) {
            $query->whereHas('helpers', fn ($q) => $q->where('helper_id', $user->helper->id));
        }

        return Inertia::render('patients/Index', [
            'patients' => $query->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new patient.
     */
    public function create(): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('patients/Create', [
            'helpers' => Helper::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created patient.
     */
    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->safe()->except('helper_ids'));
        $patient->helpers()->sync($request->validated('helper_ids'));

        return redirect()->route('patients.show', $patient)->with('success', 'Patient created.');
    }

    /**
     * Display the specified patient.
     */
    public function show(Patient $patient): Response
    {
        $this->authorize('view', $patient);

        $patient->load(['medications', 'helpers:id,name']);

        $user = request()->user();

        if ($user->isAdmin()) {
            $patient->makeVisible('nric');
        }

        $presetMinutes = [
            '3 Times a Day (If Needed)' => 419,
            'After Breakfast' => 420,
            'After Lunch' => 720,
            'After Dinner' => 1080,
            'Before Sleep' => 1260,
        ];

        $patient->setRelation(
            'medications',
            $patient->medications->sortBy(function ($med) use ($presetMinutes) {
                $freq = $med->frequency;

                if (isset($presetMinutes[$freq])) {
                    return $presetMinutes[$freq];
                }

                if (preg_match('/^(\d{2}):(\d{2})$/', $freq, $m)) {
                    return (int) $m[1] * 60 + (int) $m[2];
                }

                return 9999;
            })->values()
        );

        return Inertia::render('patients/Show', [
            'patient' => $patient,
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /**
     * Show the form for editing the specified patient.
     */
    public function edit(Patient $patient): Response
    {
        $this->authorize('update', $patient);

        $patient->makeVisible('nric');
        $patient->load('helpers:id');

        return Inertia::render('patients/Edit', [
            'patient' => $patient,
            'helpers' => Helper::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified patient.
     */
    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->safe()->except('helper_ids'));
        $patient->helpers()->sync($request->validated('helper_ids'));

        return redirect()->route('patients.show', $patient)->with('success', 'Patient updated.');
    }

    /**
     * Remove the specified patient.
     */
    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return redirect()->route('patients.index')->with('success', 'Patient deleted.');
    }
}
