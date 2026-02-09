<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTourBookingRequest;
use App\Http\Requests\UpdateTourBookingRequest;
use App\Http\Resources\TourBookingResource;
use App\Models\Tour_bookings;
use App\Models\Tours;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TourBookingController - إدارة حجوزات الرحلات
 *
 * السيناريو:
 * 1. السائح يحجز الرحلة → إنشاء booking بحالة pending
 * 2. السائح يدفع → إنشاء payment مربوط بالـ booking
 * 3. Admin يوافق على الدفع → تحديث حالة الـ booking إلى approved
 *
 * ✅ إنشاء حجز جديد
 * ✅ عرض جميع الحجوزات (حسب الصلاحية)
 * ✅ عرض حجوزاتي
 * ✅ عرض حجز واحد
 * ✅ تحديث الحجز
 * ✅ إلغاء/حذف الحجز
 * ✅ الموافقة/الرفض (للمرشد والـ Admin)
 * ✅ إحصائيات الحجوزات
 */
class TourBookingController extends Controller
{
    /**
     * GET /api/v1/tour-bookings
     * عرض جميع الحجوزات (حسب الصلاحية)
     * - Admin: يرى جميع الحجوزات
     * - Guide: يرى حجوزات رحلاته فقط
     * - Tourist: يتم تحويله لـ /my-bookings
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
            $user = auth('sanctum')->user();
            $query = Tour_bookings::with(['tour.guide', 'tourist', 'payments']);

            // حسب دور المستخدم
            if ($user->role === 'admin') {
                // Admin يرى كل شيء
            } elseif ($user->role === 'guide') {
                // المرشد يرى حجوزات رحلاته فقط
                $query->forGuide($user->id);
            } else {
                // السائح يتم تحويله لحجوزاته فقط
                return $this->myBookings($request);
            }

            // فلترة حسب الحالة
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // فلترة حسب الرحلة
            if ($request->has('tour_id')) {
                $query->where('tour_id', $request->tour_id);
            }

            // فلترة حسب السائح
            if ($request->has('tourist_id') && $user->role === 'admin') {
                $query->where('tourist_id', $request->tourist_id);
            }

            // ترتيب النتائج
            $query->latest();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $bookings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => TourBookingResource::collection($bookings),
                'meta' => [
                    'total' => $bookings->total(),
                    'current_page' => $bookings->currentPage(),
                    'per_page' => $bookings->perPage(),
                    'last_page' => $bookings->lastPage(),
                    'role' => $user->role,
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching bookings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/tour-bookings/my-bookings
     * عرض حجوزات المستخدم الحالي
     */
    public function myBookings(Request $request): JsonResponse
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

            $query = Tour_bookings::with(['tour.guide', 'payments'])
                ->where('tourist_id', $userId);

            // فلترة حسب الحالة
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // ترتيب النتائج
            $query->latest();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $bookings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => TourBookingResource::collection($bookings),
                'statistics' => Tour_bookings::getTouristBookingStats($userId),
                'meta' => [
                    'total' => $bookings->total(),
                    'current_page' => $bookings->currentPage(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching your bookings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/tour-bookings/{id}
     * عرض حجز واحد
     */
    public function show($id): JsonResponse
    {
        try {
            $booking = Tour_bookings::with(['tour.guide', 'tourist', 'payments'])->find($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
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

            // التحقق من الصلاحية للوصول
            if ($user->role !== 'admin' &&
                $booking->tourist_id !== $user->id &&
                $booking->tour->guide_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this booking',
                    'error' => 'unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => TourBookingResource::make($booking),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/tour-bookings
     * إنشاء حجز جديد (الخطوة 1 في السيناريو)
     *
     * بعد إنشاء الحجز، يتم توجيه المستخدم لصفحة الدفع
     */
    public function store(StoreTourBookingRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // إنشاء الحجز بحالة pending
            $booking = Tour_bookings::createBooking(
                tourId: $validated['tour_id'],
                touristId: auth('sanctum')->id(),
                participantsCount: $validated['participants_count']
            );

            // تحميل العلاقات
            $booking->load(['tour.guide', 'tourist']);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully. Please proceed to payment.',
                'data' => TourBookingResource::make($booking),
                'next_step' => [
                    'action' => 'create_payment',
                    'booking_id' => $booking->id,
                    'amount' => (float) $booking->amount,
                    'instructions' => 'Create a payment for this booking to complete your reservation.',
                ],
            ], 201);

        } catch (\Throwable $e) {
            // \log('Error creating booking: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/tour-bookings/{id}
     * تحديث الحجز
     * - السائح: يمكنه تحديث عدد المشاركين (إذا كان pending)
     * - المرشد/Admin: يمكنهم تحديث الحالة
     */
    public function update(UpdateTourBookingRequest $request, Tour_bookings $booking): JsonResponse
    {
        try {
            $validated = $request->validated();

            $booking->update($validated);
            $booking->load(['tour.guide', 'tourist', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'Booking updated successfully',
                'data' => TourBookingResource::make($booking),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/tour-bookings/{id}
     * إلغاء/حذف الحجز
     */
    public function destroy($id): JsonResponse
    {
        try {
            $booking = Tour_bookings::find($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                    'error' => 'not_found',
                ], 404);
            }

            $user = auth('sanctum')->user();

            // التحقق من الصلاحية
            if ($user->role !== 'admin' &&
                ($booking->tourist_id !== $user->id || !$booking->canBeCancelled())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel this booking',
                    'error' => 'unauthorized',
                ], 403);
            }

            $booking->delete();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/tour-bookings/{id}/approve
     * الموافقة على الحجز (Guide/Admin فقط)
     */
    public function approve($id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !in_array($user->role, ['admin', 'guide'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Guide or Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            $booking = Tour_bookings::with(['tour.guide', 'tourist', 'payments'])->find($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                    'error' => 'not_found',
                ], 404);
            }

            // المرشد يمكنه الموافقة على حجوزات رحلاته فقط
            if ($user->role === 'guide' && $booking->tour->guide_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only approve bookings for your own tours',
                    'error' => 'unauthorized',
                ], 403);
            }

            if ($booking->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is already approved',
                    'error' => 'already_approved',
                ], 400);
            }

            // التحقق من وجود دفعة معتمدة
            if (!$booking->hasApprovedPayment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot approve booking without an approved payment',
                    'error' => 'no_approved_payment',
                ], 400);
            }

            $booking->approve();

            return response()->json([
                'success' => true,
                'message' => 'Booking approved successfully',
                'data' => TourBookingResource::make($booking),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/tour-bookings/{id}/reject
     * رفض الحجز (Guide/Admin فقط)
     */
    public function reject($id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !in_array($user->role, ['admin', 'guide'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Guide or Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            $booking = Tour_bookings::with(['tour.guide', 'tourist', 'payments'])->find($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                    'error' => 'not_found',
                ], 404);
            }

            // المرشد يمكنه رفض حجوزات رحلاته فقط
            if ($user->role === 'guide' && $booking->tour->guide_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only reject bookings for your own tours',
                    'error' => 'unauthorized',
                ], 403);
            }

            if ($booking->isRejected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking is already rejected',
                    'error' => 'already_rejected',
                ], 400);
            }

            $booking->reject();

            return response()->json([
                'success' => true,
                'message' => 'Booking rejected successfully',
                'data' => TourBookingResource::make($booking),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/tour-bookings/statistics
     * إحصائيات الحجوزات (حسب الصلاحية)
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
            $user = auth('sanctum')->user();

            if ($user->role === 'admin') {
                // إحصائيات عامة للـ Admin
                $allBookings = Tour_bookings::with(['tour', 'payments'])->get();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_bookings' => $allBookings->count(),
                        'total_revenue' => (float) $allBookings->where('status', 'approved')->sum('amount'),
                        'by_status' => [
                            'pending' => $allBookings->where('status', 'pending')->count(),
                            'approved' => $allBookings->where('status', 'approved')->count(),
                            'rejected' => $allBookings->where('status', 'rejected')->count(),
                        ],
                        'total_participants' => $allBookings->sum('participants_count'),
                        'bookings_with_payment' => $allBookings->filter->hasPayment()->count(),
                        'bookings_with_approved_payment' => $allBookings->filter->hasApprovedPayment()->count(),
                    ],
                ]);

            } elseif ($user->role === 'guide') {
                // إحصائيات المرشد
                return response()->json([
                    'success' => true,
                    'data' => Tour_bookings::getGuideBookingStats($user->id),
                ]);

            } else {
                // إحصائيات السائح
                return response()->json([
                    'success' => true,
                    'data' => Tour_bookings::getTouristBookingStats($user->id),
                ]);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/tours/{tourId}/bookings
     * عرض حجوزات رحلة معينة
     */
    public function tourBookings($tourId): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error' => 'unauthenticated',
            ], 401);
        }

        try {
            $tour = Tours::find($tourId);

            if (!$tour) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour not found',
                    'error' => 'not_found',
                ], 404);
            }

            // التحقق من الصلاحية
            if ($user->role !== 'admin' && $tour->guide_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view bookings for this tour',
                    'error' => 'unauthorized',
                ], 403);
            }

            $bookings = Tour_bookings::with(['tourist', 'payments'])
                ->where('tour_id', $tourId)
                ->latest()
                ->paginate(15);

            return response()->json([
                'success' => true,
                'tour' => [
                    'id' => $tour->id,
                    'title' => $tour->title,
                    'price' => (float) $tour->price,
                ],
                'data' => TourBookingResource::collection($bookings),
                'meta' => [
                    'total' => $bookings->total(),
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tour bookings: ' . $e->getMessage(),
            ], 500);
        }
    }
}
