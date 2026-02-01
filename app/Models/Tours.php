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
        'payment_method',
        'details',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'start_time' => 'time',
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
        return $this->belongsToMany(Places::class, 'tour_places')
                    ->withPivot('sequence')
                    ->orderBy('sequence');
    }

    /**
     * Get all bookings for this tour
     */
    public function bookings()
    {
        return $this->hasMany(Tour_bookings::class);
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
}
