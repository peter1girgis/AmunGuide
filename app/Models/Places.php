<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Places extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'description',
        'ticket_price',
        'image',
        'rating',
    ];

    protected $casts = [
        'ticket_price' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all media files (images, videos, 3d models) for this place
     */
    public function media()
    {
        return $this->hasMany(Place_media::class);
    }

    /**
     * Get all tour-place connections
     */
    public function tourPlaces()
    {
        return $this->hasMany(Tour_place::class);
    }

    /**
     * Get all tours that include this place
     */
    public function tours()
    {
        return $this->belongsToMany(Tours::class, 'tour_places')
                    ->withPivot('sequence')
                    ->orderBy('sequence');
    }

    /**
     * Get all plan items that reference this place
     */
    public function planItems()
    {
        return $this->hasMany(Plan_items::class);
    }

    /**
     * Get all plans that include this place
     */
    public function plans()
    {
        return $this->belongsToMany(Plans::class, 'plan_items')
                    ->withPivot('day_index');
    }

    /**
     * Get all generated images for this place
     */
    public function generatedImages()
    {
        return $this->hasMany(Generated_images::class);
    }

    /**
     * Get all activities associated with this place
     */
    public function activities()
    {
        return $this->hasMany(User_activities::class);
    }

    /**
     * Get all comments on this place (polymorphic)
     */
    public function comments()
    {
        return $this->morphMany(Comments::class, 'commentable');
    }

    /**
     * Get all likes on this place (polymorphic)
     */
    public function likes()
    {
        return $this->morphMany(Likes::class, 'likeable');
    }
}
