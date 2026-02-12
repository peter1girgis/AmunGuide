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
 * ConversationController - إدارة محادثات الـ Chatbot
 *
 * السيناريو:
 * 1. المستخدم يبدأ محادثة جديدة
 * 2. تبادل الرسائل بين المستخدم والبوت
 * 3. البوت قد يولد صور أثناء المحادثة
 * 4. تخزين الصور تلقائياً عند إرسال رسالة من البوت مع image_url
 *
 * ✅ بدء محادثة جديدة
 * ✅ عرض جميع المحادثات للمستخدم
 * ✅ عرض محادثة واحدة بالتفصيل (Eager Loading)
 * ✅ إضافة رسالة مع تخزين صورة تلقائياً
 * ✅ حذف محادثة (cascade delete)
 */
class ConversationController extends Controller
{
    /**
     * POST /api/v1/conversations
     * بدء محادثة جديدة
     */
    public function store(StoreConversationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // إنشاء المحادثة
            $conversation = Chatbot_conversations::startConversation(
                userId: auth('sanctum')->id(),
                context: $validated['context'] ?? null
            );

            // تحميل العلاقات
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
     * عرض جميع محادثات المستخدم الحالي
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

            // فلترة حسب السياق
            if ($request->has('context')) {
                $query->where('context', $request->context);
            }

            // فلترة المحادثات التي تحتوي على صور
            if ($request->boolean('with_images')) {
                $query->withImages();
            }

            // ترتيب النتائج
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
     * عرض محادثة واحدة بالتفصيل (مع جميع الرسائل والصور)
     *
     * Eager Loading: messages, generatedImages, user
     */
    public function show($id): JsonResponse
    {
        try {
            // Eager Loading لجميع العلاقات المطلوبة
            $conversation = Chatbot_conversations::with([
                'user',
                'messages' => function ($query) {
                    $query->oldest(); // ترتيب الرسائل زمنياً
                },
                'generatedImages.place' // تحميل الصور مع المكان المرتبط
            ])->find($id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                    'error' => 'not_found',
                ], 404);
            }

            // التحقق من الصلاحية
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
     * إضافة رسالة جديدة إلى المحادثة
     *
     * Logic الخاص:
     * - إذا كان sender = 'bot' و تم إرسال image_url
     * - يتم إنشاء سجل تلقائياً في جدول generated_images
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

            // إنشاء الرسالة
            $message = Chatbot_messages::createMessage(
                conversationId: $conversation->id,
                sender: $validated['sender'],
                message: $validated['message']
            );

            // Logic الخاص: إذا كان البوت يرسل صورة
            if ($validated['sender'] === 'bot' && isset($validated['image_url'])) {
                // إنشاء سجل الصورة تلقائياً
                $generatedImage = Generated_images::createImage(
                    conversationId: $conversation->id,
                    imageUrl: $validated['image_url'],
                    placeId: $validated['place_id'] ?? null
                );

                // إرجاع الرسالة مع معلومات الصورة
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

            // رسالة عادية بدون صورة
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
     * حذف المحادثة
     *
     * Cascade Delete: سيتم حذف جميع الرسائل والصور تلقائياً
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

            // التحقق من الصلاحية
            if (!$user || !$conversation->belongsToUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this conversation',
                    'error' => 'unauthorized',
                ], 403);
            }

            // حفظ الإحصائيات قبل الحذف
            $stats = [
                'messages_deleted' => $conversation->getMessagesCount(),
                'images_deleted' => $conversation->getImagesCount(),
            ];

            // الحذف (cascade سيحذف الرسائل والصور تلقائياً)
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
     * عرض رسائل محادثة معينة فقط
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
     * عرض الصور المولدة في محادثة معينة
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
     * إحصائيات شاملة لمحادثات المستخدم
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

            // إضافة إحصائيات إضافية
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
