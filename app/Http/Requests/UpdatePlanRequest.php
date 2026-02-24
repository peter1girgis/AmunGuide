<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the plan owner may update it.
        $plan = $this->route('plan');


        return $plan && (int) $plan->user_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [
            // ── Plan ──────────────────────────────────────────────────────────
            'title'                   => ['sometimes', 'required', 'string', 'max:255'],

            // ── Plan Items ────────────────────────────────────────────────────
            // Sending `plan_items` on update will REPLACE the existing set.
            'plan_items'              => ['sometimes', 'array'],
            'plan_items.*.place_id'   => ['required_with:plan_items', 'integer', 'exists:places,id'],
            'plan_items.*.day_index'  => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                      => 'A plan title is required.',
            'plan_items.*.place_id.required_with' => 'Each plan item must reference a valid place.',
            'plan_items.*.place_id.exists'         => 'One or more selected places do not exist.',
            'plan_items.*.day_index.integer'       => 'Day index must be a whole number.',
            'plan_items.*.day_index.min'           => 'Day index must be at least 1.',
            'plan_items.*.day_index.max'           => 'Day index cannot exceed 365.',
        ];
    }
}
