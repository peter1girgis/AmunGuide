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

            // معلومات الدفع الأساسية
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'receipt_image'  => $this->receipt_image ? url('storage/' . $this->receipt_image) : null,
            'transaction_id' => $this->transaction_id,
            'payment_method' => $this->payment_method,
            'notes'          => $this->notes,

            // معلومات المستخدم الدافع
            'payer' => [
                'id' => $this->payer->id,
                'name' => $this->payer->name,
                'email' => $this->payer->email,
                'role' => $this->payer->role,
            ],

            // معلومات المورد المدفوع
            'payable' => [
                'type' => $this->payable_type,
                'type_name' => $this->payable_type_name,
                'id' => $this->payable_id,
                // إضافة تفاصيل المورد إذا كان محملاً
                'details' => $this->when(
                    $this->relationLoaded('payable'),
                    function () {
                        return $this->getPayableDetails();
                    }
                ),
            ],

            // التواريخ
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // معلومات إضافية
            'can_update' => $this->canUpdate($request->user()),
            'can_delete' => $this->canDelete($request->user()),
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
            'failed' => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * الحصول على تفاصيل المورد المدفوع
     */
    private function getPayableDetails(): ?array
    {
        if (!$this->payable) {
            return null;
        }

        // حسب نوع المورد، نعرض تفاصيل مختلفة
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
     * التحقق من إمكانية التحديث
     */
    private function canUpdate($user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin يمكنه تحديث أي دفعة
        if ($user->role === 'admin') {
            return true;
        }

        // المستخدم يمكنه تحديث دفعاته المعلقة فقط
        return $this->payer_id === $user->id && $this->status === 'pending';
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

        // المستخدم يمكنه حذف دفعاته المعلقة فقط
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
