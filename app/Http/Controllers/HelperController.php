<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetHelperPasswordRequest;
use App\Http\Requests\StoreHelperRequest;
use App\Http\Requests\UpdateHelperBankDetailsRequest;
use App\Http\Requests\UpdateHelperRequest;
use App\Models\Helper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HelperController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Helper::class);

        $helpers = Helper::query()
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('fin', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('helpers/Index', [
            'helpers' => $helpers,
            'filters' => request()->only('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Helper::class);

        return Inertia::render('helpers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHelperRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $customPassword = $validated['password'] ?? null;
        $password = $customPassword ?: Str::random(12);
        unset($validated['password']);

        DB::transaction(function () use ($validated, $password) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['fin'],
                'role' => 'helper',
                'password' => Hash::make($password),
            ]);

            $user->helper()->create($validated);
        });

        $message = $customPassword
            ? 'Helper created. Password has been set as specified.'
            : "Helper created. Temporary password: {$password}";

        return to_route('helpers.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Helper $helper): Response
    {
        $this->authorize('view', $helper);

        $helper->load('documents');

        return Inertia::render('helpers/Show', [
            'helper' => $helper,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Helper $helper): Response
    {
        $this->authorize('update', $helper);

        return Inertia::render('helpers/Edit', [
            'helper' => $helper,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHelperRequest $request, Helper $helper): RedirectResponse
    {
        $validated = $request->validated();

        $helper->update($validated);

        if ($helper->wasChanged('fin')) {
            $helper->user->update(['username' => $validated['fin']]);
        }

        if ($helper->wasChanged('name')) {
            $helper->user->update(['name' => $validated['name']]);
        }

        return to_route('helpers.show', $helper)->with('success', 'Helper updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Helper $helper): RedirectResponse
    {
        $this->authorize('delete', $helper);

        $helper->user->delete();

        return to_route('helpers.index')->with('success', 'Helper deleted.');
    }

    /**
     * Update the authenticated helper's bank details.
     */
    public function updateBankDetails(UpdateHelperBankDetailsRequest $request): RedirectResponse
    {
        $helper = $request->user()->helper;
        $validated = $request->validated();

        if ($validated['payment_method'] === 'bank_transfer') {
            $helper->update([
                'bank_name' => $validated['bank_name'],
                'bank_account_no' => $validated['bank_account_no'],
                'paynow_enabled' => false,
            ]);
        } else {
            $helper->update([
                'paynow_enabled' => true,
                'paynow_identifier' => $validated['paynow_identifier'],
            ]);
        }

        return back()->with('success', 'Bank details updated.');
    }

    /**
     * Reset the helper's password.
     */
    public function resetPassword(ResetHelperPasswordRequest $request, Helper $helper): RedirectResponse
    {
        $helper->user->update(['password' => Hash::make($request->validated('password'))]);

        return back()->with('success', 'Password has been reset successfully.');
    }
}
