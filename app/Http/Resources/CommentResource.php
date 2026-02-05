<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CommentResource - تحويل Comment model إلى JSON
 *
 * ✅ تنسيق البيانات للـ API response
 * ✅ عرض معلومات المستخدم (مختصرة)
 * ✅ تاريخ التعليق
 */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            // ──────────────────────────────────────────────────
            // Basic Information
            // ──────────────────────────────────────────────────
            'id' => $this->id,
            'content' => $this->content,

            // ──────────────────────────────────────────────────
            // User Information (الي اضاف التعليق)
            // ──────────────────────────────────────────────────
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'profile_image' => $this->user->profile_image
                    ? asset('storage/' . $this->user->profile_image)
                    : null,
            ],

            // ──────────────────────────────────────────────────
            // Commentable Information (الموضوع اللي اتعلق عليه)
            // ──────────────────────────────────────────────────
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,

            // ──────────────────────────────────────────────────
            // Metadata (معلومات عامة)
            // ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // ──────────────────────────────────────────────────
            // Is user the owner? (حق المستخدم في التعديل)
            // ──────────────────────────────────────────────────
            'is_owner' => $this->when(
                auth('sanctum')->check(),
                auth('sanctum')->id() === $this->user_id
            ),
        ];
    }
}
