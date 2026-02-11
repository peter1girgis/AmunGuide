<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Payments;
use App\Models\User;
use App\Models\User_activities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PaymentController - إدارة الدفعات
 *
 * ✅ إنشاء دفعة جديدة
 * ✅ عرض جميع الدفعات (للـ Admin)
 * ✅ عرض دفعات المستخدم الحالي
 * ✅ عرض دفعة واحدة
 * ✅ تحديث الدفعة (المبلغ للمستخدم، الحالة للـ Admin)
 * ✅ حذف الدفعة
 * ✅ الموافقة على الدفعة (Admin)
 * ✅ رفض الدفعة (Admin)
 * ✅ إحصائيات الدفعات
 */
class PaymentController extends Controller
{
    /**
     * GET /api/v1/payments
     * عرض جميع الدفعات (Admin فقط)
     */
    public function index(Request $request): JsonResponse
    {
        // التحقق من صلاحية Admin
        if (!auth('sanctum')->check() || auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            // فلترة حسب الحالة
            $query = Payments::with(['payer', 'payable']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // فلترة حسب نوع المورد
            if ($request->has('payable_type')) {
                $modelMap = [
                    'tour_bookings' => 'App\\Models\\Tour_bookings',
                    'plans' => 'App\\Models\\Plans',
                ];

                if (isset($modelMap[$request->payable_type])) {
                    $query->where('payable_type', $modelMap[$request->payable_type]);
                }
            }

            // فلترة حسب المستخدم
            if ($request->has('user_id')) {
                $query->where('payer_id', $request->user_id);
            }

            // فلترة حسب التاريخ
            if ($request->has('from_date') && $request->has('to_date')) {
                $query->betweenDates($request->from_date, $request->to_date);
            }

            // ترتيب النتائج
            $query->latest();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => PaymentResource::collection($payments),
                'meta' => [
                    'total' => $payments->total(),
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'last_page' => $payments->lastPage(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/payments/my-payments
     * عرض دفعات المستخدم الحالي
     */
    public function myPayments(Request $request): JsonResponse
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

            // فلترة حسب الحالة
            $query = Payments::with(['payable'])
                ->where('payer_id', $userId);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // ترتيب النتائج
            $query->latest();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => PaymentResource::collection($payments),
                'statistics' => Payments::getUserPaymentStats($userId),
                'meta' => [
                    'total' => $payments->total(),
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching your payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/payments/{id}
     * عرض دفعة واحدة
     */
    public function show($id): JsonResponse
    {
        try {
            $payment = Payments::with(['payer', 'payable'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
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

            if ($user->role !== 'admin' && $payment->payer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this payment',
                    'error' => 'unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => PaymentResource::make($payment),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/payments
     * إنشاء دفعة جديدة (الخطوة 2 في السيناريو)
     *
     * يتم إنشاء الدفعة بعد إنشاء الحجز
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // تحويل payable_type إلى Model Class
            $modelMap = [
                'tour_bookings' => 'App\\Models\\Tour_bookings',
                'plans' => 'App\\Models\\Plans',
            ];

            $payableType = $modelMap[$validated['payable_type']];

            // إذا كان الدفع لحجز رحلة، نتحقق من حالة الحجز
            if ($payableType === 'App\\Models\\Tour_bookings') {
                $booking = \App\Models\Tour_bookings::find($validated['payable_id']);

                if (!$booking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Booking not found',
                        'error' => 'not_found',
                    ], 404);
                }

                // التحقق من أن الحجز يخص المستخدم الحالي
                if ($booking->tourist_id !== auth('sanctum')->id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only create payments for your own bookings',
                        'error' => 'unauthorized',
                    ], 403);
                }

                // التحقق من إمكانية إنشاء دفعة
                if (!$booking->canCreatePayment()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot create payment for this booking. It may already have a pending payment or is not in pending status.',
                        'error' => 'cannot_create_payment',
                    ], 400);
                }

                // التأكد من أن المبلغ مطابق لمبلغ الحجز
                if ($validated['amount'] != $booking->amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment amount must match booking amount',
                        'error' => 'amount_mismatch',
                        'booking_amount' => (float) $booking->amount,
                    ], 400);
                }
            }

            // --- التعديلات المطلوبة (رفع الصورة) ---
            $receiptPath = null;
            if ($request->hasFile('receipt_image')) {
                $receiptPath = $request->file('receipt_image')->store('payments/receipts', 'public');
            }

            // --- إضافة الحقول الجديدة للدالة ---
            $payment = Payments::createPayment(
                payerId: auth('sanctum')->id(),
                amount: $validated['amount'],
                payableType: $payableType,
                payableId: $validated['payable_id'],
                status: 'pending',
                receiptImage: $receiptPath, // الحقل الجديد
                transactionId: $validated['transaction_id'] ?? null, // الحقل الجديد
                paymentMethod: $validated['payment_method'] ?? null, // الحقل الجديد
                notes: $validated['notes'] ?? null // الحقل الجديد
            );

            User_activities::create([
                    'user_id'       => auth('sanctum')->id(),
                    'activity_type' => 'plan_creation', // الأقرب لعملية إنشاء معاملة
                    'place_id'      => null,
                    'details'       => [
                        'action'         => 'payment_submitted',
                        'payment_id'     => $payment->id,
                        'amount'         => $payment->amount,
                        'payable_type'   => $validated['payable_type'],
                        'payable_id'     => $payment->payable_id,
                        'method'         => $payment->payment_method,
                        'transaction_id' => $payment->transaction_id,
                        'status'         => 'pending_approval',
                        'ip_address'     => $request->ip(),
                    ],
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully. Waiting for admin approval.',
                'data' => PaymentResource::make($payment->load(['payer', 'payable'])),
                'next_step' => [
                    'action' => 'wait_for_approval',
                    'instructions' => 'Your payment is pending. An admin will review it shortly.',
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/v1/payments/{id}
     * تحديث الدفعة
     * - المستخدم العادي: يمكنه تحديث المبلغ فقط للدفعات المعلقة
     * - Admin: يمكنه تحديث المبلغ والحالة
     */
    public function update(UpdatePaymentRequest $request, $id): JsonResponse
    {
        // dd($request->all());
        // البحث عن الدفعة يدوياً
        $payment = Payments::find($id);

        // dd($request);

        // التحقق من وجودها قبل البدء في أي منطق
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }
        try {
            $validated = $request->validated();

            // --- التعديل الخاص بالصورة ---
            if ($request->hasFile('receipt_image')) {
                // 1. مسح الصورة القديمة من السيرفر إذا كانت موجودة
                if ($payment->receipt_image) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($payment->receipt_image);
                }

                // 2. تخزين الصورة الجديدة
                $path = $request->file('receipt_image')->store('payments/receipts', 'public');
                $validated['receipt_image'] = $path;
            }

            // تحديث البيانات في الداتابيز (بما فيها الحقول الجديدة transaction_id, notes, etc)

            $payment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => PaymentResource::make($payment->load(['payer', 'payable'])),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/payments/{id}
     * حذف الدفعة
     * - المستخدم: يمكنه حذف دفعاته المعلقة فقط
     * - Admin: يمكنه حذف أي دفعة
     */
    public function destroy($id): JsonResponse
    {
        try {
            $payment = Payments::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                    'error' => 'not_found',
                ], 404);
            }

            $user = auth('sanctum')->user();

            // التحقق من الصلاحية
            if (
                $user->role !== 'admin' &&
                ($payment->payer_id !== $user->id || $payment->status !== 'pending' || $payment->status !== 'rejected')
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this payment',
                    'error' => 'unauthorized',
                ], 403);
            }
            // --- التعديل المطلوب لفتح الحجز مرة أخرى ---
            $payable = $payment->payable; // العلاقة Polymorphic

            if ($payable instanceof \App\Models\Tour_bookings) {
                // نعيد حالة الحجز إلى pending عشان ميثود canCreatePayment ترجع true
                $payable->update(['status' => 'pending']);
            }

            // --- التعديل المطلوب: مسح الصورة من السيرفر قبل حذف السجل ---
            if ($payment->receipt_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($payment->receipt_image);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/payments/{id}/approve
     * الموافقة على الدفعة (Admin فقط) - الخطوة 3 في السيناريو
     *
     * عند الموافقة على الدفع، يتم تلقائياً تحديث حالة الحجز إلى approved
     */
    public function approve($id): JsonResponse
    {
        if (!auth('sanctum')->check() || auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            $payment = Payments::with(['payer', 'payable'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                    'error' => 'not_found',
                ], 404);
            }

            if ($payment->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already approved',
                    'error' => 'already_approved',
                ], 400);
            }

            // الموافقة على الدفع
            $payment->approve();

            // إذا كان الدفع لحجز رحلة، نوافق على الحجز تلقائياً
            if ($payment->payable_type === 'App\\Models\\Tour_bookings' && $payment->payable) {
                $booking = $payment->payable;

                // تحديث حالة الحجز إلى approved
                if ($booking->isPending()) {
                    $booking->approve();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment approved successfully. Booking has been confirmed.',
                'data' => PaymentResource::make($payment->fresh(['payer', 'payable'])),
                'booking_updated' => $payment->payable_type === 'App\\Models\\Tour_bookings',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error approving payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/payments/{id}/reject
     * رفض الدفعة (Admin فقط)
     */
    public function reject($id): JsonResponse
    {
        if (!auth('sanctum')->check() || auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            $payment = Payments::with(['payer', 'payable'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                    'error' => 'not_found',
                ], 404);
            }

            if ($payment->isFailed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already marked as failed',
                    'error' => 'already_failed',
                ], 400);
            }

            $payment->markAsFailed();

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected successfully',
                'data' => PaymentResource::make($payment),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/payments/statistics
     * إحصائيات الدفعات (Admin فقط)
     */
    public function statistics(): JsonResponse
    {
        if (!auth('sanctum')->check() || auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            $allPayments = Payments::all();

            $statistics = [
                'total_payments' => $allPayments->count(),
                'total_amount' => (float) $allPayments->sum('amount'),

                'by_status' => [
                    'pending' => [
                        'count' => $allPayments->where('status', 'pending')->count(),
                        'amount' => (float) $allPayments->where('status', 'pending')->sum('amount'),
                    ],
                    'approved' => [
                        'count' => $allPayments->where('status', 'approved')->count(),
                        'amount' => (float) $allPayments->where('status', 'approved')->sum('amount'),
                    ],
                    'failed' => [
                        'count' => $allPayments->where('status', 'failed')->count(),
                        'amount' => (float) $allPayments->where('status', 'failed')->sum('amount'),
                    ],
                ],

                'by_type' => $allPayments->groupBy('payable_type')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount' => (float) $group->sum('amount'),
                    ];
                }),

                'recent_payments' => PaymentResource::collection(
                    Payments::with(['payer', 'payable'])
                        ->latest()
                        ->take(10)
                        ->get()
                ),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/users/{userId}/payments
     * عرض دفعات مستخدم معين (Admin فقط)
     */
    public function userPayments($userId): JsonResponse
    {
        if (!auth('sanctum')->check() || auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'not_found',
                ], 404);
            }

            $payments = Payments::with(['payable'])
                ->where('payer_id', $userId)
                ->latest()
                ->paginate(15);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'data' => PaymentResource::collection($payments),
                'statistics' => Payments::getUserPaymentStats($userId),
                'meta' => [
                    'total' => $payments->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/payments/bulk-approve
     * الموافقة على عدة دفعات دفعة واحدة (Admin فقط)
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        if (!auth('sanctum')->check() || auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
                'error' => 'unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'required|integer|exists:payments,id',
        ]);

        try {
            $approved = 0;
            $failed = 0;
            $errors = [];

            foreach ($validated['payment_ids'] as $paymentId) {
                $payment = Payments::find($paymentId);

                if ($payment && !$payment->isApproved()) {
                    $payment->approve();
                    $approved++;
                } else {
                    $failed++;
                    $errors[] = "Payment ID $paymentId could not be approved";
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully approved $approved payment(s)",
                'data' => [
                    'approved' => $approved,
                    'failed' => $failed,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk approval: ' . $e->getMessage(),
            ], 500);
        }
    }
}
