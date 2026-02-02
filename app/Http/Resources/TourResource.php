<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TourResource - Transform Tour Model to JSON
 *
 * ✅ تنسيق البيانات للـ API response
 * ✅ التحكم في البيانات المرسلة حسب الـ route
 */
class TourResource extends JsonResource
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
            'title' => $this->title,
            'details' => $this->details,

            // ──────────────────────────────────────────────────
            // Price Information
            // ──────────────────────────────────────────────────
            'price' => (float) $this->price,
            'price_formatted' => 'EGP ' . number_format($this->price, 2),

            // ──────────────────────────────────────────────────
            // Schedule Information
            // ──────────────────────────────────────────────────
            'start_date' => $this->start_date->format('Y-m-d'),
            'start_date_formatted' => $this->start_date->format('F j, Y'),
            'start_time' => $this->start_time,

            // ──────────────────────────────────────────────────
            // Guide Information
            // ──────────────────────────────────────────────────
            'guide' => [
                'id' => $this->guide->id,
                'name' => $this->guide->name,
                'phone' => $this->guide->phone,
                // Email only in show/detail endpoints
                'email' => $this->when(
                    $request->routeIs('tours.show'),
                    $this->guide->email
                ),
                'profile_image' => asset('storage/' . $this->guide->profile_image),
            ],

            // ──────────────────────────────────────────────────
            // Payment Information
            // ──────────────────────────────────────────────────
            'payment_method' => $this->payment_method,
            'payment_options' => [
                'cash' => in_array($this->payment_method, ['cash', 'both']),
                'card' => in_array($this->payment_method, ['card', 'both']),
            ],

            // ──────────────────────────────────────────────────
            // Places Information
            // ──────────────────────────────────────────────────
            'places' => PlaceResource::collection($this->places),
            'places_count' => $this->places->count(),

            // ──────────────────────────────────────────────────
            // Booking Statistics
            // ──────────────────────────────────────────────────
            'booking_count' => $this->when(
                $request->routeIs('tours.index', 'tours.show', 'tours.my-tours'),
                $this->bookings_count ?? $this->bookings()->count()
            ),

            'is_popular' => $this->when(
                $request->routeIs('tours.index'),
                $this->bookings()->count() >= 5
            ),

            // ──────────────────────────────────────────────────
            // Detailed Bookings (only in detail endpoints)
            // ──────────────────────────────────────────────────
            'bookings' => $this->when(
                $request->routeIs('tours.show', 'tours.bookings'),
                TourBookingResource::collection($this->bookings)
            ),

            // ──────────────────────────────────────────────────
            // Status Information
            // ──────────────────────────────────────────────────
            'status' => $this->isActive() ? 'active' : 'inactive',
            'is_active' => $this->isActive(),

            // ──────────────────────────────────────────────────
            // Timestamps
            // ──────────────────────────────────────────────────
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'request_time' => now()->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
