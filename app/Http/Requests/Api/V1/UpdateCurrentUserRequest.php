<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrentUserRequest extends FormRequest
{
    use ProfileValidationRules;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->profileRules($this->user()?->id);

        $rules['phone'] = [
            'nullable',
            'string',
            'min:8',
            'max:32',
            Rule::unique(User::class)->ignore($this->user()?->id),
        ];
        $rules['verification_code'] = ['nullable', 'digits:6', 'required_with:phone'];

        return $rules;
    }
}
