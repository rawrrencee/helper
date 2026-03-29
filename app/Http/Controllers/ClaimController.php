<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClaimRequest;
use App\Http\Requests\UpdateClaimRequest;
use App\Models\Claim;
use App\Models\Helper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClaimController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Claim::class);

        $query = Claim::query()->with('helper');

        if ($request->user()->isHelper()) {
            $query->whereHas('helper', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $claims = $query
            ->when($request->input('helper_id'), fn ($q, $helperId) => $q->where('helper_id', $helperId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $helpers = $request->user()->isAdmin() ? Helper::query()->orderBy('name')->get(['id', 'name']) : collect();

        return Inertia::render('claims/Index', [
            'claims' => $claims,
            'helpers' => $helpers,
            'filters' => $request->only('helper_id'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Claim::class);

        $helpers = $request->user()->isAdmin()
            ? Helper::query()->orderBy('name')->get(['id', 'name'])
            : Helper::query()->where('user_id', $request->user()->id)->get(['id', 'name']);

        return Inertia::render('claims/Create', [
            'helpers' => $helpers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClaimRequest $request): RedirectResponse
    {
        $claim = Claim::create($request->safe()->except('screenshot'));

        if ($request->hasFile('screenshot')) {
            $path = $request->file('screenshot')->store("claim-screenshots/{$claim->helper_id}", 'local');
            $claim->update(['screenshot_path' => $path]);
        }

        return to_route('claims.index')->with('success', 'Claim submitted.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Claim $claim): Response
    {
        $this->authorize('view', $claim);

        $claim->load('helper', 'salaryPayments');

        return Inertia::render('claims/Show', [
            'claim' => $claim,
            'screenshotUrl' => $claim->screenshot_path
                ? route('claims.screenshot', $claim)
                : null,
            'paymentScreenshotUrl' => $claim->payment_screenshot_path
                ? route('claims.payment-screenshot', $claim)
                : null,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClaimRequest $request, Claim $claim): RedirectResponse
    {
        if (
            $request->has('status')
            && $request->input('status') !== 'approved'
            && $claim->status === 'approved'
            && $claim->payment_screenshot_path
        ) {
            Storage::disk('local')->delete($claim->payment_screenshot_path);
            $claim->payment_screenshot_path = null;
        }

        $claim->update($request->validated());

        return back()->with('success', 'Claim updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Claim $claim): RedirectResponse
    {
        $this->authorize('delete', $claim);

        if ($claim->screenshot_path) {
            Storage::disk('local')->delete($claim->screenshot_path);
        }

        if ($claim->payment_screenshot_path) {
            Storage::disk('local')->delete($claim->payment_screenshot_path);
        }

        $claim->delete();

        return to_route('claims.index')->with('success', 'Claim deleted.');
    }

    /**
     * Serve the claim screenshot file.
     */
    public function screenshot(Claim $claim): BinaryFileResponse
    {
        $this->authorize('view', $claim);

        abort_unless($claim->screenshot_path, 404);

        return response()->file(Storage::disk('local')->path($claim->screenshot_path));
    }

    /**
     * Upload a payment screenshot for an approved claim.
     */
    public function uploadPaymentScreenshot(Request $request, Claim $claim): RedirectResponse
    {
        $this->authorize('update', $claim);

        $request->validate([
            'screenshot' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,heic,heif', 'max:20480'],
        ]);

        if ($claim->payment_screenshot_path) {
            Storage::disk('local')->delete($claim->payment_screenshot_path);
        }

        $path = $request->file('screenshot')->store("claim-payment-screenshots/{$claim->helper_id}", 'local');
        $claim->update(['payment_screenshot_path' => $path]);

        return back()->with('success', 'Payment screenshot uploaded.');
    }

    /**
     * Serve the claim payment screenshot file.
     */
    public function paymentScreenshot(Claim $claim): BinaryFileResponse
    {
        $this->authorize('view', $claim);

        abort_unless($claim->payment_screenshot_path, 404);

        return response()->file(Storage::disk('local')->path($claim->payment_screenshot_path));
    }
}
