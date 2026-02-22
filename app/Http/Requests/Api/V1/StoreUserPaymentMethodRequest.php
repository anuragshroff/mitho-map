<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserPaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:50'],
            'last_four' => ['required', 'digits:4'],
            'exp_month' => ['nullable', 'integer', 'between:1,12'],
            'exp_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'token_reference' => ['nullable', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
