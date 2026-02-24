<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Plans
 */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Core Fields ───────────────────────────────────────────────────
            'id'         => $this->id,
            'title'      => $this->title,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // ── Owner ─────────────────────────────────────────────────────────
            'user' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),

            // ── Places (via BelongsToMany) ────────────────────────────────────
            'places' => $this->whenLoaded('places', fn () => $this->places->map(fn ($place) => [
                'id'           => $place->id,
                'title'        => $place->title,
                'slug'         => $place->slug,
                'ticket_price' => $place->ticket_price,
                'rating'       => $place->rating,
                'image'        => $place->image,
                'day_index'    => $place->pivot->day_index,
            ])),
            'tours' => TourResource::collection($this->whenLoaded('tours')),

            // ── Calculated Fields (from Model helpers) ────────────────────────
            'total_price' => $this->whenLoaded('places', fn () => $this->totalPrice()),
            'total_days'  => $this->whenLoaded('planItems', fn () => $this->totalDays()),
            'is_complete' => $this->whenLoaded('planItems', fn () => $this->isComplete()),
            'summary'     => $this->whenLoaded('places', fn () => $this->summary()),
        ];
    }
}
