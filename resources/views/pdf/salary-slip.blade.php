<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; color: #666; }
        .section { margin-bottom: 15px; }
        .section h3 { font-size: 13px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 4px 8px; }
        table td:first-child { color: #666; width: 40%; }
        table td:last-child { font-weight: bold; }
        .total-row { border-top: 2px solid #333; }
        .total-row td { padding-top: 8px; font-size: 14px; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Salary Slip</h1>
        <p>{{ str_pad($payment->month, 2, '0', STR_PAD_LEFT) }}/{{ $payment->year }}</p>
    </div>

    @php
        $resolvedEmployer = $employer_name ?: ($helper->employer_name ?? null);
    @endphp
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding: 4px 8px;">
                <strong>Employer</strong><br>
                @if($resolvedEmployer)
                    {{ $resolvedEmployer }}<br>
                @endif
                @if($employer_address)
                    {{ $employer_address }}
                @endif
            </td>
            <td style="width: 50%; vertical-align: top; text-align: right; padding: 4px 8px;">
                <strong>Employee</strong><br>
                {{ $helper->name }}<br>
                {{ $helper->fin }}
            </td>
        </tr>
    </table>

    <div class="section">
        <h3>Payment Period</h3>
        <table>
            <tr><td>Month / Year</td><td>{{ str_pad($payment->month, 2, '0', STR_PAD_LEFT) }}/{{ $payment->year }}</td></tr>
            @if($payment->working_days_start && $payment->working_days_end)
            <tr><td>Working Period</td><td>{{ $payment->working_days_start->format('d/m/Y') }} - {{ $payment->working_days_end->format('d/m/Y') }}</td></tr>
            @endif
            <tr><td>Total Calendar Days</td><td>{{ $payment->total_calendar_days }}</td></tr>
            <tr><td>Sundays in Period</td><td>{{ $payment->sundays_in_period }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Salary Calculation</h3>
        <table>
            <tr><td>Base Monthly Salary</td><td>${{ number_format($payment->base_salary, 2) }}</td></tr>
            @if($payment->pro_rated_amount != $payment->base_salary)
            <tr><td>Daily Rate (Salary / 26)</td><td>${{ number_format($payment->rest_day_rate, 2) }}</td></tr>
            <tr><td>Pro-Rated Amount</td><td>${{ number_format($payment->pro_rated_amount, 2) }}</td></tr>
            @endif
            @if($payment->extra_rest_days_worked > 0)
            <tr><td>Sundays Worked</td><td>{{ $payment->extra_rest_days_worked }}</td></tr>
            @if($payment->sundays_worked_dates)
            <tr><td>Dates Worked</td><td>{{ implode(', ', $payment->sundays_worked_dates) }}</td></tr>
            @endif
            <tr><td>Sundays Worked Pay</td><td>${{ number_format($payment->extra_rest_day_pay, 2) }}</td></tr>
            @endif
            @if($payment->ad_hoc_payments)
            @foreach($payment->ad_hoc_payments as $adHoc)
            <tr><td>{{ $adHoc['description'] }}</td><td>${{ number_format($adHoc['amount'], 2) }}</td></tr>
            @endforeach
            @endif
            <tr class="total-row"><td>Total Amount</td><td>${{ number_format($payment->total_amount, 2) }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Payment Details</h3>
        <table>
            <tr><td>Payment Method</td><td>{{ $payment->payment_method === 'bank_transfer' ? 'Bank Transfer' : 'PayNow' }}</td></tr>
            @if($payment->paid_at)
            <tr><td>Paid On</td><td>{{ $payment->paid_at->format('d/m/Y') }}</td></tr>
            @endif
            @if($payment->notes)
            <tr><td>Notes</td><td>{{ $payment->notes }}</td></tr>
            @endif
        </table>
    </div>

    @if($payment->payment_screenshot_path)
    <div class="section" style="page-break-inside: avoid;">
        <h3>Payment Screenshot</h3>
        <img src="{{ Storage::disk('local')->path($payment->payment_screenshot_path) }}"
             style="max-width: 100%; max-height: 400px;" />
    </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
