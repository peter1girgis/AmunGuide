<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Tours;

class StoreTourBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated user can create a booking
        // and must be tourist or admin
        $user = auth('sanctum')->user();

        if (!$user) {
            return false;
        }

        return in_array($user->role, ['tourist', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tour_id' => [
                'required',
                'integer',
                'exists:tours,id',
            ],
            'participants_count' => [
                'required',
                'integer',
                'min:1',
                'max:50', // Maximum 50 participants
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Tour ID Messages
            'tour_id.required' => 'The tour selection is required',
            'tour_id.integer' => 'Invalid tour ID',
            'tour_id.exists' => 'The selected tour does not exist',

            // Participants Count Messages
            'participants_count.required' => 'The number of participants is required',
            'participants_count.integer' => 'The number of participants must be a number',
            'participants_count.min' => 'You must book for at least 1 participant',
            'participants_count.max' => 'You cannot book for more than 50 participants',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->has('tour_id')) {
                $tourId = $this->input('tour_id');
                $tour = Tours::find($tourId);

                // Check that tour exists and is available
                if ($tour) {
                    // Check that tour date has not passed
                    $tourDate = \Carbon\Carbon::parse($tour->start_date);
                    if ($tourDate->isPast()) {
                        $validator->errors()->add(
                            'tour_id',
                            'This tour has already passed. You cannot book it.'
                        );
                    }

                    // Check that user is not the guide themselves
                    if ($tour->guide_id === auth('sanctum')->id()) {
                        $validator->errors()->add(
                            'tour_id',
                            'You cannot book your own tour.'
                        );
                    }
                }
            }

            // Check that there is no previous booking for the same tour
            if (!$validator->errors()->any()) {
                $existingBooking = \App\Models\Tour_bookings::where('tour_id', $this->input('tour_id'))
                    ->where('tourist_id', auth('sanctum')->id())
                    ->whereIn('status', ['pending', 'approved'])
                    ->first();

                if ($existingBooking) {
                    $validator->errors()->add(
                        'tour_id',
                        'You already have a booking for this tour.'
                    );
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tour_id' => 'tour',
            'participants_count' => 'number of participants',
        ];
    }
}
