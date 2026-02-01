<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chatbot_conversations extends Model
{
    use HasFactory;

    protected $table = 'chatbot_conversations';

    protected $fillable = [
        'user_id',
        'context',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who started this conversation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages in this conversation
     */
    public function messages()
    {
        return $this->hasMany(Chatbot_messages::class, 'conversation_id');
    }

    /**
     * Get all generated images from this conversation
     */
    public function generatedImages()
    {
        return $this->hasMany(Generated_images::class, 'conversation_id');
    }
}
