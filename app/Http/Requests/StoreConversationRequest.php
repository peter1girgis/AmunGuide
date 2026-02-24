<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated user can create conversation
        return auth('sanctum')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'context' => [
                'nullable',
                'string',
                'max:255',
                'in:image_generation,travel_plan,info_request,general,place_inquiry,tour_inquiry',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'context.string' => 'The conversation context must be a text',
            'context.max' => 'The conversation context cannot exceed 255 characters',
            'context.in' => 'Invalid conversation context type',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'context' => 'conversation context',
        ];
    }
}
