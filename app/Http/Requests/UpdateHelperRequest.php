<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelperRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'fin' => ['required', 'string', 'regex:/^[A-Z]\d{7}[A-Z]$/', Rule::unique('helpers', 'fin')->ignore($this->route('helper'))],
            'passport_no' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'date_of_application' => ['nullable', 'date'],
            'work_permit_no' => ['nullable', 'string', 'max:255'],
            'sb_transmission_ref_no' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'employment_agency' => ['nullable', 'string', 'max:255'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'monthly_levy_rate' => ['nullable', 'numeric', 'min:0'],
            'rest_days_per_month' => ['nullable', 'integer', 'min:0', 'max:31'],
            'round_up_rest_day_rate' => ['nullable', 'boolean'],
            'fees_payable_to_ea' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:255'],
            'paynow_enabled' => ['nullable', 'boolean'],
            'paynow_identifier' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fin.regex' => 'The FIN must be in the format: one letter, seven digits, one letter (e.g., G1234567X).',
        ];
    }
}
