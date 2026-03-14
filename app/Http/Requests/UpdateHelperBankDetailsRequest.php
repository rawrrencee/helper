<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHelperBankDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isHelper();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['bank_transfer', 'paynow'])],
            'bank_name' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:255'],
            'bank_account_no' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:255'],
            'paynow_identifier' => ['required_if:payment_method,paynow', 'nullable', 'string', 'max:255'],
        ];
    }
}
