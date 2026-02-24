<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may create a plan.
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Plan ──────────────────────────────────────────────────────────
            'title'                   => ['required', 'string', 'max:255'],

            // ── Plan Items (optional at creation) ─────────────────────────────
            'plan_items'              => ['sometimes', 'array'],
            'plan_items.*.place_id'   => ['required_with:plan_items', 'integer', 'exists:places,id'],
            'plan_items.*.day_index'  => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                    => 'A plan title is required.',
            'plan_items.*.place_id.required_with' => 'Each plan item must reference a valid place.',
            'plan_items.*.place_id.exists'        => 'One or more selected places do not exist.',
            'plan_items.*.day_index.integer'      => 'Day index must be a whole number.',
            'plan_items.*.day_index.min'          => 'Day index must be at least 1.',
            'plan_items.*.day_index.max'          => 'Day index cannot exceed 365.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Automatically attaches the authenticated user's id.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['user_id' => $this->user()->id]);
    }
}
