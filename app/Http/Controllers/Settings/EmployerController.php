<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateEmployerRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployerController extends Controller
{
    /**
     * Show the employer settings form.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Employer', [
            'employer_name' => Setting::getValue('employer_name', ''),
            'employer_address' => Setting::getValue('employer_address', ''),
        ]);
    }

    /**
     * Update the employer settings.
     */
    public function update(UpdateEmployerRequest $request): RedirectResponse
    {
        Setting::setValue('employer_name', $request->validated('employer_name'));
        Setting::setValue('employer_address', $request->validated('employer_address'));

        return back()->with('success', 'Employer settings updated.');
    }
}
