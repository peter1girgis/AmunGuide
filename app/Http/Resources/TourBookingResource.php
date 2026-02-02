<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participants_count' => (int) $this->participants_count,
            'total_amount' => (float) $this->amount,
            'status' => $this->status,

            // ✅ معلومات السائح (Tourist)
            // نستخدم whenLoaded عشان لو معملناش Load للعلاقة ميعملش Error
            'tourist' => [
                'id' => $this->tourist_id,
                'name' => $this->whenLoaded('tourist', fn() => $this->tourist->name),
                'email' => $this->whenLoaded('tourist', fn() => $this->tourist->email),
            ],

            // ✅ التوقيت
            'booked_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
