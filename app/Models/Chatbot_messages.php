<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chatbot_messages extends Model
{
    use HasFactory;
    protected $table = 'chatbot_messages';

    protected $fillable = [
        'conversation_id',
        'sender',
        'message',
    ];

    protected $casts = [
        'sender' => 'string',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the conversation this message belongs to
     */
    public function conversation()
    {
        return $this->belongsTo(Chatbot_conversations::class, 'conversation_id');
    }
}
