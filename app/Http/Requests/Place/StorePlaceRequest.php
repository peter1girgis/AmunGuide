<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:places,title',
            'description' => 'required|string|min:10|max:5000',

            'ticket_price' => 'required|numeric|min:0|max:10000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'rating' => 'nullable|numeric|min:0|max:5',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required.',
            'title.unique' => 'This title already exists.',
            'description.required' => 'Description is required.',
            'description.min' => 'Description must be at least 10 characters.',

            'ticket_price.required' => 'Ticket price is required.',
        ];
    }
}
