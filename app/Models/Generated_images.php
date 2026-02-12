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
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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


    /**
     * ═══════════════════════════════════════════════════════
     * SCOPES
     * ═══════════════════════════════════════════════════════
     */

    /**
     * صور محادثة معينة
     */
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * صور مكان معين
     */
    public function scopeForPlace($query, $placeId)
    {
        return $query->where('place_id', $placeId);
    }

    /**
     * الصور الأخيرة
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
     * التحقق من ارتباط الصورة بمكان
     */
    public function hasPlace(): bool
    {
        return !is_null($this->place_id);
    }

    /**
     * الحصول على URL الصورة الكامل
     */
    public function getFullImageUrl(): string
    {
        // إذا كان URL كامل
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }

        // إذا كان مسار نسبي
        return url($this->image_url);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * STATIC HELPERS
     * ═══════════════════════════════════════════════════════
     */

    /**
     * إنشاء صورة مولدة جديدة
     */
    public static function createImage(
        int $conversationId,
        string $imageUrl,
        ?int $placeId = null
    ): self {
        return self::create([
            'conversation_id' => $conversationId,
            'image_url' => $imageUrl,
            'place_id' => $placeId,
        ]);
    }
}
