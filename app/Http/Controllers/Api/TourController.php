<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourResource;
use App\Http\Requests\StoreTourRequest;
use App\Http\Requests\UpdateTourRequest;
use App\Http\Requests\FilterTourRequest;
use App\Models\Tour;
use App\Models\Tours;
use App\Models\User_activities;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * TourController - Final Professional Edition
 *
 * ✅ استخدام كل الـ Scopes من Model
 * ✅ استخدام الـ Requests للـ validation
 * ✅ استخدام الـ Resources للـ formatting
 * ✅ Activity tracking محترف
 */
class TourController extends Controller
{
    /**
     * GET /api/v1/tours
     *
     * قائمة الجولات النشطة مع تصفية و ترتيب
     * ✅ استخدام: active(), forGuide(), priceBetween()
     */
    public function index(FilterTourRequest $request): JsonResponse
    {
        try {
            // ✅ ابدأ بـ الجولات النشطة فقط
            $query = Tours::query()->active();

            // ✅ استخدم الـ scopes للـ filtering
            if ($request->has('guide_id')) {
                $query->forGuide($request->get('guide_id'));
            }

            if ($request->has('min_price') && $request->has('max_price')) {
                $query->priceBetween(
                    $request->get('min_price'),
                    $request->get('max_price')
                );
            }

            // ✅ الترتيب
            $sort = $request->get('sort', 'newest');
            match ($sort) {
                'price_asc' => $query->orderBy('price'),
                'price_desc' => $query->orderByDesc('price'),
                'popular' => $query->withCount('bookings')->orderByDesc('bookings_count'),
                default => $query->latest('created_at'),
            };

            $tours = $query->with(['guide:id,name,phone', 'places:places.id,places.title'])
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'pagination' => [
                    'total' => $tours->total(),
                    'per_page' => $tours->perPage(),
                    'current_page' => $tours->currentPage(),
                    'has_more' => $tours->hasMorePages(),
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch tours', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tours.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/{id}
     *
     * تفاصيل جولة معينة
     * ✅ Track user visit
     */
    public function show(Tours $tour): JsonResponse
    {
        try {
            // ✅ Track activity
            if (auth('sanctum')->check()) {
                User_activities::create([
                    'user_id' => auth('sanctum')->id(),
                    'activity_type' => 'visit',
                    'place_id' => null,
                    'details' => json_encode([
                        'tour_id' => $tour->id,
                        'tour_title' => $tour->title,
                        'price' => $tour->price,
                    ]),
                ]);
            }

            $tour->load(['guide:id,name,phone,email', 'places:places.id,places.title,places.description']);

            return response()->json([
                'success' => true,
                'data' => new TourResource($tour),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch tour details', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tour details.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/search
     *
     * البحث عن جولات
     * ✅ استخدام: active(), search()
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



            // ✅ استخدم الـ scopes
            $tours = Tours::query()
                ->active()
                ->Search($query)
                ->with(['guide:id,name,phone'])
                ->latest('created_at')
                ->paginate($request->get('per_page', 15));
                // ✅ Track search activity
                if (auth('sanctum')->check()) {
                    $fullMatchedTerm = $tours->first()->title;

                    User_activities::create([
                        'user_id' => auth('sanctum')->id(),
                        'activity_type' => 'search',

                        // 💡 تركة: خزن الكلمة الكاملة في الحقل الأساسي للتحليل السريع
                        'search_query' => $fullMatchedTerm,

                        'details' => [
                            // خزن ما كتبه المستخدم فعلياً للمقارنة مستقبلاً
                            'user_typed_this' => $query,
                            'actual_match' => $fullMatchedTerm,
                            'results_count' => $tours->total(),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ],
                    ]);
                }

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'pagination' => [
                    'total' => $tours->total(),
                    'per_page' => $tours->perPage(),
                    'current_page' => $tours->currentPage(),
                    'has_more' => $tours->hasMorePages(),
                ]
            ]);


        } catch (\Throwable $e) {
            \Log::error('Search tours failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/filter
     *
     * فلترة الجولات مع معايير متقدمة
     * ✅ استخدام: active(), priceBetween(), forGuide(), startingFrom()
     */
    public function filter(FilterTourRequest $request): JsonResponse
    {
        try {
            // ✅ Track filter activity
            if (auth('sanctum')->check()) {
                User_activities::create([
                    'user_id' => auth('sanctum')->id(),
                    'activity_type' => 'search',
                    'details' => json_encode([
                        'filter_type' => 'tours_filter',
                        'criteria' => $request->all(),
                    ]),
                ]);
            }

            // ✅ Build query with scopes
            $query = Tours::query()->active();

            if ($request->has('min_price') && $request->has('max_price')) {
                $query->priceBetween(
                    $request->get('min_price'),
                    $request->get('max_price')
                );
            }

            if ($request->has('guide_id')) {
                $query->forGuide($request->get('guide_id'));
            }

            if ($request->has('start_date')) {
                $query->startingFrom($request->get('start_date'));
            }

            $tours = $query->with(['guide:id,name,phone'])
                ->latest('created_at')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'pagination' => [
                    'total' => $tours->total(),
                    'per_page' => $tours->perPage(),
                    'current_page' => $tours->currentPage(),
                    'has_more' => $tours->hasMorePages(),
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Filter tours failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Filter failed.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/popular
     *
     * أشهر الجولات
     * ✅ استخدام: active(), popular()
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);

            // ✅ استخدام الـ scope
            $tours = Tours::query()
                ->active()
                ->popular($limit)
                ->with(['guide:id,name,phone'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch popular tours', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular tours.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/guide/{guide_id}
     *
     * جميع جولات دليل معين
     * ✅ استخدام: active(), forGuide()
     */
    public function getGuideToursPublic($guide_id): JsonResponse
    {
        try {
            // ✅ استخدام الـ scopes
            $tours = Tours::query()
                ->active()
                ->forGuide($guide_id)
                ->with(['guide:id,name,phone,email', 'places:places.id,places.title'])
                ->latest('created_at')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'pagination' => ['total' => $tours->total()]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch guide tours', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch guide tours.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/tours
     *
     * إنشاء جولة جديدة (Guide فقط)
     * ✅ استخدام StoreTourRequest للـ validation
     */
    public function store(StoreTourRequest $request): JsonResponse
    {

        try {
            // ✅ البيانات validated بالفعل من الـ Request
            $validated = $request->validated();
            $validated['guide_id'] = auth('sanctum')->id();

            $tour = Tours::create($validated);

            // ✅ Attach places
            // if (!empty($validated['places'])) {
            //     $sequence = 1;
            //     foreach ($validated['places'] as $place_id) {
            //         $tour->places()->attach($place_id, ['sequence' => $sequence++]);
            //     }
            // }
            // ✅ Attach places بشكل احترافي وسريع
            if (!empty($validated['places'])) {
                $placesWithPivot = [];
                foreach ($validated['places'] as $index => $placeId) {
                    // نجهز مصفوفة تحتوي على الـ ID والبيانات الإضافية (pivot data)
                    $placesWithPivot[$placeId] = ['sequence' => $index + 1];
                }

                // تنفيذ عملية الإدخال في قاعدة البيانات بـ Query واحد فقط
                $tour->places()->attach($placesWithPivot);
            }
            \Log::info('New tour created', ['tour_id' => $tour->id, 'guide_id' => auth('sanctum')->id()]);

            $tour->load(['guide:id,name,phone', 'places:places.id,places.title']);

            return response()->json([
                'success' => true,
                'message' => 'Tour created successfully.',
                // 'data' => new TourResource($tour),
                'data' => new TourResource($tour->load(['places', 'bookings'])),
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Failed to create tour', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tour.',
            ], 500);
        }
    }

    /**
     * PUT /api/v1/tours/{id}
     *
     * تحديث جولة (Guide owner فقط)
     * ✅ استخدام UpdateTourRequest للـ validation و authorization
     */
    public function update(UpdateTourRequest $request, Tours $tour): JsonResponse
    {
        try {
            // ✅ البيانات validated و authorized
            $validated = $request->validated();

            $tour->update($validated);

            // ✅ Update places
            if (isset($validated['places'])) {
                $tour->places()->detach();
                $sequence = 1;
                foreach ($validated['places'] as $place_id) {
                    $tour->places()->attach($place_id, ['sequence' => $sequence++]);
                }
            }

            \Log::info('Tour updated', ['tour_id' => $tour->id]);

            $tour->load(['guide:id,name,phone', 'places:places.id,places.title']);

            return response()->json([
                'success' => true,
                'message' => 'Tour updated successfully.',
                'data' => new TourResource($tour),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to update tour', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tour.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/tours/{id}
     *
     * حذف جولة (Guide owner فقط)
     */
    public function destroy(Tours $tour): JsonResponse
    {
        try {
            // ✅ Authorization check
            if (auth('sanctum')->id() !== $tour?->guide_id && auth('sanctum')->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }
            if (empty($tour)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour not found.',
                ], 404);
            }

            $tour->places()->detach();
            $tour->delete();

            \Log::info('Tour deleted', ['tour_id' => $tour->id]);

            return response()->json([
                'success' => true,
                'message' => 'Tour deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to delete tour', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tour.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/my-tours
     *
     * جولاتي (للـ Guide فقط)
     * ✅ استخدام: forGuide()
     */
    public function myTours(): JsonResponse
    {
        if (!auth('sanctum')->check() || !auth('sanctum')->user()->role === 'guide') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }
        try {
            // ✅ استخدام الـ scope
            $tours = Tours::query()
                ->forGuide(auth('sanctum')->id())
                ->with(['guide:id,name,phone', 'places:places.id,places.title'])
                ->latest('created_at')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'pagination' => ['total' => $tours->total()]
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch my tours', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch my tours.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/{tour_id}/bookings
     *
     * حجوزات جولة معينة (للـ guide فقط)
     */
    public function getTourBookings($tour_id): JsonResponse
    {
        try {
            $tour = Tours::find($tour_id);

            if (!$tour) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour not found.',
                ], 404);
            }

            if (auth('sanctum')->id() !== $tour->guide_id && auth('sanctum')->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 403);
            }

            $bookings = $tour->bookings()
                ->with(['tourist:id,name,email,phone'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $bookings,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch tour bookings', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tour bookings.',
            ], 500);
        }
    }
}
