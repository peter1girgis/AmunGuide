<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
/**
 * CommentsController - إدارة التعليقات
 *
 * ✅ إضافة تعليق على Tour, Place, أو Plan
 * ✅ عرض التعليقات
 * ✅ تحديث التعليق
 * ✅ حذف التعليق
 */
class CommentsController extends Controller
{
    /**
     * GET /api/v1/comments/{commentable_type}/{commentable_id}
     * الحصول على جميع التعليقات لـ مورد معين
     *
     * @param string $commentableType (tours, places, plans)
     * @param int $commentableId
     */
    public function index(string $commentableType, int $commentableId): JsonResponse
    {
        // Validate type
        $validTypes = ['tours', 'places', 'plans'];
        if (!in_array($commentableType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'نوع المورد غير صحيح',
                'error' => 'invalid_type',
            ], 400);
        }

        // Get all comments for this resource
        $comments = Comments::where('commentable_type', $commentableType)
                           ->where('commentable_id', $commentableId)
                           ->with('user')
                           ->latest()
                           ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'total' => $comments->total(),
                'page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total_pages' => $comments->lastPage(),
                'type' => $commentableType,
                'resource_id' => $commentableId,
            ]
        ]);
    }

    /**
     * GET /api/v1/comments/{id}
     * الحصول على تعليق واحد
     */
    public function show($id): JsonResponse
    {
        $comment = Comments::find($id);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment NOT found',
                'error' => 'not_found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => CommentResource::make($comment->load('user')),
        ]);
    }

    /**
     * POST /api/v1/comments
     * إضافة تعليق جديد
     *
     * الـ Request يجب أن يحتوي على:
     * {
     *   "content": "محتوى التعليق",
     *   "commentable_type": "tours|places|plans",
     *   "commentable_id": 1
     * }
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        // Request validates & authorizes automatically
        $validated = $request->validated();

        // Add user_id from authenticated user
        $validated['user_id'] = auth('sanctum')->id();

        // Create comment
        $comment = Comments::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التعليق بنجاح',
            'data' => CommentResource::make($comment->load('user')),
        ], 201); // 201 Created
    }

    /**
     * PUT /api/v1/comments/{id}
     * تحديث تعليق
     *
     * يمكن فقط للـ owner أو admin التحديث
     */
    public function update(UpdateCommentRequest $request, Comments $comment): JsonResponse
    {
        // Request validates & authorizes automatically
        $validated = $request->validated();

        // Update comment
        $comment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التعليق بنجاح',
            'data' => CommentResource::make($comment->load('user')),
        ]);
    }

    /**
     * DELETE /api/v1/comments/{id}
     * حذف تعليق
     *
     * يمكن فقط للـ owner أو admin الحذف
     */
    public function destroy($id): JsonResponse
    {
        $comment = Comments::find($id);
        // Check authorization
        if (auth('sanctum')->id() !== $comment?->user_id &&
            auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this comment.',
                'error' => 'unauthorized',
            ], 403);
        }
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment NOT found',
                'error' => 'not_found',
            ], 404);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'The comment was successfully deleted.',
        ]);
    }

    /**
     * GET /api/v1/comments/user/{userId}
     * الحصول على جميع تعليقات مستخدم معين
     */
    public function userComments(int $userId): JsonResponse
    {
        try {
            if(  auth('sanctum')->id() !== $userId && auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Viewing this user`s comments is not permitted',
                'error' => 'unauthorized',
            ], 403);
        }
        $comments = Comments::where('user_id', $userId)
                           ->with('user')
                           ->latest()
                           ->paginate(15);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user comments: ' . $e->getMessage(),
            ], 500);
        }


        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'total' => $comments->total(),
                'user_id' => $userId,
            ]
        ]);
    }

    /**
     * GET /api/v1/{commentable_type}/{id}/comments/count
     * الحصول على عدد التعليقات لـ مورد معين
     */
    public function count(string $commentableType, int $commentableId): JsonResponse
    {
        $count = Comments::where('commentable_type', $commentableType)
                        ->where('commentable_id', $commentableId)
                        ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $commentableType,
                'id' => $commentableId,
                'count' => $count,
            ]
        ]);
    }

    /**
     * POST /api/v1/{commentable_type}/{id}/comments
     * إضافة تعليق مباشرة على موضوع
     *
     * بديل لـ /api/v1/comments (أسهل للـ frontend)
     */
    public function storeOnResource(
        string $commentableType,
        int $commentableId,
        Request $request
    ): JsonResponse
    {
        // Check authentication
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'مطلوب تسجيل الدخول',
                'error' => 'unauthenticated',
            ], 401);
        }

        // Validate input
        $validated = $request->validate([
            'content' => 'required|string|min:3|max:1000',
        ], [
            'content.required' => 'محتوى التعليق مطلوب',
            'content.min' => 'محتوى التعليق يجب أن يكون 3 أحرف على الأقل',
            'content.max' => 'محتوى التعليق لا يجب أن يتجاوز 1000 حرف',
        ]);

        // Validate type
        $validTypes = ['tours', 'places', 'plans'];
        if (!in_array($commentableType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'نوع المورد غير صحيح',
                'error' => 'invalid_type',
            ], 400);
        }

        // Check if resource exists
        $modelMap = [
            'tours' => 'App\\Models\\Tours',
            'places' => 'App\\Models\\Places',
            'plans' => 'App\\Models\\Plans',
        ];

        $model = $modelMap[$commentableType];
        if (!$model::find($commentableId)) {
            return response()->json([
                'success' => false,
                'message' => "المورد ($commentableType) غير موجود",
                'error' => 'not_found',
            ], 404);
        }

        // Create comment
        $comment = Comments::create([
            'user_id' => auth('sanctum')->id(),
            'commentable_type' => $commentableType,
            'commentable_id' => $commentableId,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة التعليق بنجاح',
            'data' => CommentResource::make($comment->load('user')),
        ], 201);
    }
}
