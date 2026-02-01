<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the tour that was booked
     */
    public function tour()
    {
        return $this->belongsTo(Tours::class);
    }

    /**
     * Get the tourist who made the booking
     */
    public function tourist()
    {
        return $this->belongsTo(User::class, 'tourist_id');
    }

    /**
     * Get all payments for this booking (polymorphic)
     */
    public function payments()
    {
        return $this->morphMany(Payments::class, 'payable');
    }

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
}
