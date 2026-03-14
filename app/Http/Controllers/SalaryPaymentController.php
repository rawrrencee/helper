<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryPaymentRequest;
use App\Http\Requests\UpdateSalaryPaymentRequest;
use App\Models\Helper;
use App\Models\SalaryPayment;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalaryPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SalaryPayment::class);

        $query = SalaryPayment::query()->with('helper');

        if ($request->user()->isHelper()) {
            $query->whereHas('helper', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        $payments = $query
            ->when($request->input('helper_id'), fn ($q, $helperId) => $q->where('helper_id', $helperId))
            ->latest('year')
            ->latest('month')
            ->paginate(10)
            ->withQueryString();

        $helpers = $request->user()->isAdmin() ? Helper::query()->orderBy('name')->get(['id', 'name']) : collect();

        $bankDetails = null;
        if ($request->user()->isHelper()) {
            $helper = $request->user()->helper;
            $bankDetails = [
                'bank_name' => $helper->bank_name,
                'bank_account_no' => $helper->bank_account_no,
                'paynow_enabled' => $helper->paynow_enabled,
                'paynow_identifier' => $helper->paynow_identifier,
            ];
        }

        return Inertia::render('salary-payments/Index', [
            'payments' => $payments,
            'helpers' => $helpers,
            'filters' => $request->only('helper_id'),
            'bankDetails' => $bankDetails,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', SalaryPayment::class);

        $helpers = Helper::query()->orderBy('name')->get(['id', 'name', 'monthly_salary', 'round_up_rest_day_rate']);

        return Inertia::render('salary-payments/Create', [
            'helpers' => $helpers,
            'existingPayments' => SalaryPayment::query()
                ->select('helper_id', 'month', 'year')
                ->get()
                ->groupBy('helper_id')
                ->map(fn ($payments) => $payments->map(fn ($p) => ['month' => $p->month, 'year' => $p->year])->values()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalaryPaymentRequest $request): RedirectResponse
    {
        $payment = SalaryPayment::create($request->safe()->except('screenshot'));

        if ($request->hasFile('screenshot')) {
            $path = $request->file('screenshot')->store("screenshots/{$payment->helper_id}", 'local');
            $payment->update(['payment_screenshot_path' => $path]);
        }

        return to_route('salary-payments.index')->with('success', 'Salary payment created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SalaryPayment $salaryPayment): Response
    {
        $this->authorize('view', $salaryPayment);

        $salaryPayment->load('helper');

        return Inertia::render('salary-payments/Show', [
            'payment' => $salaryPayment,
            'screenshotUrl' => $salaryPayment->payment_screenshot_path
                ? route('salary-payments.screenshot', $salaryPayment)
                : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalaryPayment $salaryPayment): Response
    {
        $this->authorize('update', $salaryPayment);

        $salaryPayment->load('helper');

        return Inertia::render('salary-payments/Edit', [
            'payment' => $salaryPayment,
            'screenshotUrl' => $salaryPayment->payment_screenshot_path
                ? route('salary-payments.screenshot', $salaryPayment)
                : null,
            'existingPayments' => SalaryPayment::query()
                ->where('helper_id', $salaryPayment->helper_id)
                ->where('id', '!=', $salaryPayment->id)
                ->select('month', 'year')->get()
                ->map(fn ($p) => ['month' => $p->month, 'year' => $p->year]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalaryPaymentRequest $request, SalaryPayment $salaryPayment): RedirectResponse
    {
        $salaryPayment->update($request->safe()->except('screenshot'));

        if ($request->hasFile('screenshot')) {
            if ($salaryPayment->payment_screenshot_path) {
                Storage::disk('local')->delete($salaryPayment->payment_screenshot_path);
            }
            $path = $request->file('screenshot')->store("screenshots/{$salaryPayment->helper_id}", 'local');
            $salaryPayment->update(['payment_screenshot_path' => $path]);
        }

        return to_route('salary-payments.show', $salaryPayment)->with('success', 'Salary payment updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorize('delete', $salaryPayment);

        $salaryPayment->delete();

        return to_route('salary-payments.index')->with('success', 'Salary payment deleted.');
    }

    /**
     * Upload payment screenshot.
     */
    public function uploadScreenshot(Request $request, SalaryPayment $salaryPayment): RedirectResponse
    {
        $this->authorize('update', $salaryPayment);

        $request->validate([
            'screenshot' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,heic,heif', 'max:20480'],
        ]);

        if ($salaryPayment->payment_screenshot_path) {
            Storage::disk('local')->delete($salaryPayment->payment_screenshot_path);
        }

        $path = $request->file('screenshot')->store("screenshots/{$salaryPayment->helper_id}", 'local');
        $salaryPayment->update(['payment_screenshot_path' => $path]);

        return back()->with('success', 'Screenshot uploaded.');
    }

    /**
     * Serve the payment screenshot file.
     */
    public function screenshot(SalaryPayment $salaryPayment): BinaryFileResponse
    {
        $this->authorize('view', $salaryPayment);

        abort_unless($salaryPayment->payment_screenshot_path, 404);

        return response()->file(Storage::disk('local')->path($salaryPayment->payment_screenshot_path));
    }

    /**
     * Generate PDF salary slip.
     */
    public function generatePdf(SalaryPayment $salaryPayment): \Illuminate\Http\Response
    {
        $this->authorize('view', $salaryPayment);

        $salaryPayment->load('helper');

        $pdf = Pdf::loadView('pdf.salary-slip', [
            'payment' => $salaryPayment,
            'helper' => $salaryPayment->helper,
            'employer_name' => Setting::getValue('employer_name'),
            'employer_address' => Setting::getValue('employer_address'),
        ]);

        return $pdf->download("salary-slip-{$salaryPayment->helper->name}-{$salaryPayment->month}-{$salaryPayment->year}.pdf");
    }
}
