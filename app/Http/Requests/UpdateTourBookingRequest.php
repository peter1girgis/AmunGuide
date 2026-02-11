<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        $user = auth('sanctum')->user();

        if (!$user || !$booking) {
            return false;
        }

        // Admin يمكنه تحديث أي حجز
        if ($user->role === 'admin') {
            return true;
        }

        // المرشد يمكنه تحديث حجوزات رحلاته فقط (تغيير الحالة)
        if ($user->role === 'guide' && $booking->tour->guide_id === $user->id) {
            return true;
        }

        // السائح يمكنه تحديث حجزه فقط إذا كان pending (تغيير عدد المشاركين)
        return $booking->tourist_id === $user->id && $booking->status === 'pending';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = auth('sanctum')->user();
        $booking = $this->route('booking');

        // Admin والمرشد يمكنهما تحديث الحالة
        if ($user && in_array($user->role, ['admin', 'guide'])) {
            return [
                'status' => [
                    'sometimes',
                    'required',
                    'in:pending,approved,rejected',
                ],
                'participants_count' => [
                    'sometimes',
                    'required',
                    'integer',
                    'min:1',
                    'max:50',
                ],
                'amount' => ['sometimes', 'numeric'],
            ];
        }

        // السائح يمكنه تحديث عدد المشاركين فقط
        return [
            'participants_count' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:50',
                ],
                'amount' => ['sometimes', 'numeric']
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The booking status is required',
            'status.in' => 'The booking status must be pending, approved, or rejected',
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
        if ($this->has('amount')) {
            $validator->errors()->add(
                'amount',
                'Amount cannot be updated directly. It will be calculated automatically based on participants count.'
            );
        }
        $validator->after(function ($validator) {
            $booking = $this->route('booking');
            $user = auth('sanctum')->user();



            // لا يمكن تحديث حجز مرفوض
            if ($booking && $booking->isRejected() && $user->role !== 'admin') {
                $validator->errors()->add(
                    'status',
                    'You cannot update a rejected booking.'
                );
            }

            // لا يمكن تحديث حجز معتمد له دفعة معتمدة
            if ($booking &&
                $booking->isApproved() &&
                $booking->hasApprovedPayment() &&
                $user->role !== 'admin') {

                $validator->errors()->add(
                    'status',
                    'You cannot update a booking with an approved payment.'
                );
            }

            // إذا تم تحديث عدد المشاركين، نحتاج إلى إعادة حساب المبلغ
            if ($this->has('participants_count') && $booking) {
                $newAmount = $booking->tour->price * $this->input('participants_count');
                $this->merge(['amount' => $newAmount]);
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $booking = $this->route('booking');
        // 1. لو المستخدم بعت amount يدوي، بنشيلها فوراً عشان نضمن إننا اللي هنحسبها
        if ($this->has('amount')) {
            $this->offsetUnset('amount');
        }

        // إذا تم تحديث عدد المشاركين، نحسب المبلغ الجديد
        if ($this->has('participants_count') && $booking) {
            $newAmount = $booking->tour->price * $this->input('participants_count');
            $this->merge(['amount' => $newAmount]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'status' => 'booking status',
            'participants_count' => 'number of participants',
        ];
    }
}
