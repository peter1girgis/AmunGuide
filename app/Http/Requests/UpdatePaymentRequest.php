<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $payment = $this->route('payment');
        $user = auth('sanctum')->user();

        if (!$user || !$payment) {
            return false;
        }

        // Admin يمكنه تحديث أي دفعة
        if ($user->role === 'admin') {
            return true;
        }

        // المستخدم العادي يمكنه تحديث دفعاته فقط إذا كانت pending
        return $payment->payer_id === $user->id  ;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = auth('sanctum')->user();

        // Admin يمكنه تحديث الحالة
        if ($user && $user->role === 'admin') {
            return [
                'status' => [
                    'sometimes',
                    'required',
                    Rule::in(['pending', 'approved', 'failed']),
                ],
                'amount' => [
                    'sometimes',
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:999999.99',
                ],

            ];
        }

        // المستخدم العادي يمكنه تحديث المبلغ فقط
        return [
            'receipt_image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
            'transaction_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'payment_method' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The payment status is required',
            'status.in' => 'The payment status must be pending, approved, or failed',
            'amount.required' => 'The payment amount is required',
            'amount.numeric' => 'The payment amount must be a number',
            'amount.min' => 'The payment amount must be at least 0.01',
            'amount.max' => 'The payment amount must not exceed 999,999.99',
            'receipt_image.image' => 'The receipt must be an image file.',
            'receipt_image.mimes' => 'The receipt must be a file of type: jpg, jpeg, png.',
            'receipt_image.max'   => 'The receipt image may not be greater than 2MB.',
            'transaction_id.max' => 'The transaction ID must not exceed 100 characters',
            'payment_method.max' => 'The payment method must not exceed 50 characters',
            'notes.max' => 'The notes must not exceed 1000 characters',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $payment = $this->route('payment');

            // لا يمكن تحديث دفعة معتمدة أو فاشلة (إلا من Admin)
            if (
                $payment &&
                $payment->status !== 'pending' &&
                auth('sanctum')->user()->role !== 'admin'
            ) {

                $validator->errors()->add(
                    'status',
                    'You cannot update a payment that is already ' . $payment->status . '.'
                );
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'status' => 'payment status',
            'amount' => 'payment amount',
            'receipt_image'  => 'payment receipt',
            'transaction_id' => 'transaction number',
            'payment_method' => 'payment method',
            'notes'          => 'notes',
        ];
    }
}
