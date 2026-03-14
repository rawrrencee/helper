<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
            'nric' => ['required', 'string', 'regex:/^[STFGM]\d{7}[A-Z]$/i', 'unique:patients,nric'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'helper_ids' => ['required', 'array', 'min:1'],
            'helper_ids.*' => ['exists:helpers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nric.regex' => 'The NRIC format is invalid (e.g., S1234567A).',
        ];
    }
}
