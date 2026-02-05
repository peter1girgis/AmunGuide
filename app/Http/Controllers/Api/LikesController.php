<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LikeResource;
use App\Models\Likes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
/**
 * LikesController - إدارة التقييمات (Like)
 *
 * ✅ إضافة تقييم على Tour, Place, أو Plan
 * ✅ إزالة التقييم
 * ✅ عرض التقييمات
 * ✅ عد التقييمات
 */
class LikesController extends Controller
{
    /**
     * POST /api/v1/likes
     * إضافة تقييم (Like)
     *
     * الـ Request يجب أن يحتوي على:
     * {
     *   "likeable_type": "tours|places|plans",
     *   "likeable_id": 1
     * }
     */
    public function store(Request $request): JsonResponse
    {
        // Check authentication
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Login required',
                'error' => 'unauthenticated',
            ], 401);
        }

        // Validate input
        $validated = $request->validate([
            'likeable_type' => 'required|string|in:tours,places,plans',
            'likeable_id' => 'required|integer',
        ], [
            'likeable_type.required' => 'Supplier type required',
            'likeable_type.in' => 'Incorrect resource type',
            'likeable_id.required' => 'Supplier ID required',
            'likeable_id.integer' => 'The supplier ID must be a number',
        ]);

        // Check if resource exists
        $modelMap = [
            'tours' => 'App\\Models\\Tours',
            'places' => 'App\\Models\\Places',
            'plans' => 'App\\Models\\Plans',
        ];

        $model = $modelMap[$validated['likeable_type']];
        if (!$model::find($validated['likeable_id'])) {
            return response()->json([
                'success' => false,
                'message' => "Supplier ({$validated['likeable_type']}) Not Found",
                'error' => 'not_found',
            ], 404);
        }

        // Check if user already liked this
        $existingLike = Likes::where('user_id', auth('sanctum')->id())
                            ->where('likeable_type', $validated['likeable_type'])
                            ->where('likeable_id', $validated['likeable_id'])
                            ->first();

        if ($existingLike) {
            return response()->json([
                'success' => false,
                'message' => 'This resource has been previously assessed.',
                'error' => 'already_liked',
                'data' => LikeResource::make($existingLike),
            ], 400);
        }

        // Create like
        $like = Likes::create([
            'user_id' => auth('sanctum')->id(),
            'likeable_type' => $validated['likeable_type'],
            'likeable_id' => $validated['likeable_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'The assessment was successful.',
            'data' => LikeResource::make($like->load('user')),
        ], 201);
    }

    /**
     * DELETE /api/v1/likes/{id}
     * إزالة التقييم
     *
     * فقط الـ owner يمكن أن يزيل تقييمه
     */
    public function destroy($id): JsonResponse
    {
        $like = Likes::find($id);
        if (empty($like)) {
            return response()->json([
                'success' => false,
                'message' => 'This rating does not exist.',
                'error' => 'not_found',
            ], 401);
        }
        // Check authorization
        if (auth('sanctum')->id() !== $like->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Removing this review is not permitted.',
                'error' => 'unauthorized',
            ], 403);
        }


        $like->delete();

        return response()->json([
            'success' => true,
            'message' => 'The rating was successfully removed.',
        ]);
    }

    /**
     * DELETE /api/v1/likes/{likeable_type}/{likeable_id}
     * إزالة التقييم من مورد معين
     *
     * طريقة بديلة للحذف (أسهل للـ frontend)
     */
    public function removeFromResource(
        string $likeableType,
        int $likeableId
    ): JsonResponse
    {
        // Check authentication
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Login required',
                'error' => 'unauthenticated',
            ], 401);
        }

        // Find like
        $like = Likes::where('user_id', auth('sanctum')->id())
                    ->where('likeable_type', $likeableType)
                    ->where('likeable_id', $likeableId)
                    ->first();

        if (!$like) {
            return response()->json([
                'success' => false,
                'message' => 'This resource has not yet been evaluated.',
                'error' => 'not_liked',
            ], 404);
        }

        $like->delete();

        return response()->json([
            'success' => true,
            'message' => 'The rating was successfully removed.',
        ]);
    }

    /**
     * GET /api/v1/{likeable_type}/{likeable_id}/likes
     * الحصول على جميع التقييمات لـ مورد معين
     */
    public function index(string $likeableType, int $likeableId): JsonResponse
    {
        // Validate type
        $validTypes = ['tours', 'places', 'plans'];
        if (!in_array($likeableType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect resource type',
                'error' => 'invalid_type',
            ], 400);
        }

        // Get all likes for this resource
        $likes = Likes::where('likeable_type', $likeableType)
                     ->where('likeable_id', $likeableId)
                     ->with('user')
                     ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => LikeResource::collection($likes),
            'meta' => [
                'total' => $likes->total(),
                'type' => $likeableType,
                'id' => $likeableId,
            ]
        ]);
    }

    /**
     * GET /api/v1/{likeable_type}/{likeable_id}/likes/count
     * الحصول على عدد التقييمات
     */
    public function count(string $likeableType, int $likeableId): JsonResponse
    {
        // Validate type
        $validTypes = ['tours', 'places', 'plans'];
        if (!in_array($likeableType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect resource type',
                'error' => 'invalid_type',
            ], 400);
        }

        $count = Likes::where('likeable_type', $likeableType)
                     ->where('likeable_id', $likeableId)
                     ->count();

        // Check if current user liked it
        $userLiked = false;
        if (auth('sanctum')->check()) {
            $userLiked = Likes::where('user_id', auth('sanctum')->id())
                             ->where('likeable_type', $likeableType)
                             ->where('likeable_id', $likeableId)
                             ->exists();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $likeableType,
                'id' => $likeableId,
                'total_likes' => $count,
                'user_liked' => $userLiked,
            ]
        ]);
    }

    /**
     * GET /api/v1/user/{userId}/likes
     * الحصول على جميع التقييمات لـ مستخدم معين
     */
    public function userLikes(int $userId): JsonResponse
    {
        if(!auth('sanctum')->check() && (auth('sanctum')->id() !== $userId || auth('sanctum')->user()->role !== 'admin' ) ){
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to user likes.',
                'error' => 'unauthorized',
            ], 403);
        }
        $likes = Likes::where('user_id', $userId)
                     ->with('user')
                     ->latest()
                     ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => LikeResource::collection($likes),
            'meta' => [
                'total' => $likes->total(),
                'user_id' => $userId,
            ]
        ]);
    }

    /**
     * POST /api/v1/likes/toggle
     * تقييم أو إزالة التقييم (Toggle)
     *
     * أسهل endpoint للـ frontend
     * إذا كان user لم يقيّم → يضيف تقييم
     * إذا كان user قيّم → يزيل التقييم
     */
    public function toggle(Request $request): JsonResponse
    {
        // Check authentication
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Login required',
                'error' => 'unauthenticated',
            ], 401);
        }

        // Validate input
        $validated = $request->validate([
            'likeable_type' => 'required|string|in:tours,places,plans',
            'likeable_id' => 'required|integer',
        ]);

        // Check if user already liked this
        $existingLike = Likes::where('user_id', auth('sanctum')->id())
                            ->where('likeable_type', $validated['likeable_type'])
                            ->where('likeable_id', $validated['likeable_id'])
                            ->first();

        if ($existingLike) {
            // Remove like (Unlike)
            $existingLike->delete();

            return response()->json([
                'success' => true,
                'message' => 'The rating was successfully removed.',
                'action' => 'unliked',
                'data' => [
                    'type' => $validated['likeable_type'],
                    'id' => $validated['likeable_id'],
                ]
            ]);
        } else {
            // Check if resource exists
            $modelMap = [
                'tours' => 'App\\Models\\Tours',
                'places' => 'App\\Models\\Places',
                'plans' => 'App\\Models\\Plans',
            ];

            $model = $modelMap[$validated['likeable_type']];
            if (!$model::find($validated['likeable_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Supplier ({$validated['likeable_type']}) Not Found",
                    'error' => 'not_found',
                ], 404);
            }

            // Add like
            $like = Likes::create([
                'user_id' => auth('sanctum')->id(),
                'likeable_type' => $validated['likeable_type'],
                'likeable_id' => $validated['likeable_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'The assessment was successful.',
                'action' => 'liked',
                'data' => LikeResource::make($like->load('user')),
            ], 201);
        }
    }
}
