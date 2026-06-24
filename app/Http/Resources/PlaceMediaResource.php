<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceMediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // ──────────────────────────────────────────────────
            // Basic Information
            // ──────────────────────────────────────────────────
            'id'       => $this->id,
            'type'     => $this->type,

            // ──────────────────────────────────────────────────
            // File URL (full public URL)
            // ──────────────────────────────────────────────────
            'url'      => asset('storage/' . $this->file_path),

            // ──────────────────────────────────────────────────
            // Place (basic info, only if relation is loaded)
            // ──────────────────────────────────────────────────
            'place' => $this->when(
                $this->relationLoaded('place'),
                fn() => [
                    'id'    => $this->place->id,
                    'title' => $this->place->title,
                ]
            ),

            // ──────────────────────────────────────────────────
            // Timestamps
            // ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
