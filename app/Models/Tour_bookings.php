<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tour_bookings extends Model
{
    use HasFactory;
    protected $table = 'tour_bookings';

    protected $fillable = [
        'tour_id',
        'tourist_id',
        'participants_count',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'participants_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    /**
     * Get all comments on this booking (polymorphic)
     */
    public function comments()
    {
        return $this->morphMany(Comments::class, 'commentable');
    }

    /**
     * Get all likes on this booking (polymorphic)
     */
    public function likes()
    {
        return $this->morphMany(Likes::class, 'likeable');
    }
    /**
     * ═══════════════════════════════════════════════════════
     * RELATIONSHIPS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * The Relationship wiht Tour
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tours::class, 'tour_id');
    }

    /**
     * The Relationship wiht User (Tourist)
     */
    public function tourist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tourist_id');
    }

    /**
     * The Relationship wiht Payments (Polymorphic)
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payments::class, 'payable');
    }

    /**
     * ═══════════════════════════════════════════════════════
     * SCOPES
     * ═══════════════════════════════════════════════════════
     */

    /**
     * الحجوزات المعلقة (في انتظار الدفع)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * الحجوزات المعتمدة (تم الدفع والموافقة)
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * الحجوزات المرفوضة
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * حجوزات سائح معين
     */
    public function scopeForTourist($query, $touristId)
    {
        return $query->where('tourist_id', $touristId);
    }

    /**
     * حجوزات رحلة معينة
     */
    public function scopeForTour($query, $tourId)
    {
        return $query->where('tour_id', $tourId);
    }

    /**
     * حجوزات مرشد معين (عن طريق الرحلات)
     */
    public function scopeForGuide($query, $guideId)
    {
        return $query->whereHas('tour', function ($q) use ($guideId) {
            $q->where('guide_id', $guideId);
        });
    }

    /**
     * الحجوزات التي لها دفعة معتمدة
     */
    public function scopeWithApprovedPayment($query)
    {
        return $query->whereHas('payments', function ($q) {
            $q->where('status', 'approved');
        });
    }

    /**
     * الحجوزات التي لم يتم دفعها بعد
     */
    public function scopeWithoutPayment($query)
    {
        return $query->doesntHave('payments');
    }

    /**
     * الحجوزات الأخيرة
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
     * التحقق من حالة الحجز
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * تحديث حالة الحجز
     */
    public function approve(): bool
    {
        return $this->update(['status' => 'approved']);
    }

    public function reject(): bool
    {
        return $this->update(['status' => 'rejected']);
    }

    public function markAsPending(): bool
    {
        return $this->update(['status' => 'pending']);
    }

    /**
     * check if the booking has an approved payment
     */
    public function hasApprovedPayment(): bool
    {
        return $this->payments()
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * check if the booking has any payment
     */
    public function hasPayment(): bool
    {
        return $this->payments()->exists();
    }

    /**
     * الحصول على آخر دفعة
     */
    public function getLatestPayment()
    {
        return $this->payments()->latest()->first();
    }

    /**
     * check if the admin can cancel the booking
     */
    public function canBeCancelled(): bool
    {
        // if the booking is pending or has no approved payment, it can be cancelled
        return $this->isPending() || !$this->hasApprovedPayment();
    }

    /**
     * التحقق من صلاحية المستخدم للوصول لهذا الحجز
     */
    public function belongsToUser($userId): bool
    {
        return $this->tourist_id == $userId;
    }

    /**
     * التحقق من إمكانية إنشاء دفعة لهذا الحجز
     */
    public function canCreatePayment(): bool
    {
        // يمكن إنشاء دفعة إذا كان الحجز pending وليس له دفعة معلقة
        if (!$this->isPending()) {
            return false;
        }

        // التحقق من عدم وجود دفعة معلقة
        $hasPendingPayment = $this->payments()
            ->where('status', 'pending')
            ->exists();


        return !$hasPendingPayment;
    }

    /**
     * حساب المبلغ الإجمالي
     */
    public function calculateTotalAmount(): float
    {
        // المبلغ = سعر الرحلة × عدد المشاركين
        return (float) ($this->tour->price * $this->participants_count);
    }

    /**
     * GetFullDetails
     */
    public function getFullDetails(): array
    {
        return [
            'booking_id' => $this->id,
            'status' => $this->status,
            'participants_count' => $this->participants_count,
            'amount' => (float) $this->amount,
            'tour' => [
                'id' => $this->tour->id,
                'title' => $this->tour->title,
                'price' => (float) $this->tour->price,
                'start_date' => $this->tour->start_date,
                'start_time' => $this->tour->start_time,
                'guide' => [
                    'id' => $this->tour->guide->id,
                    'name' => $this->tour->guide->name,
                ],
            ],
            'tourist' => [
                'id' => $this->tourist->id,
                'name' => $this->tourist->name,
                'email' => $this->tourist->email,
            ],
            'payment_status' => $this->getPaymentStatus(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * الحصول على حالة الدفع
     */
    public function getPaymentStatus(): string
    {
        $latestPayment = $this->getLatestPayment();

        if (!$latestPayment) {
            return 'not_paid';
        }

        return $latestPayment->status; // pending, approved, failed
    }

    /**
     * ═══════════════════════════════════════════════════════
     * STATIC HELPERS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * إنشاء حجز جديد
     */
    public static function createBooking(
        int $tourId,
        int $touristId,
        int $participantsCount = 1
    ): self {
        // الحصول على الرحلة لحساب المبلغ
        $tour = Tours::findOrFail($tourId);
        $amount = $tour->price * $participantsCount;

        return self::create([
            'tour_id' => $tourId,
            'tourist_id' => $touristId,
            'participants_count' => $participantsCount,
            'amount' => $amount,
            'status' => 'pending', // دائماً تبدأ pending
        ]);
    }

    /**
     * Statistical of Tourist Booking Status
     */
    public static function getTouristBookingStats(int $touristId): array
    {
        $bookings = self::where('tourist_id', $touristId)->get();

        return [
            'total_bookings' => $bookings->count(),
            'total_amount' => $bookings->sum('amount'),
            'pending_count' => $bookings->where('status', 'pending')->count(),
            'approved_count' => $bookings->where('status', 'approved')->count(),
            'rejected_count' => $bookings->where('status', 'rejected')->count(),
            'total_participants' => $bookings->sum('participants_count'),
        ];
    }

    /**
     * Statistical of Guide Booking Status
     */
    public static function getGuideBookingStats(int $guideId): array
    {
        $bookings = self::whereHas('tour', function ($q) use ($guideId) {
            $q->where('guide_id', $guideId);
        })->get();

        return [
            'total_bookings' => $bookings->count(),
            'total_revenue' => $bookings->where('status', 'approved')->sum('amount'),
            'pending_bookings' => $bookings->where('status', 'pending')->count(),
            'approved_bookings' => $bookings->where('status', 'approved')->count(),
            'rejected_bookings' => $bookings->where('status', 'rejected')->count(),
        ];
    }

    /**
     * التحقق من توفر مقاعد في الرحلة
     */
    public static function checkAvailability(int $tourId, int $requestedSeats = 1): bool
    {
        // يمكن إضافة لوجيك للتحقق من السعة القصوى للرحلة
        // حالياً نفترض أن جميع الحجوزات متاحة
        return true;
    }
}
