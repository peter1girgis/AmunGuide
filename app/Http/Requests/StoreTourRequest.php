<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreTourRequest - Create new tour
 *
 * ✅ Professional validation
 * ✅ Error messages in English
 * ✅ Authorization checks
 */
class StoreTourRequest extends FormRequest
{
    /**
     * Check authorization
     */
    public function authorize(): bool
    {
        // Only guides can create tours
        return auth('sanctum')->check() && auth('sanctum')->user()->role === 'guide';
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:tours,title',
            'price' => 'required|numeric|min:0|max:999999.99',
            'start_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'payment_method' => 'nullable|string|in:cash,card,both',
            'details' => 'nullable|string|max:2000',
            'plan_id' => 'nullable|integer|exists:plans,id',
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
            'title.required' => 'Tour title is required',
            'title.unique' => 'Tour title already exists',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price must be positive',
            'start_date.required' => 'Start date is required',
            'start_date.after' => 'Start date must be in the future',
            'start_time.required' => 'Start time is required',
            'plan_id.integer' => 'Plan ID must be a whole number',
            'plan_id.exists' => 'The selected plan does not exist',
            'start_time.date_format' => 'Time format is invalid (HH:mm)',
            'payment_method.in' => 'Payment method is invalid',
            'places.array' => 'Places must be a list',
            'places.*.exists' => 'The location does not exist',
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
            'plan_id' => 'selected plan',
            'places' => 'locations',
        ];
    }
}
