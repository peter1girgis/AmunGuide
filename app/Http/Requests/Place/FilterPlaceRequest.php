<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class FilterPlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:price,rating,newest,trending',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
