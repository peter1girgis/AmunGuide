<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * RagDataResource
 *
 * Transforms the full (or filtered) project data snapshot into a structured,
 * AI-friendly format for the RAG (Retrieval-Augmented Generation) pipeline.
 *
 * Structure (depends on requested "filter"):
 * {
 *   "user":    { ...authenticated user info + their plans },
 *   "places":  [ { ...place, comments, likes } ],      // only if included
 *   "tours":   [ { ...tour, places, plan } ],          // only if included
 *   "plans":   [ { ...all public plans with items } ], // only if included
 *   "meta":    { counts, filter_sections, generated_at }
 * }
 */
class RagDataResource extends JsonResource
{
    /**
     * $resource holds the raw data array assembled by the controller.
     * Shape:
     * [
     *   'user'      => User,
     *   'places'    => Collection,
     *   'tours'     => Collection,
     *   'all_plans' => Collection,
     *   'filter'    => ['places', 'tours', 'plans'],
     * ]
     */
    public function toArray(Request $request): array
    {
        $authUser = $this->resource['user'];
        $places   = $this->resource['places'];
        $tours    = $this->resource['tours'];
        $allPlans = $this->resource['all_plans'];
        $filter   = $this->resource['filter'] ?? ['places', 'tours', 'plans'];

        // ── Build response dynamically based on requested sections ───────
        $response = [
            // User is always included (needed for context)
            'user' => $this->formatUser($authUser),
        ];

        if (in_array('places', $filter)) {
            $response['places'] = $places->map(fn ($place) => $this->formatPlace($place))->values();
        }

        if (in_array('tours', $filter)) {
            $response['tours'] = $tours->map(fn ($tour) => $this->formatTour($tour))->values();
        }

        if (in_array('plans', $filter)) {
            $response['plans'] = $allPlans->map(fn ($plan) => $this->formatPlan($plan))->values();
        }

        // ── Meta ──────────────────────────────────────────────────────────
        $response['meta'] = [
            'filter_sections' => $filter,
            'total_places'    => in_array('places', $filter) ? $places->count() : null,
            'total_tours'     => in_array('tours', $filter)  ? $tours->count()  : null,
            'total_plans'     => in_array('plans', $filter)  ? $allPlans->count(): null,
            'user_plans'      => $authUser->plans?->count() ?? 0,
            'generated_at'    => now()->toDateTimeString(),
        ];

        return $response;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private Formatters
    // ─────────────────────────────────────────────────────────────────────────

    private function formatUser($user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,

            // Only this user's own plans (may be empty)
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
            'image_url'    => $place->image
                                ? asset('storage/' . $place->image)
                                : null,

            // Engagement
            'engagement' => [
                'likes_count'    => $place->likes?->count() ?? 0,
                'comments_count' => $place->comments?->count() ?? 0,
            ],

            // Comments with author info
            'comments' => ($place->comments && $place->comments->isNotEmpty())
                ? $place->comments->map(fn ($comment) => [
                    'id'         => $comment->id,
                    'content'    => $comment->content,
                    'created_at' => $comment->created_at->toDateTimeString(),
                    'author'     => [
                        'id'   => $comment->user?->id,
                        'name' => $comment->user?->name,
                    ],
                ])->values()
                : [],

            // Likes with user info
            'likes' => ($place->likes && $place->likes->isNotEmpty())
                ? $place->likes->map(fn ($like) => [
                    'id'         => $like->id,
                    'created_at' => $like->created_at->toDateTimeString(),
                    'user'       => [
                        'id'   => $like->user?->id,
                        'name' => $like->user?->name,
                    ],
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

            // Payments summary (total paid + status counts)
            'payments_summary' => [
                'total_paid'    => (float) $tour->paid_amount,
                'status_counts' => [
                    'pending'  => $tour->payments->where('status', 'pending')->count(),
                    'approved' => $tour->payments->where('status', 'approved')->count(),
                ],
            ],

            // Guide (tour creator)
            'guide' => $tour->guide ? [
                'id'   => $tour->guide->id,
                'name' => $tour->guide->name,
            ] : null,

            // Linked plan (if any)
            'plan' => $tour->plan ? [
                'id'    => $tour->plan->id,
                'title' => $tour->plan->title,
            ] : null,

            // Places visited in this tour (ordered by sequence)
            'places' => ($tour->tourPlaces && $tour->tourPlaces->isNotEmpty())
                ? $tour->tourPlaces
                    ->sortBy('sequence')
                    ->map(fn ($tp) => [
                        'sequence'     => $tp->sequence,
                        'place_id'     => $tp->place?->id,
                        'place_title'  => $tp->place?->title,
                        'ticket_price' => $tp->place ? (float) $tp->place->ticket_price : null,
                    ])->values()
                : [],

            'created_at' => $tour->created_at->toDateTimeString(),

            // Engagement
            'engagement' => [
                'likes_count'    => $tour->likes?->count() ?? 0,
                'comments_count' => $tour->comments?->count() ?? 0,
            ],

            // Comments with author info
            'comments' => ($tour->comments && $tour->comments->isNotEmpty())
                ? $tour->comments->map(fn ($comment) => [
                    'id'         => $comment->id,
                    'content'    => $comment->content,
                    'created_at' => $comment->created_at->toDateTimeString(),
                    'author'     => [
                        'id'   => $comment->user?->id,
                        'name' => $comment->user?->name,
                    ],
                ])->values()
                : [],

            // Likes with user info
            'likes' => ($tour->likes && $tour->likes->isNotEmpty())
                ? $tour->likes->map(fn ($like) => [
                    'id'         => $like->id,
                    'created_at' => $like->created_at->toDateTimeString(),
                    'user'       => [
                        'id'   => $like->user?->id,
                        'name' => $like->user?->name,
                    ],
                ])->values()
                : [],
        ];
    }

    private function formatPlan($plan): array
    {
        return [
            'id'    => $plan->id,
            'title' => $plan->title,

            // Plan owner
            'owner' => $plan->user ? [
                'id'   => $plan->user->id,
                'name' => $plan->user->name,
            ] : null,

            // Plan items (places per day)
            'items' => ($plan->planItems && $plan->planItems->isNotEmpty())
                ? $plan->planItems
                    ->sortBy('day_index')
                    ->map(fn ($item) => [
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
