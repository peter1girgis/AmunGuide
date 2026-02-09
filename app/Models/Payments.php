<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'payer_id',
        'amount',
        'status',
        'payable_type',
        'payable_id',
        'receipt_image',
        'transaction_id',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ═══════════════════════════════════════════════════════
     * RELATIONSHIPS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * العلاقة مع المستخدم الدافع
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * العلاقة Polymorphic مع الموارد القابلة للدفع
     * (TourBooking, Plan, أو أي مورد آخر)
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * ═══════════════════════════════════════════════════════
     * SCOPES
     * ═══════════════════════════════════════════════════════
     */

    /**
     * الدفعات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * الدفعات المعتمدة
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * الدفعات الفاشلة
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * دفعات مستخدم معين
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('payer_id', $userId);
    }

    /**
     * دفعات نوع معين من الموارد
     */
    public function scopeForPayableType($query, $type)
    {
        return $query->where('payable_type', $type);
    }

    /**
     * دفعات ضمن نطاق زمني
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * الدفعات الأخيرة
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * ═══════════════════════════════════════════════════════
     * HELPER METHODS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * التحقق من حالة الدفع
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * تحديث حالة الدفع إلى معتمد
     */
    public function approve(): bool
    {
        // return $this->update(['status' => 'approved']);
        $updated = $this->update(['status' => 'approved']);


        $payable = $this->payable;

        if ($updated && $payable instanceof \App\Models\Tour_bookings) {

            if (!$payable->isApproved()) {
                $payable->approve();
            }
        }

        return $updated;
    }

    /**
     * تحديث حالة الدفع إلى فاشل
     */
    public function markAsFailed(): bool
    {
        $updated = $this->update(['status' => 'failed']);

        // 2. التحقق من المورد المرتبط (payable)
        // نستخدم الـ optional عشان لو المورد ممسوح الكود ميضربش
        $payable = $this->payable;

        if ($updated && $payable instanceof \App\Models\Tour_bookings) {
            // إذا كان المورد هو حجز رحلة وحالته ليست مرفوضة أصلاً
            if (!$payable->isRejected()) {
                $payable->reject(); // بنادي ميثود الـ reject اللي إحنا معرفينها في موديل الحجز
            }
        }

        return $updated;
    }

    /**
     * الحصول على نوع المورد المدفوع بشكل قابل للقراءة
     */
    public function getPayableTypeNameAttribute(): string
    {
        $typeMap = [
            'App\\Models\\Tour_bookings' => 'Tour Booking',
            'App\\Models\\Plan' => 'Travel Plan',
        ];

        return $typeMap[$this->payable_type] ?? 'Unknown';
    }

    /**
     * التحقق من صلاحية المستخدم للوصول لهذه الدفعة
     */
    public function belongsToUser($userId): bool
    {
        return $this->payer_id == $userId;
    }

    /**
     * الحصول على معلومات الدفع كاملة مع المورد
     */
    public function getFullDetails(): array
    {
        return [
            'payment_id' => $this->id,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'payer' => [
                'id' => $this->payer->id,
                'name' => $this->payer->name,
                'email' => $this->payer->email,
            ],
            'payable' => [
                'type' => $this->payable_type_name,
                'id' => $this->payable_id,
                'details' => $this->payable ? $this->payable->toArray() : null,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * STATIC HELPERS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * إنشاء دفعة جديدة
     */
    public static function createPayment(
        int $payerId,
        float $amount,
        string $payableType,
        int $payableId,
        string $status = 'pending',
        ?string $receiptImage = null,  // حقل جديد
        ?string $transactionId = null, // حقل جديد
        ?string $paymentMethod = null, // حقل جديد
        ?string $notes = null          // حقل جديد
    ): self {
        return self::create([
            'payer_id'       => $payerId,
            'amount'         => $amount,
            'payable_type'   => $payableType,
            'payable_id'     => $payableId,
            'status'         => $status,
            'receipt_image'  => $receiptImage,
            'transaction_id' => $transactionId,
            'payment_method' => $paymentMethod,
            'notes'          => $notes,
        ]);
    }

    /**
     * إحصائيات الدفعات لمستخدم معين
     */
    public static function getUserPaymentStats(int $userId): array
    {
        $payments = self::where('payer_id', $userId)->get();

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'pending_count' => $payments->where('status', 'pending')->count(),
            'approved_count' => $payments->where('status', 'approved')->count(),
            'failed_count' => $payments->where('status', 'failed')->count(),
            'pending_amount' => $payments->where('status', 'pending')->sum('amount'),
            'approved_amount' => $payments->where('status', 'approved')->sum('amount'),
            'failed_amount' => $payments->where('status', 'failed')->sum('amount'),
            
        ];
    }

    /**
     * التحقق من وجود دفعة معلقة لمورد معين
     */
    public static function hasPendingPayment(string $payableType, int $payableId, int $userId): bool
    {
        return self::where('payable_type', $payableType)
            ->where('payable_id', $payableId)
            ->where('payer_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }
}
