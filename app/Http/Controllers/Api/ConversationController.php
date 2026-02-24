<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\ConversationDetailResource;
use App\Http\Resources\MessageResource;
use App\Models\Chatbot_conversations;
use App\Models\Chatbot_messages;
use App\Models\Generated_images;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ConversationController - Chatbot Conversations Management
 *
 * Scenario:
 * 1. User starts a new conversation
 * 2. Message exchange between user and bot
 * 3. Bot may generate images during conversation
 * 4. Images are stored automatically when sending message from bot with image_url
 *
 * ✅ Start a new conversation
 * ✅ Display all conversations for user
 * ✅ Display one conversation in detail (Eager Loading)
 * ✅ Add message with automatic image storage
 * ✅ Delete conversation (cascade delete)
 */
class ConversationController extends Controller
{
    /**
     * POST /api/v1/conversations
     * Start a new conversation
     */
    public function store(StoreConversationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Create conversation
            $conversation = Chatbot_conversations::startConversation(
                userId: auth('sanctum')->id(),
                context: $validated['context'] ?? null
            );

            // Load relationships
            $conversation->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Conversation started successfully',
                'data' => ConversationResource::make($conversation),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error starting conversation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/conversations
     * Display all conversations for current user
     */
    public function index(Request $request): JsonResponse
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'unauthenticated',
            ], 401);
        }

        try {
            $userId = auth('sanctum')->id();

            $query = Chatbot_conversations::with(['user'])
                ->where('user_id', $userId);

            // Filter by context
            if ($request->has('context')) {
                $query->where('context', $request->context);
            }

            // Filter conversations containing images
            if ($request->boolean('with_images')) {
                $query->withImages();
            }

            // Sort results
            $query->latest();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $conversations = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ConversationResource::collection($conversations),
                'statistics' => Chatbot_conversations::getUserStats($userId),
                'meta' => [
                    'total' => $conversations->total(),
                    'current_page' => $conversations->currentPage(),
                    'per_page' => $conversations->perPage(),
                    'last_page' => $conversations->lastPage(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching conversations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/conversations/{id}
     * Display one conversation in detail (with all messages and images)
     *
     * Eager Loading: messages, generatedImages, user
     */
    public function show($id): JsonResponse
    {
        try {
            // Eager Loading for all required relationships
            $conversation = Chatbot_conversations::with([
                'user',
                'messages' => function ($query) {
                    $query->oldest(); // Sort messages chronologically
                },
                'generatedImages.place' // Load images with related place
            ])->find($id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                    'error' => 'not_found',
                ], 404);
            }

            // Check authorization
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'unauthenticated',
                ], 401);
            }

            if (!$conversation->belongsToUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this conversation',
                    'error' => 'unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => ConversationDetailResource::make($conversation),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching conversation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/conversations/{id}/messages
     * Add a new message to the conversation
     *
     * Special Logic:
     * - If sender = 'bot' and image_url is sent
     * - A record is automatically created in generated_images table
     */
    public function storeMessage(StoreMessageRequest $request, $id): JsonResponse
    {
        $conversation = Chatbot_conversations::find($id);
        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found',
                'error' => 'not_found',
            ], 404);
        }
        try {
            $validated = $request->validated();

            // Create message
            $message = Chatbot_messages::createMessage(
                conversationId: $conversation->id,
                sender: $validated['sender'],
                message: $validated['message']
            );

            // Special Logic: If bot sends an image
            if ($validated['sender'] === 'bot' && isset($validated['image_url'])) {
                // Create image record automatically
                $generatedImage = Generated_images::createImage(
                    conversationId: $conversation->id,
                    imageUrl: $validated['image_url'],
                    placeId: $validated['place_id'] ?? null
                );

                // Return message with image information
                return response()->json([
                    'success' => true,
                    'message' => 'Message and image stored successfully',
                    'data' => [
                        'message' => MessageResource::make($message->load('conversation')),
                        'generated_image' => [
                            'id' => $generatedImage->id,
                            'image_url' => $generatedImage->image_url,
                            'full_image_url' => $generatedImage->getFullImageUrl(),
                            'place_id' => $generatedImage->place_id,
                        ],
                    ],
                ], 201);
            }

            // Regular message without image
            return response()->json([
                'success' => true,
                'message' => 'Message stored successfully',
                'data' => MessageResource::make($message->load('conversation')),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error storing message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/conversations/{id}
     * Delete the conversation
     *
     * Cascade Delete: All messages and images will be deleted automatically
     */
    public function destroy($id): JsonResponse
    {
        try {
            $conversation = Chatbot_conversations::find($id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                    'error' => 'not_found',
                ], 404);
            }

            $user = auth('sanctum')->user();

            // Check authorization
            if (!$user || !$conversation->belongsToUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this conversation',
                    'error' => 'unauthorized',
                ], 403);
            }

            // Save statistics before deletion
            $stats = [
                'messages_deleted' => $conversation->getMessagesCount(),
                'images_deleted' => $conversation->getImagesCount(),
            ];

            // Delete (cascade will automatically delete messages and images)
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully',
                'deleted_items' => $stats,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting conversation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/conversations/{id}/messages
     * Display messages from a specific conversation only
     */
    public function getMessages($id): JsonResponse
    {
        try {
            $conversation = Chatbot_conversations::find($id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                    'error' => 'not_found',
                ], 404);
            }

            $user = auth('sanctum')->user();

            if (!$user || !$conversation->belongsToUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view messages',
                    'error' => 'unauthorized',
                ], 403);
            }

            $messages = $conversation->messages()->oldest()->get();

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'data' => MessageResource::collection($messages),
                'meta' => [
                    'total' => $messages->count(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/conversations/{id}/images
     * Display images generated in a specific conversation
     */
    public function getImages($id): JsonResponse
    {
        try {
            $conversation = Chatbot_conversations::find($id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                    'error' => 'not_found',
                ], 404);
            }

            $user = auth('sanctum')->user();

            if (!$user || !$conversation->belongsToUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view images',
                    'error' => 'unauthorized',
                ], 403);
            }

            $images = $conversation->generatedImages()
                ->with('place')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'conversation_id' => $conversation->id,
                'data' => \App\Http\Resources\ImageResource::collection($images),
                'meta' => [
                    'total' => $images->count(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching images: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/conversations/statistics
     * Comprehensive statistics for user conversations
     */
    public function statistics(): JsonResponse
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'unauthenticated',
            ], 401);
        }

        try {
            $userId = auth('sanctum')->id();
            $stats = Chatbot_conversations::getUserStats($userId);

            // Add additional statistics
            $conversations = Chatbot_conversations::where('user_id', $userId)->get();

            $contextBreakdown = $conversations->groupBy('context')->map(function ($group) {
                return $group->count();
            });

            return response()->json([
                'success' => true,
                'data' => array_merge($stats, [
                    'by_context' => $contextBreakdown,
                    'average_messages_per_conversation' => $conversations->count() > 0
                        ? round($stats['total_messages'] / $conversations->count(), 2)
                        : 0,
                ]),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
