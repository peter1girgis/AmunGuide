<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\User_activities;
use App\Models\Places;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    /**
     * 1. Analyze a single user (User-Centric)
     * Dedicated for Chatbot and building personal plans
     */
    public function getMyData(): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $activities = User_activities::where('user_id', $user->id)
                ->with('place:id,title')
                ->latest()
                ->get();

            $formattedActivities = $activities->map(function ($act) {

                // ── Step 1: Smart multi-layer JSON decode ────────────────────
                // The `details` column stores data in 3 formats found in production:
                //   (a) Normal JSON object   → {"place_title":"..."}
                //   (b) Double-escaped JSON  → "{\"place_title\":\"...\"}"
                //   (c) Double-escaped plain text → "\"New plan created...\""
                // We peel layers until we reach an array or a plain string.
                $raw     = $act->details;
                $decoded = is_array($raw) ? $raw : null;

                if (!$decoded && is_string($raw)) {
                    $pass = json_decode($raw, true);
                    // If the first decode still returns a string → double-escaped, decode again
                    if (is_string($pass)) {
                        $pass = json_decode($pass, true);
                    }
                    $decoded = is_array($pass) ? $pass : [];
                }

                $decoded = $decoded ?? [];

                // ── Step 2: Strip internal/redundant keys ────────────────────
                // ip_address, user_agent → infrastructure noise, not useful to chatbot
                // place_id, user_id      → already exist as top-level DB columns
                unset(
                    $decoded['ip_address'],
                    $decoded['user_agent'],
                    $decoded['place_id'],
                    $decoded['user_id']
                );

                // ── Step 3: Activity-specific field shaping ──────────────────
                $type = $act->activity_type;

                if ($type === 'visit') {

                    // Tour visit: identified by having tour_id in details
                    if (isset($decoded['tour_id'])) {
                        $shaped = [
                            'subject'    => 'tour',
                            'tour_id'    => $decoded['tour_id'],
                            'tour_title' => $decoded['tour_title'] ?? null,
                            'price'      => isset($decoded['price']) ? (float) $decoded['price'] : null,
                        ];
                    } else {
                        // Place visit
                        $shaped = [
                            'subject'     => 'place',
                            'place_title' => $decoded['place_title'] ?? ($act->place->title ?? null),
                            'price'       => isset($decoded['place_price']) ? (float) $decoded['place_price'] : null,
                        ];
                    }

                } elseif ($type === 'search') {

                    // Filter-based search (places_filter / tours_filter)
                    if (isset($decoded['filter_type'])) {
                        $criteria = $decoded['criteria'] ?? [];
                        $shaped = [
                            'search_kind' => 'filter',
                            'filter_type' => $decoded['filter_type'],
                            'criteria'    => is_array($criteria) ? $criteria : [],
                        ];
                    } else {
                        // Keyword / free-text search
                        $shaped = [
                            'search_kind'   => 'keyword',
                            'search_term'   => $decoded['search_term']
                                ?? $decoded['user_typed_this']
                                ?? $act->search_query,
                            'actual_match'  => $decoded['actual_match'] ?? null,
                            'results_count' => isset($decoded['results_count'])
                                ? (int) $decoded['results_count']
                                : null,
                        ];
                    }

                } elseif ($type === 'like') {

                    $actionVerb   = $decoded['action'] ?? '';
                    $resourceName = $decoded['resource_name'] ?? null;
                    $shaped = [
                        'action'        => str_contains($actionVerb, 'unlike') ? 'unliked' : 'liked',
                        'resource_type' => $decoded['resource_type'] ?? null,
                        'resource_id'   => $decoded['resource_id'] ?? null,
                        // Normalise the "N/A" placeholder stored in early rows
                        'resource_name' => ($resourceName === 'N/A') ? null : $resourceName,
                    ];

                } elseif ($type === 'comment') {

                    $shaped = [
                        'action'          => str_contains($decoded['action'] ?? '', 'added')
                            ? 'added'
                            : 'updated',
                        'resource_type'   => $decoded['commentable_type']
                            ?? $decoded['resource_type']
                            ?? null,
                        'comment_preview' => $decoded['comment_preview'] ?? null,
                        'old_preview'     => $decoded['old_preview'] ?? null,
                        'new_preview'     => $decoded['new_preview'] ?? null,
                    ];
                    // Only include resource_id when it differs from the column-level
                    // place_id — avoids sending the same value twice
                    $resourceId = $decoded['resource_id'] ?? null;
                    if ($resourceId !== null && $resourceId !== $act->place_id) {
                        $shaped['resource_id'] = $resourceId;
                    }

                } elseif ($type === 'plan_creation') {

                    $actionVerb = $decoded['action'] ?? '';

                    // Sub-type A: structured JSON (tour booking / cancellation)
                    if (in_array($actionVerb, ['tour_booking_created', 'tour_booking_cancelled'], true)) {
                        $shaped = [
                            'action'       => $actionVerb,
                            'tour_title'   => $decoded['tour_title'] ?? null,
                            'total_amount' => isset($decoded['total_amount'])
                                ? (float) $decoded['total_amount']
                                : (isset($decoded['amount_refunded'])
                                    ? (float) $decoded['amount_refunded']
                                    : null),
                            'status'       => $decoded['status'] ?? null,
                        ];
                        if (isset($decoded['cancelled_by'])) {
                            $shaped['cancelled_by'] = $decoded['cancelled_by'];
                        }

                    } else {
                        // Sub-type B: plain text string (double-encoded in DB).
                        // After our decode loop it may arrive as an empty array
                        // because the inner value is a string not an object.
                        // Re-peel from the original raw value to get the text.
                        $text = $act->details;
                        $text = json_decode($text, true) ?? $text;
                        if (is_string($text)) {
                            $text = json_decode($text, true) ?? $text;
                        }

                        $planAction  = 'plan_event';
                        $planTitle   = null;
                        $totalAmount = null;
                        $days        = null;
                        $status      = null;

                        if (is_string($text)) {
                            if (str_starts_with($text, 'New plan created')) {
                                $planAction = 'plan_created';
                            } elseif (str_starts_with($text, 'Plan updated')) {
                                $planAction = 'plan_updated';
                            }
                            if (preg_match('/Plan\s+"([^"]+)"/', $text, $m)) {
                                $planTitle = $m[1];
                            }
                            // Use the pipe-section value (no thousands comma issues)
                            if (preg_match('/Total EGP:\s*([\d,]+\.?\d*)/', $text, $m)) {
                                $totalAmount = (float) str_replace(',', '', $m[1]);
                            }
                            if (preg_match('/Days:\s*(\d+)/', $text, $m)) {
                                $days = (int) $m[1];
                            }
                            if (preg_match('/Complete:\s*(Yes|No)/i', $text, $m)) {
                                $status = strtolower($m[1]) === 'yes' ? 'complete' : 'incomplete';
                            }
                        }

                        $shaped = [
                            'action'       => $planAction,
                            'plan_title'   => $planTitle,
                            'total_amount' => $totalAmount,
                            'days'         => $days,
                            'status'       => $status,
                        ];
                    }

                } else {
                    $shaped = $decoded;
                }

                return [
                    'type'       => $type,
                    'place_name' => $act->place->title ?? null,
                    'timestamp'  => $act->created_at->toDateTimeString(),
                    ...$shaped,
                ];
            });

            return response()->json([
                'success' => true,
                'user_info' => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'statistics' => [
                    'total_actions' => $formattedActivities->count(),
                    'breakdown' => [
                        'visits'        => $activities->where('activity_type', 'visit')->count(),
                        'likes'         => $activities->where('activity_type', 'like')->count(),
                        'searches'      => $activities->where('activity_type', 'search')->count(),
                        'comments'      => $activities->where('activity_type', 'comment')->count(),
                        'plan_creation' => $activities->where('activity_type', 'plan_creation')->count(),
                    ],
                ],
                'timeline' => $formattedActivities,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 2. Comprehensive analysis for all users (Global Trends)
     * Dedicated for general analysis script and trends
     */
    public function getAllUsersData(): JsonResponse
    {
        if (auth('sanctum')->user()?->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // One query with eager loading — eliminates the original double-query
            // and the N+1 that happened on the second un-eager-loaded query.
            $allActivities = User_activities::with('place:id,title')
                ->latest()
                ->get();

            $placesStats = $allActivities
                ->whereNotNull('place_id')
                ->groupBy('place_id')
                ->map(function ($group) {
                    $place = $group->first()->place;

                    return [
                        'place_id' => $place->id ?? null,
                        'title'    => $place->title ?? 'Unknown',
                        'metrics'  => [
                            'visits'   => $group->where('activity_type', 'visit')->count(),
                            'likes'    => $group->where('activity_type', 'like')->count(),
                            'comments' => $group->where('activity_type', 'comment')->count(),
                        ],
                    ];
                })
                ->sortByDesc(fn($p) => $p['metrics']['visits'])
                ->values();

            $rawActivities = $allActivities->take(50)->map(function ($act) {
                // Same smart decode used in getMyData()
                $raw     = $act->details;
                $decoded = is_array($raw) ? $raw : null;

                if (!$decoded && is_string($raw)) {
                    $pass = json_decode($raw, true);
                    if (is_string($pass)) {
                        $pass = json_decode($pass, true);
                    }
                    $decoded = is_array($pass) ? $pass : [];
                }

                $decoded = $decoded ?? [];

                // Strip noise keys
                unset($decoded['ip_address'], $decoded['user_agent']);

                return [
                    'u_id'      => $act->user_id,
                    'type'      => $act->activity_type,
                    'place_id'  => $act->place_id,
                    'timestamp' => $act->created_at->toDateTimeString(),
                    'details'   => $decoded,
                ];
            });

            return response()->json([
                'success'      => true,
                'timestamp'    => now()->toIso8601String(),
                'global_stats' => [
                    'active_users_count' => $allActivities->pluck('user_id')->unique()->count(),
                    'trending_places'    => $placesStats,
                    'raw_activities'     => $rawActivities,
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
