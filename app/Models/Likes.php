<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Likes extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who made this like
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the likeable model (Place, Tour, TourBooking, or Plan)
     * Polymorphic relationship
     */
    public function likeable()
    {
        return $this->morphTo();
    }
}
