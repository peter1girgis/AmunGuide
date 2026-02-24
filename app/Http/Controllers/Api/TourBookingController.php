<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTourBookingRequest;
use App\Http\Requests\UpdateTourBookingRequest;
use App\Http\Resources\TourBookingResource;
use App\Models\Tour_bookings;
use App\Models\Tours;
use App\Models\User;
use App\Models\User_activities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TourBookingController - Tour Bookings Management
 *
 * Scenario:
 * 1. Tourist books the tour → create booking with pending status
 * 2. Tourist pays → create payment linked to the booking
 * 3. Admin approves payment → update booking status to approved
 *
 * ✅ Create a new booking
 * ✅ Display all bookings (by authorization)
 * ✅ Display my bookings
 * ✅ Display one booking
 * ✅ Update booking
 * ✅ Cancel/delete booking
 * ✅ Approve/reject (for guide and admin)
 * ✅ Booking statistics
 */
class TourBookingController extends Controller
{
    /**
     * GET /api/v1/tour-bookings
     * Display all bookings (by authorization)
     * - Admin: sees all bookings
     * - Guide: sees bookings for their tours only
     * - Tourist: redirected to /my-bookings
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

            // Based on user role
            if ($user->role === 'admin') {
                // Admin sees everything
            } elseif ($user->role === 'guide') {
                // Guide sees bookings for their tours only
                $query->forGuide($user->id);
            } else {
                // Tourist redirected to their bookings only
                return $this->myBookings($request);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by tour
            if ($request->has('tour_id')) {
                $query->where('tour_id', $request->tour_id);
            }

            // Filter by tourist
            if ($request->has('tourist_id') && $user->role === 'admin') {
                $query->where('tourist_id', $request->tourist_id);
            }

            // Sort results
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
     * Display current user bookings
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

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sort results
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
     * Display one booking
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

            // Check authorization
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'unauthenticated',
                ], 401);
            }

            // Check authorization to access
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
     * Create a new booking (Step 1 in scenario)
     *
     * After creating booking, user is directed to payment page
     */
    public function store(StoreTourBookingRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Create booking with pending status
            $booking = Tour_bookings::createBooking(
                tourId: $validated['tour_id'],
                touristId: auth('sanctum')->id(),
                participantsCount: $validated['participants_count']
            );

            // Load relationships
            $booking->load(['tour.guide', 'tourist']);

            User_activities::create([
                'user_id'       => auth('sanctum')->id(),
                'activity_type' => 'plan_creation', // Chose plan_creation because it's closest to booking in current Enum
                'place_id'      => null, // Leave null because booking is for Tour not Place
                'details'       => [
                    'action'             => 'tour_booking_created',
                    'tour_id'            => $booking->tour_id,
                    'tour_title'         => $booking->tour->title ?? 'N/A',
                    'booking_id'         => $booking->id,
                    'participants_count' => $booking->participants_count,
                    'total_amount'       => $booking->amount,
                    'status'             => 'pending',
                    'ip_address'         => $request->ip(),
                ],
            ]);

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
     * Update booking
     * - Tourist: can update participant count (if pending)
     * - Guide/Admin: can update status
     */
    public function update(UpdateTourBookingRequest $request, Tour_bookings $booking): JsonResponse
    {
        // $requestTest = $request;

        // dd($request->all());
        try {
            $validated = $request->validated();


            // unset($validated['amount']); // Prevent amount update directly from here

            // dd($validated);
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
     * Cancel/delete booking
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

            // Check authorization
            if ($user->role !== 'admin' &&
                ($booking->tourist_id !== $user->id || !$booking->canBeCancelled())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel this booking',
                    'error' => 'unauthorized',
                ], 403);
            }
            $bookingData = [
                'action'        => 'tour_booking_cancelled',
                'booking_id'    => $booking->id,
                'tour_id'       => $booking->tour_id,
                'tour_title'    => $booking->tour->title ?? 'N/A',
                'amount_refunded' => $booking->amount,
                'cancelled_by'  => $user->role, // Was the admin who cancelled or the user?
                'ip_address'    => request()->ip(),
            ];

            $booking->delete();
            User_activities::create([
                'user_id'       => $user->id,
                'activity_type' => 'plan_creation', // Available value in enum
                'place_id'      => null,
                'details'       => $bookingData,
            ]);

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
     * Approve booking (Guide/Admin only)
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

            // Guide can only approve bookings for their own tours
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

            // Check for approved payment
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
     * Reject booking (Guide/Admin only)
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

            // Guide can only reject bookings for their own tours
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
     * Booking statistics (by authorization)
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
                // General statistics for Admin
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
                // Guide statistics
                return response()->json([
                    'success' => true,
                    'data' => Tour_bookings::getGuideBookingStats($user->id),
                ]);

            } else {
                // Tourist statistics
                return response()->json([
                    'success' => false,
                    'message' => 'Tourists should use /my-bookings endpoint for their statistics',
                    'error' => 'use_my_bookings',
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
     * Display bookings for a specific tour
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

            // Check authorization
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

                User_activities::create([
                    'user_id'       => $user->id,
                    'activity_type' => 'visit',
                    'place_id'      => null,
                    'details'       => [
                        'action'        => 'view_tour_bookings',
                        'tour_id'       => $tour->id,
                        'tour_title'    => $tour->title,
                        'user_role'     => $user->role,
                        'results_count' => $bookings->total(),
                        'ip_address'    => request()->ip(),
                    ],
                ]);
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
