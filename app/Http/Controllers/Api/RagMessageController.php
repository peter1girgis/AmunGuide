<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RagDataRequest;
use App\Http\Resources\RagDataResource;
use App\Models\Places;
use App\Models\Tours;
use App\Models\Plans;
use Illuminate\Http\JsonResponse;

/**
 * RagMessageController - RAG Context Data Endpoint
 *
 * ✅ POST /api/v1/chat/rag-message
 * ✅ Auth: Sanctum token
 * ✅ Body: optional filter object
 *
 * Filter shape:
 * {
 *   "type": ["places", "tours", "plans"],
 *
 *   // ── Places ──────────────────────────
 *   "price":        { "min": 100, "max": 300 },
 *   "rating":       { "min": 4 },
 *   "has_image":    true,
 *   "has_comments": true,
 *
 *   // ── Tours ───────────────────────────
 *   "payment_method": ["cash", "both"],
 *   "date":         { "from": "2026-05-01", "to": "2026-05-30" },
 *
 *   // ── Plans ───────────────────────────
 *   "days":         { "max": 5 },
 *   "places_count": { "min": 3 }
 * }
 */
class RagMessageController extends Controller
{
    public function index(RagDataRequest $request): JsonResponse
    {
        try {
            $authUser = auth('sanctum')->user();
            $authUser->load(['plans.planItems.place']);

            // ── استخرج كل الـ filters من الـ request ─────────────────────
            $types         = $request->input('type', ['places', 'tours', 'plans']);
            $price         = $request->input('price');
            $rating        = $request->input('rating');
            $hasImage      = $request->input('has_image');
            $hasComments   = $request->input('has_comments');
            $paymentMethod = $request->input('payment_method');
            $date          = $request->input('date');
            $days          = $request->input('days');
            $placesCount   = $request->input('places_count');

            // ── applied_filters tracker ───────────────────────────────────
            // كل section بيتتبع الـ filters اللي اتطبقت عليه فعلاً
            $appliedFilters = [
                'type'    => $types,
                'places'  => null,
                'tours'   => null,
                'plans'   => null,
            ];

            // ── 1. Places ─────────────────────────────────────────────────
            $places = collect();
            if (in_array('places', $types)) {
                $query = Places::with(['comments.user', 'likes.user']);

                $placesApplied = [];

                if (!empty($price['min']) || !empty($price['max'])) {
                    if (!empty($price['min'])) $query->where('ticket_price', '>=', $price['min']);
                    if (!empty($price['max'])) $query->where('ticket_price', '<=', $price['max']);
                    $placesApplied['price'] = [
                        'min' => $price['min'] ?? null,
                        'max' => $price['max'] ?? null,
                    ];
                } else {
                    $placesApplied['price'] = null;
                }

                if (!empty($rating['min']) || !empty($rating['max'])) {
                    if (!empty($rating['min'])) $query->where('rating', '>=', $rating['min']);
                    if (!empty($rating['max'])) $query->where('rating', '<=', $rating['max']);
                    $placesApplied['rating'] = [
                        'min' => $rating['min'] ?? null,
                        'max' => $rating['max'] ?? null,
                    ];
                } else {
                    $placesApplied['rating'] = null;
                }

                if (!is_null($hasImage)) {
                    if ($hasImage) {
                        $query->whereNotNull('image')->where('image', '!=', '');
                    } else {
                        $query->where(function ($q) {
                            $q->whereNull('image')->orWhere('image', '');
                        });
                    }
                    $placesApplied['has_image'] = $hasImage;
                } else {
                    $placesApplied['has_image'] = null;
                }

                $places = $query->latest()->get();

                if (!is_null($hasComments)) {
                    $places = $hasComments
                        ? $places->filter(fn ($p) => $p->comments->isNotEmpty())->values()
                        : $places->filter(fn ($p) => $p->comments->isEmpty())->values();
                    $placesApplied['has_comments'] = $hasComments;
                } else {
                    $placesApplied['has_comments'] = null;
                }

                // filters غير خاصة بـ places → null
                $placesApplied['payment_method'] = null;
                $placesApplied['date']           = null;
                $placesApplied['days']           = null;
                $placesApplied['places_count']   = null;

                $appliedFilters['places'] = $placesApplied;
            }

            // ── 2. Tours ──────────────────────────────────────────────────
            $tours = collect();
            if (in_array('tours', $types)) {
                $query = Tours::with([
                    'guide', 'plan', 'tourPlaces.place',
                    'comments.user', 'likes.user', 'payments',
                ]);

                $toursApplied = [];

                if (!empty($price['min']) || !empty($price['max'])) {
                    if (!empty($price['min'])) $query->where('price', '>=', $price['min']);
                    if (!empty($price['max'])) $query->where('price', '<=', $price['max']);
                    $toursApplied['price'] = [
                        'min' => $price['min'] ?? null,
                        'max' => $price['max'] ?? null,
                    ];
                } else {
                    $toursApplied['price'] = null;
                }

                if (!empty($paymentMethod)) {
                    $query->whereIn('payment_method', $paymentMethod);
                    $toursApplied['payment_method'] = $paymentMethod;
                } else {
                    $toursApplied['payment_method'] = null;
                }

                if (!empty($date['from']) || !empty($date['to'])) {
                    if (!empty($date['from'])) $query->whereDate('start_date', '>=', $date['from']);
                    if (!empty($date['to']))   $query->whereDate('start_date', '<=', $date['to']);
                    $toursApplied['date'] = [
                        'from' => $date['from'] ?? null,
                        'to'   => $date['to']   ?? null,
                    ];
                } else {
                    $toursApplied['date'] = null;
                }

                // filters غير خاصة بـ tours → null
                $toursApplied['rating']       = null;
                $toursApplied['has_image']    = null;
                $toursApplied['has_comments'] = null;
                $toursApplied['days']         = null;
                $toursApplied['places_count'] = null;

                $tours = $query->latest()->get();
                $appliedFilters['tours'] = $toursApplied;
            }

            // ── 3. Plans ──────────────────────────────────────────────────
            $allPlans = collect();
            if (in_array('plans', $types)) {
                $allPlans = Plans::with(['user', 'planItems.place'])->latest()->get();

                $plansApplied = [];

                if (!empty($days['min']) || !empty($days['max'])) {
                    if (!empty($days['min'])) {
                        $allPlans = $allPlans->filter(fn ($p) =>
                            $p->planItems->pluck('day_index')->filter()->unique()->count() >= $days['min']
                        )->values();
                    }
                    if (!empty($days['max'])) {
                        $allPlans = $allPlans->filter(fn ($p) =>
                            $p->planItems->pluck('day_index')->filter()->unique()->count() <= $days['max']
                        )->values();
                    }
                    $plansApplied['days'] = [
                        'min' => $days['min'] ?? null,
                        'max' => $days['max'] ?? null,
                    ];
                } else {
                    $plansApplied['days'] = null;
                }

                if (!empty($placesCount['min']) || !empty($placesCount['max'])) {
                    if (!empty($placesCount['min'])) {
                        $allPlans = $allPlans->filter(fn ($p) =>
                            $p->planItems->count() >= $placesCount['min']
                        )->values();
                    }
                    if (!empty($placesCount['max'])) {
                        $allPlans = $allPlans->filter(fn ($p) =>
                            $p->planItems->count() <= $placesCount['max']
                        )->values();
                    }
                    $plansApplied['places_count'] = [
                        'min' => $placesCount['min'] ?? null,
                        'max' => $placesCount['max'] ?? null,
                    ];
                } else {
                    $plansApplied['places_count'] = null;
                }

                // filters غير خاصة بـ plans → null
                $plansApplied['price']          = null;
                $plansApplied['rating']         = null;
                $plansApplied['has_image']      = null;
                $plansApplied['has_comments']   = null;
                $plansApplied['payment_method'] = null;
                $plansApplied['date']           = null;

                $appliedFilters['plans'] = $plansApplied;
            }

            // ── Build resource payload ────────────────────────────────────
            $resourcePayload = [
                'user'           => $authUser,
                'places'         => $places,
                'tours'          => $tours,
                'all_plans'      => $allPlans,
                'filter'         => $types,
                'applied_filters'=> $appliedFilters,
            ];

            return response()->json([
                'success' => true,
                'data'    => RagDataResource::make($resourcePayload),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching RAG data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
