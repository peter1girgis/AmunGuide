<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * LikeResource - Convert Like model to JSON
 *
 * ✅ Format rating data
 * ✅ Display user information
 * ✅ Resource rating information
 */
class LikeResource extends JsonResource
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

            // ──────────────────────────────────────────────────
            // User Information (who rated)
            // ──────────────────────────────────────────────────
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'profile_image' => $this->user->profile_image
                    ? asset('storage/' . $this->user->profile_image)
                    : null,
            ],

            // ──────────────────────────────────────────────────
            // Likeable Information (the resource being rated)
            // ──────────────────────────────────────────────────
            'likeable_type' => $this->likeable_type,
            'likeable_id' => $this->likeable_id,

            // ──────────────────────────────────────────────────
            // Metadata
            // ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
