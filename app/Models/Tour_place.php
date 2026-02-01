<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour_place extends Model
{
    use HasFactory;
    protected $table = 'tour_places';

    protected $fillable = [
        'tour_id',
        'place_id',
        'sequence',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the tour that this connection belongs to
     */
    public function tour()
    {
        return $this->belongsTo(Tours::class);
    }

    /**
     * Get the place that this connection belongs to
     */
    public function place()
    {
        return $this->belongsTo(Places::class);
    }
}
