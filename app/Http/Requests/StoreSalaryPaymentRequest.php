<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'helper_id' => ['required', 'exists:helpers,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12',
                Rule::unique('salary_payments')->where(fn ($q) => $q->where('helper_id', $this->helper_id)->where('year', $this->year)),
            ],
            'year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'working_days_start' => ['nullable', 'date'],
            'working_days_end' => ['nullable', 'date', 'after_or_equal:working_days_start'],
            'total_calendar_days' => ['required', 'integer', 'min:0'],
            'sundays_in_period' => ['required', 'integer', 'min:0'],
            'pro_rated_amount' => ['required', 'numeric', 'min:0'],
            'extra_rest_days_worked' => ['required', 'integer', 'min:0', 'lt:sundays_in_period'],
            'sundays_worked_dates' => ['nullable', 'array'],
            'sundays_worked_dates.*' => ['date'],
            'rest_day_rate' => ['required', 'numeric', 'min:0'],
            'extra_rest_day_pay' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'ad_hoc_payments' => ['nullable', 'array', 'max:20'],
            'ad_hoc_payments.*.description' => ['required_with:ad_hoc_payments', 'string', 'max:255'],
            'ad_hoc_payments.*.amount' => ['required_with:ad_hoc_payments', 'numeric', 'min:0.01', 'max:99999.99'],
            'screenshot' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,heic,heif', 'max:20480'],
            'payment_method' => ['required', 'in:bank_transfer,paynow'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.unique' => 'A salary payment already exists for this helper in the selected month and year.',
        ];
    }
}
