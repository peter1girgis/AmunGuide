<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CommentResource - Convert Comment model to JSON
 *
 * ✅ Format data for API response
 * ✅ Display user information (abbreviated)
 * ✅ Comment date
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
            // User Information (who added the comment)
            // ──────────────────────────────────────────────────
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'profile_image' => $this->user->profile_image
                    ? asset('storage/' . $this->user->profile_image)
                    : null,
            ],

            // ──────────────────────────────────────────────────
            // Commentable Information (the topic that was commented on)
            // ──────────────────────────────────────────────────
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,

            // ──────────────────────────────────────────────────
            // Metadata (general information)
            // ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // ──────────────────────────────────────────────────
            // Is user the owner? (user's right to edit)
            // ──────────────────────────────────────────────────
            'is_owner' => $this->when(
                auth('sanctum')->check(),
                auth('sanctum')->id() === $this->user_id
            ),
        ];
    }
}
