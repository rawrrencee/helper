<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;

class MedicationController extends Controller
{
    /**
     * Store a newly created medication.
     */
    public function store(StoreMedicationRequest $request, Patient $patient): RedirectResponse
    {
        $patient->medications()->create($request->validated());

        return back()->with('success', 'Medication added.');
    }

    /**
     * Update the specified medication.
     */
    public function update(UpdateMedicationRequest $request, Patient $patient, Medication $medication): RedirectResponse
    {
        $medication->update($request->validated());

        return back()->with('success', 'Medication updated.');
    }

    /**
     * Remove the specified medication.
     */
    public function destroy(Patient $patient, Medication $medication): RedirectResponse
    {
        if (! request()->user()->isAdmin()) {
            abort(403);
        }

        $medication->delete();

        return back()->with('success', 'Medication deleted.');
    }
}
