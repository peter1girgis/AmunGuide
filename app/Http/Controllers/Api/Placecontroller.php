<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Models\Places;
use App\Models\User_activities;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlaceController extends Controller
{
    /**
     * GET /api/v1/places
     *
     * قائمة الأماكن مع الـ Pagination
     *
     * Query Parameters:
     * - page: 1 (default)
     * - per_page: 15 (default)
     * - sort: trending, rating, newest (optional)
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
     * تفاصيل مكان معين
     */
    public function show(Places $place): JsonResponse
    {
        try {
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
     * البحث عن أماكن
     *
     * Query Parameters:
     * - q: search query (min 3 chars) - مطلوب
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

            $places = Places::query()
                ->where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->latest('created_at')
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
            \Log::error('Search failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Search failed.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/places/trending
     *
     * أكثر الأماكن شهرة (أحدث)
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
     * فلترة متقدمة للأماكن
     *
     * Query Parameters:
     * - min_price: 0
     * - max_price: 1000
     * - sort: price, rating, newest
     */
    public function filter(Request $request): JsonResponse
    {
        try {
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
     * إنشاء مكان جديد (Admin فقط)
     *
     * Multipart/form-data:
     * - title: string (required, unique)
     * - description: string (required, min:10)
     * - ticket_price: number (required, min:0)
     * - rating: number (optional, 0-5)
     * - image: file (optional, image)
     */
    public function store(Request $request): JsonResponse
    {
        // ✅ Check authorization - Admin only
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        try {
            // ✅ Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255|unique:places,title',
                'description' => 'required|string|min:10|max:5000',
                'ticket_price' => 'required|numeric|min:0|max:10000',
                'rating' => 'nullable|numeric|min:0|max:5',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // ✅ Auto-generate slug
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);

            // ✅ Handle image upload
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('places', 'public');
                $validated['image'] = $path;
            }

            // ✅ Create place
            $place = Places::create($validated);

            \Log::info('New place created', [
                'place_id' => $place->id,
                'user_id' => auth()->id(),
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
     * تحديث مكان (Admin فقط)
     */
    public function update(Request $request, Places $place): JsonResponse
    {
        // ✅ Check authorization
        
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        try {
            // ✅ Validate input
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255|unique:places,title,' . $place->id,
                'description' => 'sometimes|string|min:10|max:5000',
                'ticket_price' => 'sometimes|numeric|min:0|max:10000',
                'rating' => 'nullable|numeric|min:0|max:5',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // ✅ Auto-generate slug if title changed
            if (isset($validated['title'])) {
                $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);
            }

            // ✅ Handle image update
            if ($request->hasFile('image')) {
                // Delete old image
                if ($place->image) {
                    \Storage::disk('public')->delete($place->image);
                }
                $path = $request->file('image')->store('places', 'public');
                $validated['image'] = $path;
            }

            // ✅ Update place
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
     * حذف مكان (Admin فقط)
     */
    public function destroy(Places $place): JsonResponse
    {
        // ✅ Check authorization
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        try {
            // ✅ Delete image
            if ($place->image) {
                \Storage::disk('public')->delete($place->image);
            }

            // ✅ Delete place
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
