<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyInfoController extends Controller
{
    /**
     * Show the family information page.
     */
    public function show(): Response
    {
        return Inertia::render('FamilyInfo', [
            'family_information' => Setting::getValue('family_information'),
        ]);
    }

    /**
     * Update the family information.
     */
    public function update(Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'family_information' => ['nullable', 'string', 'max:65535'],
        ]);

        $sanitized = strip_tags($validated['family_information'] ?? '', '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><blockquote><u><s><sub><sup>');

        Setting::setValue('family_information', $sanitized);

        return back()->with('success', 'Family information updated.');
    }
}
