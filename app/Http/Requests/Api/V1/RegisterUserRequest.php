<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:8', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'verification_code' => ['required', 'digits:6'],
            'device_name' => ['required', 'string', 'max:255'],

            'address' => ['required', 'array'],
            'address.label' => ['nullable', 'string', 'max:50'],
            'address.line_1' => ['required', 'string', 'max:255'],
            'address.line_2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:120'],
            'address.state' => ['required', 'string', 'max:120'],
            'address.postal_code' => ['required', 'string', 'max:32'],
            'address.country' => ['required', 'string', 'size:2'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
