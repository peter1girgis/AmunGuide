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

            // Retrieve activities with Place and filter unnecessary data
            $activities = User_activities::where('user_id', $user->id)
                ->with('place:id,title') // We only take the ID and title to save JSON space
                ->latest()
                ->get();

            $formattedActivities = $activities->map(function ($act) {
                // 1. Decrypt the details (whether it's an array, JSON text, or empty)
                $extraDetails = is_string($act->details) ? json_decode($act->details, true) : ($act->details ?? []);

                // 2. Merge basic data with dynamic details
                return array_merge([
                    'type' => $act->activity_type,
                    'place_name' => $act->place->title ?? null,
                    'search_query' => $act->search_query ?? null,
                    'timestamp' => $act->created_at->toDateTimeString(),
                ], (array)$extraDetails); // Any data in details will become base fields here
            });

            return response()->json([
                'success' => true,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'statistics' => [
                    'total_actions' => $formattedActivities->count(),
                    'breakdown' => [
                        'visits' => $activities->where('activity_type', 'visit')->count(),
                        'likes' => $activities->where('activity_type', 'like')->count(),
                        'searches' => $activities->where('activity_type', 'search')->count(),
                    ]
                ],
                'timeline' => $formattedActivities
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis Error: ' . $e->getMessage()
            ], 500);
        }
}

    /**
     * 2. Comprehensive analysis for all users (Global Trends)
     * Dedicated for general analysis script and trends
     */
    public function getAllUsersData(): JsonResponse
    {
        if(auth('sanctum')->user()?->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            // Aggregate activities by places directly to know the trend
            $placesStats = User_activities::with('place')
                ->whereNotNull('place_id')
                ->get()
                ->groupBy('place_id')
                ->map(function ($group) {
                    $place = $group->first()->place;

                    // Aggregate scattered details in activities for this place
                    $allDetails = $group->pluck('details')->filter()->map(fn($d) => is_string($d) ? json_decode($d, true) : $d);

                    return [
                        'place_id' => $place->id ?? null,
                        'title' => $place->title ?? 'Unknown',
                        'metrics' => [
                            'visits' => $group->where('activity_type', 'visit')->count(),
                            'likes' => $group->where('activity_type', 'like')->count(),
                            'comments' => $group->where('activity_type', 'comment')->count(),
                        ],
                        // Extract dynamic "repeated" details if present (example: time spent)
                        'custom_metadata' => $allDetails->flatten(1)->take(5)
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'global_stats' => [
                    'active_users_count' => User_activities::distinct('user_id')->count(),
                    'trending_places' => $placesStats->sortByDesc('metrics.visits')->values(),
                    'raw_activities' => User_activities::latest()->take(50)->get()->map(function($act) {
                        return array_merge([
                            'u_id' => $act->user_id,
                            'type' => $act->activity_type,
                        ], (array)(is_string($act->details) ? json_decode($act->details, true) : $act->details));
                    })
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
