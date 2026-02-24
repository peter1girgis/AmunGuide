<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PlanItemResource - Convert plan items (places within the program) to JSON
 */
class PlanItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Day order (for example: Day 1, Day 2)
            'day_index' => $this->day_index,
            'day_label' => $this->day_index ? "Day " . $this->day_index : "Not specified",

            // Fetch data of the place linked to this item
            // Use PlaceResource to ensure unified place data format throughout the application
            'place' => new PlaceResource($this->whenLoaded('place')),

            // If you want to display place name directly for simplification in some cases
            'place_title' => $this->place->title ?? null,

            // Additional timestamps (if you will add them in future such as visit time)
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
