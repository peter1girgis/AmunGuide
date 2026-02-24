<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Basic payment information
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'receipt_image'  => $this->receipt_image ? url('storage/' . $this->receipt_image) : null,
            'transaction_id' => $this->transaction_id,
            'payment_method' => $this->payment_method,
            'notes'          => $this->notes,

            // Payer user information
            'payer' => [
                'id' => $this->payer->id,
                'name' => $this->payer->name,
                'email' => $this->payer->email,
                'role' => $this->payer->role,
            ],

            // Resource being paid information
            'payable' => [
                'type' => $this->payable_type,
                'type_name' => $this->payable_type_name,
                'id' => $this->payable_id,
                // Add resource details if loaded
                'details' => $this->when(
                    $this->relationLoaded('payable'),
                    function () {
                        return $this->getPayableDetails();
                    }
                ),
            ],

            // Dates
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // Additional information
            'can_update' => $this->canUpdate($request->user()),
            'can_delete' => $this->canDelete($request->user()),
        ];
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Get details of the resource being paid
     */
    private function getPayableDetails(): ?array
    {
        if (!$this->payable) {
            return null;
        }

        // Depending on resource type, display different details
        if ($this->payable_type === 'App\\Models\\Tour_bookings') {
            return [
                'booking_id' => $this->payable->id,
                'tour_title' => $this->payable->tour->title ?? 'N/A',
                'participants_count' => $this->payable->participants_count,
                'booking_status' => $this->payable->status,
            ];
        }

        if ($this->payable_type === 'App\\Models\\Plans') {
            return [
                'plan_id' => $this->payable->id,
                'plan_title' => $this->payable->title,
                'user_id' => $this->payable->user_id,
            ];
        }

        // Default fallback
        return [
            'id' => $this->payable->id,
        ];
    }

    /**
     * Check if update is possible
     */
    private function canUpdate($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can update any payment
        if ($user->role === 'admin') {
            return true;
        }

        // User can only update their pending payments
        return $this->payer_id === $user->id && $this->status === 'pending';
    }

    /**
     * Check if deletion is possible
     */
    private function canDelete($user): bool
    {
        if (!$user) {
            return false;
        }

        // Only admin can delete
        if ($user->role === 'admin') {
            return true;
        }

        // User can only delete their pending payments
        return $this->payer_id === $user->id && $this->status === 'pending';
    }

    /**
     * Additional meta information when used in collections
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
