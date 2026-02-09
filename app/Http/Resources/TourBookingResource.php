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

            // معلومات الحجز الأساسية
            'participants_count' => $this->participants_count,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // معلومات الرحلة
            'tour' => [
                'id' => $this->tour->id,
                'title' => $this->tour->title,
                'price' => (float) $this->tour->price,
                'start_date' => $this->tour->start_date,
                'start_time' => $this->tour->start_time,
                'payment_method' => $this->tour->payment_method,
                'details' => $this->tour->details,
                // معلومات المرشد
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

            // معلومات السائح
            'tourist' => [
                'id' => $this->tourist->id,
                'name' => $this->tourist->name,
                'email' => $this->tourist->email,
                'phone' => $this->tourist->phone,
            ],

            // حالة الدفع
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

            // التواريخ
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // معلومات الصلاحيات
            'permissions' => [
                'can_update' => $this->canUpdate($request->user()),
                'can_delete' => $this->canDelete($request->user()),
                'can_create_payment' => $this->canCreatePayment(),
                'can_be_cancelled' => $this->canBeCancelled(),
            ],
        ];
    }

    /**
     * الحصول على تسمية الحالة
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * التحقق من إمكانية التحديث
     */
    private function canUpdate($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin يمكنه تحديث أي حجز
        if ($user->role === 'admin') {
            return true;
        }

        // المرشد يمكنه تحديث حجوزات رحلاته
        if ($user->role === 'guide' && $this->tour->guide_id === $user->id) {
            return true;
        }

        // السائح يمكنه تحديث حجزه إذا كان pending
        return $this->tourist_id === $user->id && $this->status === 'pending';
    }

    /**
     * التحقق من إمكانية الحذف
     */
    private function canDelete($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin فقط يمكنه الحذف
        if ($user->role === 'admin') {
            return true;
        }

        // السائح يمكنه حذف حجزه إذا كان pending وليس له دفعة معتمدة
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
