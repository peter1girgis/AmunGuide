<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->check();
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'payable_type' => [
                'required',
                'string',
                Rule::in(['tour_bookings', 'plans']),
            ],
            'payable_id' => [
                'required',
                'integer',
                'min:1',
                ],
            'receipt_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'transaction_id' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
                ];
    }
    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Amount Messages
            'amount.required' => 'The payment amount is required',
            'amount.numeric' => 'The payment amount must be a number',
            'amount.min' => 'The payment amount must be at least 0.01',
            'amount.max' => 'The payment amount must not exceed 999,999.99',
            // Payable Type Messages
            'payable_type.required' => 'The resource type is required',
            'payable_type.in' => 'The resource type must be tour_bookings or plans',
            // Payable ID Messages
            'payable_id.required' => 'The resource ID is required',
            'payable_id.integer' => 'The resource ID must be a number',
            'payable_id.min' => 'The resource ID must be at least 1',
            'receipt_image.image' => 'The receipt must be an image file.',
            'receipt_image.mimes' => 'The receipt must be a file of type: jpg, jpeg, png.',
            'receipt_image.max'   => 'The receipt image may not be greater than 2MB.',
            'transaction_id.max' => 'The transaction ID must not exceed 100 characters',
            'payment_method.max' => 'The payment method must not exceed 50 characters',
            'notes.max' => 'The notes must not exceed 500 characters',
        ];
    }
    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->has('payable_type') &&
                !$validator->errors()->has('payable_id')) {
                $payableType = $this->input('payable_type');
                $payableId = $this->input('payable_id');
                $modelMap = [
                    'tour_bookings' => 'App\\Models\\Tour_bookings',
                    'plans' => 'App\\Models\\Plans',
                ];
                $modelClass = $modelMap[$payableType] ?? null;
                if ($modelClass && !$modelClass::find($payableId)) {
                    $validator->errors()->add(
                        'payable_id',
                        "The selected resource ($payableType) does not exist."
                    );
                }
            }
            if (!$validator->errors()->any()) {
                $payableType = $this->input('payable_type');
                $payableId = $this->input('payable_id');
                $userId = auth('sanctum')->id();

                $modelMap = [
                    'tour_bookings' => 'App\\Models\\Tour_bookings',
                    'plans' => 'App\\Models\\Plans',
                ];
                $hasPending = \App\Models\Payments::hasPendingPayment(
                    $modelMap[$payableType],
                    $payableId,
                    $userId
                );
                if ($hasPending) {
                    $validator->errors()->add(
                        'payable_id',
                        'You already have a pending payment for this resource.'
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
            'amount' => 'payment amount',
            'payable_type' => 'resource type',
            'payable_id' => 'resource ID',
            'receipt_image'  => 'payment receipt',
            'transaction_id' => 'transaction number',
        ];
    }
}
