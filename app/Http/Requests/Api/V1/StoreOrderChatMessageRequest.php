<?php

namespace App\Http\Requests\Api\V1;

use App\Models\OrderConversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderChatMessageRequest extends FormRequest
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
            'conversation_type' => [
                'required',
                Rule::in(OrderConversation::conversationTypeValues()),
            ],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
