<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RagDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated users via Sanctum can access RAG data.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }

    /**
     * No input needed — this endpoint only returns project data snapshot.
     */
    public function rules(): array
    {
        return [
            'filter'   => ['nullable', 'array'],
            'filter.*' => ['string', \Illuminate\Validation\Rule::in(['places', 'tours', 'plans', 'user'])],
        ];
    }
}
