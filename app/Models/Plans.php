<?php

namespace App\Models;

use Dom\Comment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'title',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who created this plan
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all plan items in this plan
     */
    public function planItems()
    {
        return $this->hasMany(Plan_items::class);
    }

    /**
     * Get all places in this plan (through plan_items)
     */
    public function places()
    {
        return $this->belongsToMany(Places::class, 'plan_items')
                    ->withPivot('day_index');
    }

    /**
     * Get all comments on this plan (polymorphic)
     */
    public function comments()
    {
        return $this->morphMany(Comments::class, 'commentable');
    }

    /**
     * Get all likes on this plan (polymorphic)
     */
    public function likes()
    {
        return $this->morphMany(Likes::class, 'likeable');
    }
}
