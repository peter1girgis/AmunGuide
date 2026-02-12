<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'image_url' => $this->image_url,
            'full_image_url' => $this->getFullImageUrl(),

            // معلومات المكان (إذا كان مرتبطاً)
            'place' => $this->when(
                $this->hasPlace() && $this->relationLoaded('place'),
                function () {
                    return [
                        'id' => $this->place->id,
                        'title' => $this->place->title,
                        'slug' => $this->place->slug,
                    ];
                }
            ),

            'has_place' => $this->hasPlace(),
            'created_at' => $this->created_at->toDateTimeString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
