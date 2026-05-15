<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is already enforced by the 'auth:sanctum' and
     * 'admin' middleware on the route, so we simply return true here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Retrieve the {user} route model binding's ID for the unique email rule
        $userId = $this->route('user') ?: $this->route('id');

        return [
            'name'       => ['sometimes', 'required', 'string', 'max:255'],

            'email'      => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                // Ignore the current user's own email when checking uniqueness
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'address'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'national_id'=> ['sometimes', 'nullable', 'string', 'max:50'],

            'profile_image' => ['sometimes', 'nullable', 'string', 'max:255'],

            'role'       => ['sometimes', 'required', Rule::in(['admin', 'tourist', 'guide'])],

            // Password is optional. When provided it must meet the minimum length.
            // When omitted (or sent as null / empty string) it is ignored entirely.
            'password'   => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'email.unique'    => 'This email address is already taken by another user.',
            'role.in'         => 'Role must be one of: admin, tourist, guide.',
            'password.min'    => 'Password must be at least 8 characters long.',
        ];
    }
}
