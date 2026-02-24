<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourBookingResource extends JsonResource
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

            // Basic booking information
            'participants_count' => $this->participants_count,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // Tour information
            'tour' => [
                'id' => $this->tour->id,
                'title' => $this->tour->title,
                'price' => (float) $this->tour->price,
                'start_date' => $this->tour->start_date,
                'start_time' => $this->tour->start_time,
                'payment_method' => $this->tour->payment_method,
                'details' => $this->tour->details,
                // Guide information
                'guide' => $this->when(
                    $this->relationLoaded('tour'),
                    function () {
                        return [
                            'id' => $this->tour->guide->id,
                            'name' => $this->tour->guide->name,
                            'email' => $this->tour->guide->email,
                            'phone' => $this->tour->guide->phone,
                        ];
                    }
                ),
            ],

            // Tourist information
            'tourist' => [
                'id' => $this->tourist->id,
                'name' => $this->tourist->name,
                'email' => $this->tourist->email,
                'phone' => $this->tourist->phone,
            ],

            // Payment status
            'payment_info' => [
                'status' => $this->getPaymentStatus(),
                'has_payment' => $this->hasPayment(),
                'has_approved_payment' => $this->hasApprovedPayment(),
                'latest_payment' => $this->when(
                    $this->hasPayment(),
                    function () {
                        $payment = $this->getLatestPayment();
                        return $payment ? [
                            'id' => $payment->id,
                            'amount' => (float) $payment->amount,
                            'status' => $payment->status,
                            'created_at' => $payment->created_at->toDateTimeString(),
                        ] : null;
                    }
                ),
            ],

            // Dates
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // Permissions information
            'permissions' => [
                'can_update' => $this->canUpdate($request->user()),
                'can_delete' => $this->canDelete($request->user()),
                'can_create_payment' => $this->canCreatePayment(),
                'can_be_cancelled' => $this->canBeCancelled(),
            ],
        ];
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Check update capability
     */
    private function canUpdate($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can update any booking
        if ($user->role === 'admin') {
            return true;
        }

        // Guide can update bookings for their tours
        if ($user->role === 'guide' && $this->tour->guide_id === $user->id) {
            return true;
        }

        // Tourist can update their booking if it is pending
        return $this->tourist_id === $user->id && $this->status === 'pending';
    }

    /**
     * Check delete capability
     */
    private function canDelete($user): bool
    {
        if (!$user) {
            return false;
        }

        // Only Admin can delete
        if ($user->role === 'admin') {
            return true;
        }

        // Tourist can delete their booking if it is pending and has no approved payment
        return $this->tourist_id === $user->id &&
            $this->status === 'pending' &&
            !$this->hasApprovedPayment();
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
