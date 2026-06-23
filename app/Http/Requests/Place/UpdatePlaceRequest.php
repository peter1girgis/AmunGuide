<?php

namespace App\Http\Requests\Place;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->role === 'admin' || auth()->user()?->role === 'guide';
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255|unique:places,title,' . $this->place->id,
            'description' => 'sometimes|string|min:10|max:5000',
            'ticket_price' => 'sometimes|numeric|min:0|max:10000',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            
            'rating' => 'nullable|numeric|min:0|max:5',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'This title already exists.',
            'description.min' => 'Description must be at least 10 characters.',
            'ticket_price.numeric' => 'Ticket price must be a number.',
            'image.image' => 'The image must be a valid image file.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image.max' => 'The image may not be greater than 2048 kilobytes.',
        ];
    }
}
