<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateTourRequest - Update tour
 *
 * ✅ Most fields are optional
 * ✅ Same validation but lighter
 */
class UpdateTourRequest extends FormRequest
{
    /**
     * Check authorization
     */
    public function authorize(): bool
    {
        $tour = $this->route('tour');

        return auth('sanctum')->check() && (
            auth('sanctum')->id() === $tour->guide_id ||
            auth('sanctum')->user()->role === 'admin'
        );
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        $tour = $this->route('tour');

        return [
            'title' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tours', 'title')->ignore($tour->id),
            ],
            'price' => 'sometimes|numeric|min:0|max:999999.99',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'payment_method' => 'nullable|string|in:cash,card,both',
            'details' => 'nullable|string|max:2000',
            'plan_id' => 'sometimes|nullable|integer|exists:plans,id',
            'places' => 'nullable|array|max:20',
            'places.*' => 'integer|exists:places,id',
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'title.unique' => 'Tour title already exists',
            'title.string' => 'Tour title must be text',
            'price.numeric' => 'Price must be a number',
            'start_time.date_format' => 'Time format is invalid (HH:mm)',
            'plan_id.exists' => 'The selected plan does not exist in our records.',
            'plan_id.integer' => 'Plan ID must be a whole number.',
            'payment_method.in' => 'Payment method is invalid',
            'places.array' => 'Places must be a list',
        ];
    }

    /**
     * Translated attributes
     */
    public function attributes(): array
    {
        return [
            'title' => 'tour title',
            'price' => 'price',
            'start_date' => 'start date',
            'start_time' => 'start time',
            'payment_method' => 'payment method',
            'details' => 'details',
            'plan_id' => 'plan',
            'places' => 'locations',
        ];
    }
}
