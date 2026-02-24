<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User_activities;
use Illuminate\Support\Str;

/**
 * CommentsController - Comments Management
 *
 * ✅ Add comment on Tour, Place, or Plan
 * ✅ Display comments
 * ✅ Update comment
 * ✅ Delete comment
 */
class CommentsController extends Controller
{
    /**
     * GET /api/v1/comments/{commentable_type}/{commentable_id}
     * Get all comments for a specific resource
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
                'message' => 'Resource type is invalid',
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
     * Get a single comment
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
     * Add a new comment
     *
     * The Request must contain:
     * {
     *   "content": "Comment content",
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

        $isPlace = ($validated['commentable_type'] === 'places' || $validated['commentable_type'] === 'App\\Models\\Places');
        $placeId = $isPlace ? $validated['commentable_id'] : null;
        $userId = auth('sanctum')->id();
        User_activities::create([
            'user_id'       => $userId,
            'activity_type' => 'comment', // Fully compatible with the Migration
            'place_id'      => $placeId,
            'details'       => [
                'action'           => 'added_comment',
                'comment_id'       => $comment->id,
                'commentable_type' => $validated['commentable_type'],
                'commentable_id'   => $validated['commentable_id'],
                'comment_preview'  => Str::limit($comment->content, 50), // Store comment beginning for memory
                'ip_address'       => $request->ip(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => CommentResource::make($comment->load('user')),
        ], 201); // 201 Created
    }

    /**
     * PUT /api/v1/comments/{id}
     * Update a comment
     *
     * Only owner or admin can update
     */
    public function update(UpdateCommentRequest $request, $id): JsonResponse
    {
        // Note that we no longer need Comments::find($id) because Laravel already did it

        $comment = Comments::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment NOT found',
            ], 404);
        }

        $oldContent = $comment->content;
        $validated = $request->validated();
        $comment->update($validated);

        // Get the current user (confirmed to exist in the Request)
        $user = auth('sanctum')->user();

        // Determine if the comment is for a Place
        $isPlace = ($comment->commentable_type === 'places' || $comment->commentable_type === 'App\Models\Places');
        $placeId = $isPlace ? $comment->commentable_id : null;

        // Log the activity
        User_activities::create([
            'user_id'       => $user->id,
            'activity_type' => 'comment',
            'place_id'      => $placeId,
            'details'       => [
                'action'         => 'updated_comment',
                'comment_id'     => $comment->id,
                'resource_type'  => $comment->commentable_type,
                'resource_id'    => $comment->commentable_id,
                'old_preview'    => $oldContent,
                'new_preview'    => $comment->content,
                'ip_address'     => $request->ip(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data'    => CommentResource::make($comment->load('user')),
        ]);
    }

    /**
     * DELETE /api/v1/comments/{id}
     * Delete a comment
     *
     * Only owner or admin can delete
     */
    public function destroy($id): JsonResponse
    {
        $comment = Comments::find($id);
        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment NOT found',
                'error' => 'not_found',
            ], 404);
        }
        // Check authorization
        if (
            auth('sanctum')->id() !== $comment?->user_id &&
            auth('sanctum')->user()->role !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this comment.',
                'error' => 'unauthorized',
            ], 403);
        }

        User_activities::create([
            'user_id'       => auth('sanctum')->id(),
            'activity_type' => 'comment',
            'place_id'      => $comment->commentable_type === 'places' ? $comment->commentable_id : null,
            'details'       => [
                'action'         => 'deleted_comment',
                'comment_id'     => $comment->id,
                'resource_type'  => $comment->commentable_type,
                'resource_id'    => $comment->commentable_id,
                'comment_preview' => $comment->content,
                'ip_address'     => \request()->ip(),
            ],
        ]);

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'The comment was successfully deleted.',
        ]);
    }

    /**
     * GET /api/v1/comments/user/{userId}
     * Get all comments for a specific user
     */
    public function userComments(int $userId): JsonResponse
    {
        try {
            if (auth('sanctum')->id() !== $userId && auth('sanctum')->user()->role !== 'admin') {
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
     * Get the count of comments for a specific resource
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
     * Add a comment directly on a topic
     *
     * Alternative to /api/v1/comments (easier for frontend)
     */
    public function storeOnResource(
        string $commentableType,
        int $commentableId,
        Request $request
    ): JsonResponse {
        // Check authentication
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Login is required',
                'error' => 'unauthenticated',
            ], 401);
        }

        // Validate input
        $validated = $request->validate([
            'content' => 'required|string|min:3|max:1000',
        ], [
            'content.required' => 'Comment content is required',
            'content.min' => 'Comment content must be at least 3 characters',
            'content.max' => 'Comment content must not exceed 1000 characters',
        ]);

        // Validate type
        $validTypes = ['tours', 'places', 'plans'];
        if (!in_array($commentableType, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Resource type is invalid',
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
                'message' => "The resource ($commentableType) does not exist",
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
            'message' => 'Comment added successfully',
            'data' => CommentResource::make($comment->load('user')),
        ], 201);
    }
}
