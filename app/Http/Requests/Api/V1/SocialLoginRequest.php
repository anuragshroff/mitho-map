<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
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
            'provider' => ['required', 'string', 'in:google,facebook,apple'],
            'provider_token' => ['required', 'string', 'min:10'],
            'device_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'min:8', 'max:32'],
            'verification_code' => ['required_with:phone', 'digits:6'],
            'name' => ['nullable', 'string', 'max:255'],

            'address' => ['nullable', 'array'],
            'address.label' => ['nullable', 'string', 'max:50'],
            'address.line_1' => ['nullable', 'string', 'max:255'],
            'address.line_2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:120'],
            'address.state' => ['nullable', 'string', 'max:120'],
            'address.postal_code' => ['nullable', 'string', 'max:32'],
            'address.country' => ['nullable', 'string', 'size:2'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
