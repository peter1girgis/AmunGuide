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
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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


    /**
     * ═══════════════════════════════════════════════════════
     * SCOPES
     * ═══════════════════════════════════════════════════════
     */

    /**
     * محادثات مستخدم معين
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * محادثات حسب السياق
     */
    public function scopeByContext($query, $context)
    {
        return $query->where('context', $context);
    }

    /**
     * المحادثات التي تحتوي على صور
     */
    public function scopeWithImages($query)
    {
        return $query->has('generatedImages');
    }

    /**
     * المحادثات الأخيرة
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * ═══════════════════════════════════════════════════════
     * HELPER METHODS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * التحقق من صلاحية المستخدم للوصول للمحادثة
     */
    public function belongsToUser($userId): bool
    {
        return $this->user_id == $userId;
    }

    /**
     * الحصول على عدد الرسائل
     */
    public function getMessagesCount(): int
    {
        return $this->messages()->count();
    }

    /**
     * الحصول على عدد الصور المولدة
     */
    public function getImagesCount(): int
    {
        return $this->generatedImages()->count();
    }

    /**
     * الحصول على آخر رسالة
     */
    public function getLastMessage()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * الحصول على رسائل المستخدم فقط
     */
    public function getUserMessages()
    {
        return $this->messages()->where('sender', 'user')->get();
    }

    /**
     * الحصول على رسائل البوت فقط
     */
    public function getBotMessages()
    {
        return $this->messages()->where('sender', 'bot')->get();
    }

    /**
     * التحقق من وجود صور في المحادثة
     */
    public function hasImages(): bool
    {
        return $this->generatedImages()->exists();
    }

    /**
     * ═══════════════════════════════════════════════════════
     * STATIC HELPERS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * إنشاء محادثة جديدة
     */
    public static function startConversation(int $userId, ?string $context = null): self
    {
        return self::create([
            'user_id' => $userId,
            'context' => $context,
        ]);
    }

    /**
     * إحصائيات محادثات مستخدم
     */
    public static function getUserStats(int $userId): array
    {
        $conversations = self::where('user_id', $userId)->get();

        return [
            'total_conversations' => $conversations->count(),
            'total_messages' => Chatbot_messages::whereIn(
                'conversation_id',
                $conversations->pluck('id')
            )->count(),
            'total_images' => Generated_images::whereIn(
                'conversation_id',
                $conversations->pluck('id')
            )->count(),
            'conversations_with_images' => $conversations->filter->hasImages()->count(),
        ];
    }
}
