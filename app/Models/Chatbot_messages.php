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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the conversation this message belongs to
     */
    public function conversation()
    {
        return $this->belongsTo(Chatbot_conversations::class, 'conversation_id');
    }


    /**
     * ═══════════════════════════════════════════════════════
     * SCOPES
     * ═══════════════════════════════════════════════════════
     */

    /**
     * رسائل محادثة معينة
     */
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * رسائل من المستخدم
     */
    public function scopeFromUser($query)
    {
        return $query->where('sender', 'user');
    }

    /**
     * رسائل من البوت
     */
    public function scopeFromBot($query)
    {
        return $query->where('sender', 'bot');
    }

    /**
     * الرسائل الأخيرة
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * الرسائل الأقدم (للعرض بالترتيب الزمني)
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * ═══════════════════════════════════════════════════════
     * HELPER METHODS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * التحقق من أن الرسالة من المستخدم
     */
    public function isFromUser(): bool
    {
        return $this->sender === 'user';
    }

    /**
     * التحقق من أن الرسالة من البوت
     */
    public function isFromBot(): bool
    {
        return $this->sender === 'bot';
    }

    /**
     * الحصول على اسم المرسل
     */
    public function getSenderName(): string
    {
        if ($this->isFromUser()) {
            return $this->conversation->user->name ?? 'User';
        }

        return 'Bot';
    }

    /**
     * ═══════════════════════════════════════════════════════
     * STATIC HELPERS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * إنشاء رسالة جديدة
     */
    public static function createMessage(
        int $conversationId,
        string $sender,
        string $message
    ): self {
        return self::create([
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'message' => $message,
        ]);
    }
}
