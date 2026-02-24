<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tours extends Model
{
    use HasFactory;
    protected $fillable = [
        'guide_id',
        'title',
        'price',
        'start_date',
        'start_time',
        'plan_id',
        'payment_method',
        'details',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'start_time' => 'string',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the guide (user) that created this tour
     */
    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    /**
     * Get all tour-place connections for this tour
     */
    public function tourPlaces()
    {
        return $this->hasMany(Tour_place::class);
    }

    /**
     * Get all places in this tour (through tour_places)
     */
    public function places()
    {
        return $this->belongsToMany(Places::class, 'tour_places', 'tour_id', 'place_id')
                    ->withPivot('sequence')
                    ->orderBy('sequence');
    }
    public function plan() {
        return $this->belongsTo(Plans::class, 'plan_id', 'id');
    }

    /**
     * Get all bookings for this tour
     */
    public function bookings()
    {
        return $this->hasMany(Tour_bookings::class , 'tour_id');
    }

    /**
     * Get all tourists who booked this tour
     */
    public function tourists()
    {
        return $this->belongsToMany(User::class, 'tour_bookings', 'tour_id', 'tourist_id')
                    ->withPivot('amount', 'status', 'participants_count')
                    ->withTimestamps();
    }

    /**
     * Get all payments related to this tour (polymorphic)
     */
    public function payments()
    {
        return $this->morphMany(Payments::class, 'payable');
    }

    /**
     * Get all comments on this tour (polymorphic)
     */
    public function comments()
    {
        return $this->morphMany(Comments::class, 'commentable');
    }

    /**
     * Get all likes on this tour (polymorphic)
     */
    public function likes()
    {
        return $this->morphMany(Likes::class, 'likeable');
    }
     /**
     * ✅ الجولات النشطة (بعد اليوم)
     *
     * استخدام:
     * Tour::active()->get()
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '>', now());
    }

    /**
     * ✅ الجولات القديمة (انتهت)
     *
     * استخدام:
     * Tour::inactive()->get()
     */
    public function scopeInactive($query)
    {
        return $query->where('start_date', '<=', now());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Guide Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ الجولات لـ guide معين
     *
     * استخدام:
     * Tour::forGuide(5)->get()
     * Tour::forGuide($user_id)->count()
     */
    public function scopeForGuide($query, $guide_id)
    {
        return $query->where('guide_id', $guide_id);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Price Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ الجولات بحد السعر
     *
     * استخدام:
     * Tour::priceBetween(100, 500)->get()
     * Tour::priceBetween(200, 300)->paginate()
     */
    public function scopePriceBetween($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * ✅ الجولات أقل من سعر معين
     *
     * استخدام:
     * Tour::priceBelow(300)->get()
     */
    public function scopePriceBelow($query, $price)
    {
        return $query->where('price', '<', $price);
    }

    /**
     * ✅ الجولات أعلى من سعر معين
     *
     * استخدام:
     * Tour::priceAbove(100)->get()
     */
    public function scopePriceAbove($query, $price)
    {
        return $query->where('price', '>', $price);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Date Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ الجولات التي تبدأ في تاريخ معين أو بعده
     *
     * استخدام:
     * Tour::startingFrom('2026-02-10')->get()
     */
    public function scopeStartingFrom($query, $date)
    {
        return $query->where('start_date', '>=', $date);
    }

    /**
     * ✅ الجولات التي تبدأ قبل تاريخ معين
     *
     * استخدام:
     * Tour::startingBefore('2026-02-20')->get()
     */
    public function scopeStartingBefore($query, $date)
    {
        return $query->where('start_date', '<', $date);
    }

    /**
     * ✅ جولات معينة في أسبوع معين
     *
     * استخدام:
     * Tour::thisWeek()->get()
     */
    public function scopeThisWeek($query)
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return $query->whereBetween('start_date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * ✅ جولات معينة في شهر معين
     *
     * استخدام:
     * Tour::thisMonth()->get()
     */
    public function scopeThisMonth($query)
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return $query->whereBetween('start_date', [$startOfMonth, $endOfMonth]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Place Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ الجولات التي تحتوي على place معين
     *
     * استخدام:
     * Tour::withPlace(5)->get()
     */
    public function scopeWithPlace($query, $place_id)
    {
        return $query->whereHas('places', function ($q) use ($place_id) {
            $q->where('place_id', $place_id);
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Booking Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ الجولات التي لها bookings
     *
     * استخدام:
     * Tour::withBookings()->get()
     * Tour::withBookings()->count()
     */
    public function scopeWithBookings($query)
    {
        return $query->whereHas('bookings');
    }

    /**
     * ✅ الجولات بدون bookings
     *
     * استخدام:
     * Tour::withoutBookings()->get()
     */
    public function scopeWithoutBookings($query)
    {
        return $query->whereDoesntHave('bookings');
    }

    /**
     * ✅ الجولات المشهورة (أكثر bookings)
     *
     * استخدام:
     * Tour::popular()->get() // top 5
     * Tour::popular(10)->get() // top 10
     */
    public function scopePopular($query, $limit = 5)
    {
        return $query->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->limit($limit);
    }

    /**
     * ✅ جولات معينة بـ معدل حجوزات عالي
     *
     * استخدام:
     * Tour::highDemand()->get() // أكثر من 3 bookings
     * Tour::highDemand(5)->get() // أكثر من 5 bookings
     */
    public function scopeHighDemand($query, $minBookings = 3)
    {
        return $query->withCount('bookings')
            ->having('bookings_count', '>=', $minBookings);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Payment Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ الجولات حسب payment method
     *
     * استخدام:
     * Tour::paymentMethod('card')->get()
     * Tour::paymentMethod('cash')->count()
     */
    public function scopePaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Search Based
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ جولات معينة بـ search
     *
     * استخدام:
     * Tour::search('cairo')->get()
     * Tour::search('pyramids')->paginate()
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'like', "%{$term}%")
            ->orWhere('details', 'like', "%{$term}%");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Sorting
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ ترتيب حسب السعر تصاعدي
     *
     * استخدام:
     * Tour::sortByPriceAsc()->get()
     */
    public function scopeSortByPriceAsc($query)
    {
        return $query->orderBy('price');
    }

    /**
     * ✅ ترتيب حسب السعر تنازلي
     *
     * استخدام:
     * Tour::sortByPriceDesc()->get()
     */
    public function scopeSortByPriceDesc($query)
    {
        return $query->orderByDesc('price');
    }

    /**
     * ✅ ترتيب حسب الأحدث
     *
     * استخدام:
     * Tour::sortByNewest()->get()
     */
    public function scopeSortByNewest($query)
    {
        return $query->latest('created_at');
    }

    /**
     * ✅ ترتيب حسب الأقدم
     *
     * استخدام:
     * Tour::sortByOldest()->get()
     */
    public function scopeSortByOldest($query)
    {
        return $query->oldest('created_at');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES - Relationship Loading
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ Eager load recommended (avoid N+1)
     *
     * استخدام:
     * Tour::with($tour->recommended())->get()
     */
    public static function recommended()
    {
        return ['guide', 'places', 'bookings'];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UTILITY METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * ✅ هل الجولة نشطة؟
     */
    public function isActive(): bool
    {
        return $this->start_date > now();
    }

    /**
     * ✅ كم عدد الحجوزات؟
     */
    public function getBookingsCount(): int
    {
        return $this->bookings()->count();
    }

    /**
     * ✅ الإيراد الكلي من الجولة
     */
    public function getTotalRevenue()
    {
        return $this->bookings()
            ->where('status', 'approved')
            ->sum('amount');
    }

    /**
     * ✅ نسبة الحجز (booking percentage)
     */
    public function getBookingPercentage($capacity = 30)
    {
        $bookings = $this->getBookingsCount();
        return round(($bookings / $capacity) * 100, 2);
    }
}
