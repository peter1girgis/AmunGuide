<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FilterTourRequest - Filter tours
 *
 * ✅ Query parameters validation
 */
class FilterTourRequest extends FormRequest
{
    /**
     * Everyone can filter
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'guide_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'sort' => 'nullable|string|in:price_asc,price_desc,newest,popular',
            'plan_id'    => 'nullable|integer|exists:plans,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'min_price.numeric' => 'Minimum price must be a number',
            'max_price.numeric' => 'Maximum price must be a number',
            'guide_id.exists' => 'Guide does not exist',
            'sort.in' => 'Sorting method is invalid',
            'per_page.integer' => 'Number of results must be a number',
            'plan_id.integer'    => 'Plan ID must be a number',
            'plan_id.exists'     => 'The requested plan does not exist',
        ];
    }
}
