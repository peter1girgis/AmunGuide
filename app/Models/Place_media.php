<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place_media extends Model
{
    use HasFactory;
    protected $table = 'place_media';

    protected $fillable = [
        'place_id',
        'type',
        'file_path',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the place that owns this media
     */
    public function place()
    {
        return $this->belongsTo(Places::class);
    }
}
