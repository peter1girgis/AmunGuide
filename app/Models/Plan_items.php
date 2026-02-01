<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan_items extends Model
{
    use HasFactory;
    protected $table = 'plan_items';

    protected $fillable = [
        'plan_id',
        'place_id',
        'day_index',
    ];

    protected $casts = [
        'day_index' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the plan that owns this item
     */
    public function plan()
    {
        return $this->belongsTo(Plans::class);
    }

    /**
     * Get the place in this plan item
     */
    public function place()
    {
        return $this->belongsTo(Places::class);
    }
}
