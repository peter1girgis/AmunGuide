<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
// use App\Http\Requests\Place\StorePlaceRequest;
use App\Models\Places;
use App\Models\User_activities;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * PlaceController - Optional Authentication
 *
 * ✅ جميع الـ GET endpoints متاحة للـ guest
 * ✅ لكن لو user مسجل → تسجيل النشاط
 */
class PlaceController extends Controller
{
    /**
     * GET /api/v1/places
     *
     * ✅ متاح للـ guest والـ authenticated users
     * ✅ لو authenticated → track activity
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $places = Places::query()
                ->when(
                    $request->get('sort') === 'trending',
                    fn($q) => $q->orderByDesc('created_at')
                )
                ->when(
                    $request->get('sort') === 'rating',
                    fn($q) => $q->orderByDesc('rating')
                )
                ->when(
                    !$request->get('sort'),
                    fn($q) => $q->latest('created_at')
                )
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => PlaceResource::collection($places),
                'pagination' => [
                    'total' => $places->total(),
                    'per_page' => $places->perPage(),
                    'current_page' => $places->currentPage(),
                    'last_page' => $places->lastPage(),
                    'has_more' => $places->hasMorePages(),
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch places', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch places.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/places/{id}
     *
     * ✅ متاح للـ guest والـ authenticated users
     * ✅ لو authenticated → track visit activity
     */
    public function show(Places $place, Request $request): JsonResponse
    {
        try {
            // ✅ Track user activity ONLY إذا كان مسجل دخول
            if (auth('sanctum')->check()) {
                User_activities::create([
                    'user_id' => auth('sanctum')->id(),
                    'activity_type' => 'visit',
                    'place_id' => $place->id,
                    'details' => ([
                        'place_title' => $place->title,
                        'place_price' => $place->ticket_price,
                        'ip_address' => $request->ip(),
                    ]),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => new PlaceResource($place),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch place details', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch place details.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/places/search
     *
     * ✅ متاح للـ guest والـ authenticated users
     * ✅ لو authenticated → track search activity
     *
     * Query Parameters:
     * - q: search query (min 3 chars)
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');

            if (strlen($query) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query must be at least 3 characters.',
                ], 400);
            }

            // 1. 💡 تركة: تنفيذ الـ Query أولاً قبل تسجيل النشاط
            // لكي نعرف ما هي "الكلمة الكاملة" التي وجدها النظام
            $places = Places::query()
                ->where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->latest('created_at')
                ->paginate($request->get('per_page', 15));

            // 2. 💡 تركة: التحقق من وجود نتائج + تسجيل النشاط الذكي
            // لو المستخدم كتب "Pyra" وظهرت نتائج، أول نتيجة غالباً هي الأقرب (مثل Pyramids)
            if ($places->isNotEmpty() && auth('sanctum')->check()) {

                // نأخذ عنوان أول نتيجة كـ "كلمة مستهدفة كاملة"
                $fullMatchedTerm = $places->first()->title;

                User_activities::create([
                    'user_id' => auth('sanctum')->id(),
                    'activity_type' => 'search',

                    // 💡 تركة: خزن الكلمة الكاملة في الحقل الأساسي للتحليل السريع
                    'search_query' => $fullMatchedTerm,

                    'details' => [
                        // خزن ما كتبه المستخدم فعلياً للمقارنة مستقبلاً
                        'user_typed_this' => $query,
                        'actual_match' => $fullMatchedTerm,
                        'results_count' => $places->total(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => PlaceResource::collection($places),
                'pagination' => [
                    'total' => $places->total(),
                    'per_page' => $places->perPage(),
                    'current_page' => $places->currentPage(),
                    'last_page' => $places->lastPage(), // 💡 تركة: أضف الـ last_page لتسهيل عمل الـ Frontend
                    'has_more' => $places->hasMorePages(),
                ]
            ]);

        } catch (\Throwable $e) {
            // 💡 تركة: دائماً سجل الخطأ مع الـ Stack Trace في الـ Log للمطورين
            \Log::error('Search failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong on our side.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/places/trending
     *
     * ✅ متاح للـ guest والـ authenticated users
     */
    public function trending(): JsonResponse
    {
        try {
            $places = Places::query()
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => PlaceResource::collection($places),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch trending places', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trending places.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/places/filter
     *
     * ✅ متاح للـ guest والـ authenticated users
     * ✅ لو authenticated → track filter activity
     *
     * Query Parameters:
     * - min_price: 0
     * - max_price: 1000
     * - sort: price, rating, newest
     */
    public function filter(Request $request): JsonResponse
    {
        try {
            // ✅ جمع معايير الـ Filter
            $filterCriteria = [
                'min_price' => $request->get('min_price'),
                'max_price' => $request->get('max_price'),
                'sort' => $request->get('sort'),
            ];

            // ✅ Track filter activity ONLY إذا كان مسجل دخول
            if (auth('sanctum')->check()) {
                User_activities::create([
                    'user_id' => auth('sanctum')->id(),
                    'activity_type' => 'search', // نستخدم search للفلتر أيضاً
                    'details' => ([
                        'filter_type' => 'places_filter',
                        'criteria' => array_filter($filterCriteria),
                        'ip_address' => $request->ip(),
                    ]),
                ]);
            }

            // ✅ Build query with filters
            $places = Places::query()
                ->when(
                    $request->get('min_price'),
                    fn($q) => $q->where('ticket_price', '>=', $request->get('min_price'))
                )
                ->when(
                    $request->get('max_price'),
                    fn($q) => $q->where('ticket_price', '<=', $request->get('max_price'))
                )
                ->when(
                    $request->get('sort') === 'price',
                    fn($q) => $q->orderBy('ticket_price')
                )
                ->when(
                    $request->get('sort') === 'rating',
                    fn($q) => $q->orderByDesc('rating')
                )
                ->when(
                    $request->get('sort') === 'newest' || !$request->get('sort'),
                    fn($q) => $q->latest('created_at')
                )
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => PlaceResource::collection($places),
                'pagination' => [
                    'total' => $places->total(),
                    'per_page' => $places->perPage(),
                    'current_page' => $places->currentPage(),
                    'has_more' => $places->hasMorePages(),
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Filter failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Filter failed.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/places
     *
     * ✅ Admin only (محمي بـ middleware)
     */
    public function store(Request $request): JsonResponse
    {
        if(!auth('sanctum')->user() || auth('sanctum')->user()->role !== 'admin'){
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized | Only Admin can access .',
            ], 403);
        }
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255|unique:places,title',
                'description' => 'required|string|min:10|max:5000',
                'ticket_price' => 'required|numeric|min:0|max:10000',
                'rating' => 'nullable|numeric|min:0|max:5',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('places', 'public');
                $validated['image'] = $path;
            }

            $place = Places::create($validated);

            \Log::info('New place created', [
                'place_id' => $place->id,
                'user_id' => auth('sanctum')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Place created successfully.',
                'data' => new PlaceResource($place),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Failed to create place', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create place.',
            ], 500);
        }
    }

    /**
     * PUT /api/v1/places/{id}
     *
     * ✅ Admin only (محمي بـ middleware)
     */
    public function update(Request $request, Places $place): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255|unique:places,title,' . $place->id,
                'description' => 'sometimes|string|min:10|max:5000',
                'ticket_price' => 'sometimes|numeric|min:0|max:10000',
                'rating' => 'nullable|numeric|min:0|max:5',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if (isset($validated['title'])) {
                $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);
            }

            if ($request->hasFile('image')) {
                if ($place->image) {
                    \Storage::disk('public')->delete($place->image);
                }
                $path = $request->file('image')->store('places', 'public');
                $validated['image'] = $path;
            }

            $place->update($validated);

            \Log::info('Place updated', ['place_id' => $place->id]);

            return response()->json([
                'success' => true,
                'message' => 'Place updated successfully.',
                'data' => new PlaceResource($place),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Failed to update place', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update place.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/places/{id}
     *
     * ✅ Admin only (محمي بـ middleware)
     */
    public function destroy(Places $place): JsonResponse
    {
        try {
            if ($place->image) {
                \Storage::disk('public')->delete($place->image);
            }

            $place->delete();

            \Log::info('Place deleted', ['place_id' => $place->id]);

            return response()->json([
                'success' => true,
                'message' => 'Place deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to delete place', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete place.',
            ], 500);
        }
    }
}
