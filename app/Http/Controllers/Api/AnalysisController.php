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
     * 1. تحليل مستخدم واحد (User-Centric)
     * مخصص للـ Chatbot وبناء الخطط الشخصية
     */
    public function getMyData(): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // جلب الأنشطة مع Place وتصفية البيانات غير الضرورية
            $activities = User_activities::where('user_id', $user->id)
                ->with('place:id,title') // نأخذ فقط الـ ID والعنوان لتوفير مساحة الـ JSON
                ->latest()
                ->get();

            $formattedActivities = $activities->map(function ($act) {
                // 1. فك تشفير الـ details (سواء كانت مصفوفة أو نص JSON أو فارغة)
                $extraDetails = is_string($act->details) ? json_decode($act->details, true) : ($act->details ?? []);

                // 2. دمج البيانات الأساسية مع الـ details الديناميكية
                return array_merge([
                    'type' => $act->activity_type,
                    'place_name' => $act->place->title ?? null,
                    'search_query' => $act->search_query ?? null,
                    'timestamp' => $act->created_at->toDateTimeString(),
                ], (array)$extraDetails); // أي بيانات في details ستصبح حقول أساسية هنا
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
     * 2. تحليل شامل لكل المستخدمين (Global Trends)
     * مخصص لـ Script التحليل العام والتريندات
     */
    public function getAllUsersData(): JsonResponse
    {
        if(auth('sanctum')->user()?->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        try {
            // تجميع الأنشطة حسب الأماكن مباشرة لمعرفة التريند
            $placesStats = User_activities::with('place')
                ->whereNotNull('place_id')
                ->get()
                ->groupBy('place_id')
                ->map(function ($group) {
                    $place = $group->first()->place;

                    // تجميع الـ details المبعثرة في الأنشطة الخاصة بهذا المكان
                    $allDetails = $group->pluck('details')->filter()->map(fn($d) => is_string($d) ? json_decode($d, true) : $d);

                    return [
                        'place_id' => $place->id ?? null,
                        'title' => $place->title ?? 'Unknown',
                        'metrics' => [
                            'visits' => $group->where('activity_type', 'visit')->count(),
                            'likes' => $group->where('activity_type', 'like')->count(),
                            'comments' => $group->where('activity_type', 'comment')->count(),
                        ],
                        // استخراج تفاصيل ديناميكية "متكررة" لو موجودة (مثال: المدة المستغرقة)
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
