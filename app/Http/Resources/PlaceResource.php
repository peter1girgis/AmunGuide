<?php
/*
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'ticket_price' => $this->ticket_price,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rating' => $this->rating,
            'views_count' => $this->views_count ?? 0,
            'is_active' => (bool)$this->is_active,

            // ✅ Counts (when loaded)
            'statistics' => [
                'likes_count' => $this->likes_count ?? 0,
                'comments_count' => $this->comments_count ?? 0,
            ],

            // ✅ Media (when loaded)
            'media' => PlaceMediaResource::collection($this->whenLoaded('media')),

            // ✅ Related data (when loaded)
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'likes' => UserLikeResource::collection($this->whenLoaded('likes')),

            // ✅ Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
} */


namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ✅ Basic Information
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,

            // ✅ Pricing & Rating
            'ticket_price' => (float) $this->ticket_price,
            'rating' => $this->rating ? (float) $this->rating : null,

            // ✅ Media
            'image' => $this->image ? asset('storage/' . $this->image) : null,

            // ✅ Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
