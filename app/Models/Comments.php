<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comments extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'content',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who made this comment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the commentable model (Place, Tour, TourBooking, or Plan)
     * Polymorphic relationship
     */
    public function commentable()
    {
        return $this->morphTo();
    }
}
