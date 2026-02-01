<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_activities extends Model
{
    use HasFactory;
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'activity_type',
        'search_query',
        'place_id',
        'details',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who performed this activity
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the place related to this activity (if applicable)
     */
    public function place()
    {
        return $this->belongsTo(Places::class);
    }
}
