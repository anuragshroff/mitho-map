<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferenceRequest extends FormRequest
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
            'dietary_preferences' => ['nullable', 'array'],
            'dietary_preferences.*' => ['string'],
            'spice_level' => ['nullable', 'string', 'in:mild,medium,hot,extra_hot'],
            'favorite_cuisines' => ['nullable', 'array'],
            'favorite_cuisines.*' => ['string'],
        ];
    }
}
