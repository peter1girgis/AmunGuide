<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RagDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * Validation rules for the filter body.
     *
     * Accepted body shape:
     * {
     *   "type": ["places", "tours", "plans"],   // what sections to return
     *
     *   // ── Places filters ──────────────────
     *   "price":        { "min": 100, "max": 300 },
     *   "rating":       { "min": 4, "max": 5 },
     *   "has_image":    true,
     *   "has_comments": true,
     *
     *   // ── Tours filters ───────────────────
     *   "payment_method": ["credit_card", "cash", "both"],
     *   "date": { "from": "2026-05-01", "to": "2026-05-30" },
     *
     *   // ── Plans filters ───────────────────
     *   "days":         { "min": 1, "max": 5 },
     *   "places_count": { "min": 3, "max": 10 }
     * }
     */
    public function rules(): array
    {
        return [
            // ── Section selector ──────────────────────────────────────────
            'type'   => ['nullable', 'array'],
            'type.*' => ['string', Rule::in(['places', 'tours', 'plans'])],

            // ── Shared: price (used by places & tours) ────────────────────
            'price'     => ['nullable', 'array'],
            'price.min' => ['nullable', 'numeric', 'min:0'],
            'price.max' => ['nullable', 'numeric', 'min:0'],

            // ── Places filters ────────────────────────────────────────────
            'rating'       => ['nullable', 'array'],
            'rating.min'   => ['nullable', 'numeric', 'min:0', 'max:5'],
            'rating.max'   => ['nullable', 'numeric', 'min:0', 'max:5'],
            'has_image'    => ['nullable', 'boolean'],
            'has_comments' => ['nullable', 'boolean'],

            // ── Tours filters ─────────────────────────────────────────────
            'payment_method'   => ['nullable', 'array'],
            'payment_method.*' => ['string', Rule::in(['credit_card', 'cash', 'both'])],
            'date'             => ['nullable', 'array'],
            'date.from'        => ['nullable', 'date'],
            'date.to'          => ['nullable', 'date'],

            // ── Plans filters ─────────────────────────────────────────────
            'days'            => ['nullable', 'array'],
            'days.min'        => ['nullable', 'integer', 'min:0'],
            'days.max'        => ['nullable', 'integer', 'min:0'],
            'places_count'    => ['nullable', 'array'],
            'places_count.min'=> ['nullable', 'integer', 'min:0'],
            'places_count.max'=> ['nullable', 'integer', 'min:0'],
        ];
    }
}
