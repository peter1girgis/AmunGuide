<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generated_images extends Model
{
    use HasFactory;
    protected $table = 'generated_images';

    protected $fillable = [
        'conversation_id',
        'place_id',
        'image_url',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the conversation this image was generated in
     */
    public function conversation()
    {
        return $this->belongsTo(Chatbot_conversations::class, 'conversation_id');
    }

    /**
     * Get the place this image is about (optional)
     */
    public function place()
    {
        return $this->belongsTo(Places::class);
    }
}
