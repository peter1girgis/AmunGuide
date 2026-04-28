<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RagDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser       = $this->resource['user'];
        $places         = $this->resource['places'];
        $tours          = $this->resource['tours'];
        $allPlans       = $this->resource['all_plans'];
        $filter         = $this->resource['filter'] ?? ['places', 'tours', 'plans'];
        $appliedFilters = $this->resource['applied_filters'] ?? [];

        $response = [
            'user' => $this->formatUser($authUser),
        ];

        if (in_array('places', $filter)) {
            $response['places'] = $places->map(fn ($p) => $this->formatPlace($p))->values();
        }

        if (in_array('tours', $filter)) {
            $response['tours'] = $tours->map(fn ($t) => $this->formatTour($t))->values();
        }

        if (in_array('plans', $filter)) {
            $response['plans'] = $allPlans->map(fn ($p) => $this->formatPlan($p))->values();
        }

        $response['meta'] = [
            'requested_types' => $filter,
            'total_places'    => in_array('places', $filter) ? $places->count() : null,
            'total_tours'     => in_array('tours',  $filter) ? $tours->count()  : null,
            'total_plans'     => in_array('plans',  $filter) ? $allPlans->count(): null,
            'user_plans'      => $authUser->plans?->count() ?? 0,
            'generated_at'    => now()->toDateTimeString(),

            // ── الـ filters اللي اتطبقت فعلاً على كل section ─────────────
            // القيم اللي null = الـ filter ده مش خاص بالـ section دي أو مش اتبعت
            'applied_filters' => $appliedFilters,
        ];

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Formatters
    // ─────────────────────────────────────────────────────────────────────────

    private function formatUser($user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'my_plans' => ($user->plans && $user->plans->isNotEmpty())
                ? $user->plans->map(fn ($plan) => $this->formatPlan($plan))->values()
                : [],
        ];
    }

    private function formatPlace($place): array
    {
        return [
            'id'           => $place->id,
            'title'        => $place->title,
            'slug'         => $place->slug,
            'description'  => $place->description,
            'ticket_price' => (float) $place->ticket_price,
            'rating'       => $place->rating ? (float) $place->rating : null,
            'image_url'    => $place->image ? asset('storage/' . $place->image) : null,
            'engagement'   => [
                'likes_count'    => $place->likes?->count() ?? 0,
                'comments_count' => $place->comments?->count() ?? 0,
            ],
            'comments' => ($place->comments && $place->comments->isNotEmpty())
                ? $place->comments->map(fn ($c) => [
                    'id'         => $c->id,
                    'content'    => $c->content,
                    'created_at' => $c->created_at->toDateTimeString(),
                    'author'     => ['id' => $c->user?->id, 'name' => $c->user?->name],
                ])->values()
                : [],
            'likes' => ($place->likes && $place->likes->isNotEmpty())
                ? $place->likes->map(fn ($l) => [
                    'id'         => $l->id,
                    'created_at' => $l->created_at->toDateTimeString(),
                    'user'       => ['id' => $l->user?->id, 'name' => $l->user?->name],
                ])->values()
                : [],
            'created_at' => $place->created_at->toDateTimeString(),
        ];
    }

    private function formatTour($tour): array
    {
        return [
            'id'             => $tour->id,
            'title'          => $tour->title,
            'price'          => (float) $tour->price,
            'start_date'     => $tour->start_date,
            'start_time'     => $tour->start_time,
            'payment_method' => $tour->payment_method,
            'details'        => $tour->details,
            'payments_summary' => [
                'total_paid'    => (float) $tour->paid_amount,
                'status_counts' => [
                    'pending'  => $tour->payments->where('status', 'pending')->count(),
                    'approved' => $tour->payments->where('status', 'approved')->count(),
                ],
            ],
            'guide'  => $tour->guide  ? ['id' => $tour->guide->id,  'name' => $tour->guide->name]  : null,
            'plan'   => $tour->plan   ? ['id' => $tour->plan->id,   'title' => $tour->plan->title] : null,
            'places' => ($tour->tourPlaces && $tour->tourPlaces->isNotEmpty())
                ? $tour->tourPlaces->sortBy('sequence')->map(fn ($tp) => [
                    'sequence'     => $tp->sequence,
                    'place_id'     => $tp->place?->id,
                    'place_title'  => $tp->place?->title,
                    'ticket_price' => $tp->place ? (float) $tp->place->ticket_price : null,
                ])->values()
                : [],
            'created_at' => $tour->created_at->toDateTimeString(),
            'engagement' => [
                'likes_count'    => $tour->likes?->count() ?? 0,
                'comments_count' => $tour->comments?->count() ?? 0,
            ],
            'comments' => ($tour->comments && $tour->comments->isNotEmpty())
                ? $tour->comments->map(fn ($c) => [
                    'id'         => $c->id,
                    'content'    => $c->content,
                    'created_at' => $c->created_at->toDateTimeString(),
                    'author'     => ['id' => $c->user?->id, 'name' => $c->user?->name],
                ])->values()
                : [],
            'likes' => ($tour->likes && $tour->likes->isNotEmpty())
                ? $tour->likes->map(fn ($l) => [
                    'id'         => $l->id,
                    'created_at' => $l->created_at->toDateTimeString(),
                    'user'       => ['id' => $l->user?->id, 'name' => $l->user?->name],
                ])->values()
                : [],
        ];
    }

    private function formatPlan($plan): array
    {
        return [
            'id'    => $plan->id,
            'title' => $plan->title,
            'owner' => $plan->user ? ['id' => $plan->user->id, 'name' => $plan->user->name] : null,
            'items' => ($plan->planItems && $plan->planItems->isNotEmpty())
                ? $plan->planItems->sortBy('day_index')->map(fn ($item) => [
                    'day_index'    => $item->day_index,
                    'place_id'     => $item->place?->id,
                    'place_title'  => $item->place?->title,
                    'ticket_price' => $item->place ? (float) $item->place->ticket_price : null,
                    'rating'       => $item->place?->rating ? (float) $item->place->rating : null,
                ])->values()
                : [],
            'total_days'   => $plan->planItems?->pluck('day_index')->filter()->unique()->count() ?? 0,
            'total_places' => $plan->planItems?->count() ?? 0,
            'created_at'   => $plan->created_at->toDateTimeString(),
        ];
    }
}
