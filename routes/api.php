<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\CommentsController;
use App\Http\Controllers\Api\LikesController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TourBookingController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\TourController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great! =>
|
*/


Route::prefix('v1')->group(function () {


    Route::get('places/search', [PlaceController::class, 'search'])->name('places.search');
    Route::get('places/trending', [PlaceController::class, 'trending'])->name('places.trending');
    Route::get('places/filter', [PlaceController::class, 'filter'])->name('places.filter');


    Route::get('places', [PlaceController::class, 'index'])->name('places.index');
    Route::get('places/{place}', [PlaceController::class, 'show'])->name('places.show')
    ->missing(function () {
        return response()->json([
            'success' => false,
            'message' => 'place not found.'
        ], 404);
    });


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('places', [PlaceController::class, 'store'])->name('places.store');
        Route::put('places/{place}', [PlaceController::class, 'update'])->name('places.update')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'places not found.'
            ], 404);
        });
        Route::delete('places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'place not found.'
            ], 404);
        });
    });


    // ═════════════════════════════════════════════════════════════════════
    // 📍 PUBLIC TOUR ROUTES (Guest + Authenticated with tracking)
    // ═════════════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/tours
     * قائمة الجولات النشطة -------------------------------------  Done
     */
    Route::get('tours', [TourController::class, 'index'])
        ->name('tours.index');
    /**
     * GET /api/v1/tours/search
     * البحث عن جولات
     */

    Route::get('tours/search', [TourController::class, 'search'])
        ->name('tours.search');

        /**
     * GET /api/v1/tours/filter ------------------------ Done
     * فلترة الجولات
     */
    Route::get('tours/filter', [TourController::class, 'filter'])
        ->name('tours.filter');

    /**
     * GET /api/v1/tours/popular -----------------------------  Done
     * أشهر الجولات
     */
    Route::get('tours/popular', [TourController::class, 'popular'])
        ->name('tours.popular');
    /**
     * GET /api/v1/tours/{id}
     * تفاصيل جولة + tracking -------------------------- Done
     */
    Route::get('tours/{tour}', [TourController::class, 'show'])
        ->name('tours.show')
        ->missing(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour not found.'
                ], 404);
            });





    /**
     * GET /api/v1/tours/guide/{guide_id} ---------------------  Done
     * جولات دليل معين
     */
    Route::get('tours/guide/{guide_id}', [TourController::class, 'getGuideToursPublic'])
        ->name('tours.guide.public');

    // ═════════════════════════════════════════════════════════════════════
    // 🔒 PROTECTED TOUR ROUTES (Authentication Required)
    // ═════════════════════════════════════════════════════════════════════

    Route::middleware('auth:sanctum')->group(function () {

        /**
         * POST /api/v1/tours
         * إنشاء جولة (Guide فقط) ---------------------------  Done
         */
        Route::post('tours', [TourController::class, 'store'])
            ->name('tours.store');

        /**
         * PUT /api/v1/tours/{id}
         * تحديث جولة (Guide owner)
         * Authorization check في الـ Request -------------- Done
         */
        Route::put('tours/{tour}', [TourController::class, 'update'])
            ->name('tours.update')
            ->missing(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour not found.'
                ], 404);
            });

        /**
         * DELETE /api/v1/tours/{id}
         * حذف جولة (Guide owner)
         * Authorization check في الـ Controller -------------------------- Done
         */
        Route::delete('tours/{tour}', [TourController::class, 'destroy'])
            ->name('tours.destroy')
            ->missing(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Tour not found.'
                ], 404);
            });

        /**
         * GET /api/v1/tours/my-tours ----------------------------  Done
         * جولاتي (Guide فقط)
         */
        Route::get('my-tours', [TourController::class, 'myTours'])
            ->name('tours.my-tours');

        /**
         * GET /api/v1/tours/{tour_id}/bookings
         * حجوزات جولة (Guide owner)
         */
        // Route::get('tours/{tour_id}/bookings', [TourController::class, 'getTourBookings'])
        //     ->name('tours.bookings');
    });
    // ════════════════════════════════════════════════════════════
    // 💬 COMMENTS ENDPOINTS
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/comments/{type}/{id}
     * الحصول على جميع التعليقات لـ موضوع معين
     * type: tours, places, plans ---------------------------------- Done
     * id: معرف الموضوع
     */
    Route::get('comments/{commentableType}/{commentableId}',
        [CommentsController::class, 'index'])
        ->name('comments.index')
        ->where('commentableType', 'tours|places|plans');

    /**
     * GET /api/v1/{type}/{id}/comments ------------------------------- Done
     *  بديل: الحصول على التعليقات (نفس الوظيفة)
     */
    Route::get('{commentableType}/{commentableId}/comments',
        [CommentsController::class, 'index'])
        ->where('commentableType', 'tours|places|plans');

    /**
     * GET /api/v1/{type}/{id}/comments/count ---------------------------------- Done
     * عد التعليقات
     */
    Route::get('{commentableType}/{commentableId}/comments/count',
        [CommentsController::class, 'count'])
        ->where('commentableType', 'tours|places|plans');

    /**
     * GET /api/v1/comments/{id} ---------------------------------- Done
     * الحصول على تعليق واحد
     */
    Route::get('comments/{comment}', [CommentsController::class, 'show'])
        ->name('comments.show');

    /**
     * GET /api/v1/user/{userId}/comments -------------------------  Done
     * جميع تعليقات مستخدم معين
     */
    Route::get('user/{userId}/comments', [CommentsController::class, 'userComments'])
        ->name('comments.user');

    // Protected comment endpoints (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        /**
         * POST /api/v1/comments
         * إضافة تعليق
         * Body: {
         *   "content": "محتوى التعليق",
         *   "commentable_type": "tours|places|plans",
         *   "commentable_id": 1
         * } -------------------------------------- Done
         */
        Route::post('comments', [CommentsController::class, 'store'])
            ->name('comments.store');

        /**
         * POST /api/v1/{type}/{id}/comments
         * إضافة تعليق مباشرة على موضوع (أسهل)
         * Body: { "content": "محتوى التعليق" } --------------------------------------- Done
         */
        Route::post('{commentableType}/{commentableId}/comments',
            [CommentsController::class, 'storeOnResource'])
            ->where('commentableType', 'tours|places|plans');

        /**
         * PUT /api/v1/comments/{id}
         * تحديث التعليق (owner فقط)
         * Body: { "content": "محتوى جديد" } ---------------------------------- Done
         */
        Route::put('comments/{id}', [CommentsController::class, 'update'])
            ->name('comments.update');

        /**
         * DELETE /api/v1/comments/{id}
         * حذف التعليق (owner أو admin) ---------------------------------- Done
         */
        Route::delete('comments/{comment}', [CommentsController::class, 'destroy'])
            ->name('comments.destroy');
    });

    // ════════════════════════════════════════════════════════════
    // ❤️ LIKES ENDPOINTS
    // ════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/{type}/{id}/likes
     * الحصول على جميع التقييمات لـ موضوع معين
     * type: tours, places, plans     ------------------------------ Done
     */
    Route::get('{likeableType}/{likeableId}/likes',
        [LikesController::class, 'index'])
        ->where('likeableType', 'tours|places|plans');

    /**
     * GET /api/v1/{type}/{id}/likes/count
     * عد التقييمات + هل المستخدم قيّم؟  ------------------------------ Done
     */
    Route::get('{likeableType}/{likeableId}/likes/count',
        [LikesController::class, 'count'])
        ->where('likeableType', 'tours|places|plans');

    /**
     * GET /api/v1/user/{userId}/likes   ----------------------------------------------  Done
     * جميع التقييمات لـ مستخدم معين
     */
    Route::get('user/{userId}/likes', [LikesController::class, 'userLikes'])
        ->name('likes.user');

    // Protected like endpoints (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        /**
         * POST /api/v1/likes
         * إضافة تقييم (Like)
         * Body: {
         *   "likeable_type": "tours|places|plans",
         *   "likeable_id": 1
         * } --------------------------------------- Done
         */
        Route::post('likes', [LikesController::class, 'store'])
            ->name('likes.store');

        /**
         * POST /api/v1/likes/toggle
         * تقييم أو إزالة التقييم (أسهل للـ frontend)
         * Body: {
         *   "likeable_type": "tours|places|plans",
         *   "likeable_id": 1
         * } ----------------------------------------------------------Done
         */
        Route::post('likes/toggle', [LikesController::class, 'toggle'])
            ->name('likes.toggle');

        /**
         * DELETE /api/v1/likes/{id}
         * إزالة التقييم   --------------------------------------- Done
         */
        Route::delete('likes/{like}', [LikesController::class, 'destroy'])
            ->name('likes.destroy');
    });
    /**
     * DELETE /api/v1/{type}/{id}/likes
     * إزالة التقييم من موضوع معين ---------------------------------- Done
     */
    Route::delete('{likeableType}/{likeableId}/likes',
        [LikesController::class, 'removeFromResource'])
        ->where('likeableType', 'tours|places|plans');

});

Route::prefix('v1/analysis')->group(function () {
    /**
     * POST /api/v1/analysis/user_activity
     * give analysis data for a single user  ------------------------------  Done
     */
    Route::post('/user_activity', [AnalysisController::class, 'getMyData'])
        ->name('analysis.user.single');
    /**
     * GET /api/v1/analysis/users-all
     * give analysis data for all users  ------------------------------  Done
     */

    Route::get('/users-all', [AnalysisController::class, 'getAllUsersData'])
        ->name('analysis.users.global');

});

/**
 * ⚠️ IMPORTANT NOTES:
 *
 * 1. Route Order Matters!
 *    GET /places/search  must come before GET /places/{id}
 *    Because Laravel will try to match {id} first
 *
 * 2. Auth Middleware:
 *    - Public endpoints: no middleware
 *    - Protected endpoints: middleware('auth:sanctum')
 *
 * 3. Authorization:
 *    - Store, Update, Delete: role:admin
 *    - Check is done in controller with authorize()
 *
 * 4. Rate Limiting (optional):
 *    Add ->middleware('throttle:60,1') for rate limiting
 *
 * 5. Query Parameters:
 *    - page: for pagination (default 1)
 *    - per_page: items per page (default 15, max 100)
 *    - sort: for sorting
 *    - search: for searching
 */

/**
 * ═══════════════════════════════════════════════════════════════════════
 * PAYMENT ROUTES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * نظام إدارة الدفعات الكامل
 *
 * Protected Routes: تتطلب Sanctum Authentication
 * Admin Routes: تتطلب role = 'admin'
 * ═══════════════════════════════════════════════════════════════════════
 */

Route::prefix('v1')->group(function () {

    /**
     * ─────────────────────────────────────────────────────────────────
     * PUBLIC/GUEST ROUTES (لا تتطلب Authentication)
     * ─────────────────────────────────────────────────────────────────
     */

    // لا توجد routes عامة للدفعات - كلها محمية


    /**
     * ─────────────────────────────────────────────────────────────────
     * AUTHENTICATED USER ROUTES
     * ─────────────────────────────────────────────────────────────────
     */

    Route::middleware('auth:sanctum')->group(function () {

        /**
         * دفعات المستخدم الحالي
         */
        // GET /api/v1/payments/my-payments - عرض جميع دفعات المستخدم الحالي
        Route::get('payments/my-payments', [PaymentController::class, 'myPayments'])
            ->name('payments.my-payments');

        /**
         * إنشاء دفعة جديدة
         */
        // POST /api/v1/payments - إنشاء دفعة جديدة
        Route::post('payments', [PaymentController::class, 'store'])
            ->name('payments.store');

        /**
         * عرض دفعة واحدة
         */
        // GET /api/v1/payments/{id} - عرض تفاصيل دفعة واحدة
        Route::get('payments/{id}', [PaymentController::class, 'show'])
            ->name('payments.show')
            ->where('id', '[0-9]+');

        /**
         * تحديث الدفعة
         */
        // PUT/PATCH /api/v1/payments/{id} - تحديث دفعة (المبلغ للمستخدم، الحالة للـ Admin)
        Route::match(['put', 'patch'], 'payments/{payment}', [PaymentController::class, 'update'])
            ->name('payments.update');

        /**
         * حذف الدفعة
         */
        // DELETE /api/v1/payments/{id} - حذف دفعة (معلقة فقط للمستخدم، أي دفعة للـ Admin)
        Route::delete('payments/{id}', [PaymentController::class, 'destroy'])
            ->name('payments.destroy');


        /**
         * ─────────────────────────────────────────────────────────────────
         * ADMIN ONLY ROUTES
         * ─────────────────────────────────────────────────────────────────
         */

        // Route::middleware('role:admin')->group(function () {

            /**
             * عرض جميع الدفعات
             */
            // GET /api/v1/payments - عرض جميع الدفعات مع الفلاتر
            Route::get('payments', [PaymentController::class, 'index'])
                ->name('payments.index');

            /**
             * إحصائيات الدفعات
             */
            // GET /api/v1/payments/statistics - إحصائيات شاملة للدفعات
            Route::get('payments/statistics', [PaymentController::class, 'statistics'])
                ->name('payments.statistics');

            /**
             * الموافقة/الرفض
             */
            // POST /api/v1/payments/{id}/approve - الموافقة على دفعة
            Route::post('payments/{id}/approve', [PaymentController::class, 'approve'])
                ->name('payments.approve');

            // POST /api/v1/payments/{id}/reject - رفض دفعة
            Route::post('payments/{id}/reject', [PaymentController::class, 'reject'])
                ->name('payments.reject');

            /**
             * الموافقة الجماعية
             */
            // POST /api/v1/payments/bulk-approve - الموافقة على عدة دفعات
            Route::post('payments/bulk-approve', [PaymentController::class, 'bulkApprove'])
                ->name('payments.bulk-approve');

            /**
             * دفعات مستخدم معين
             */
            // GET /api/v1/users/{userId}/payments - عرض دفعات مستخدم معين
            Route::get('users/{userId}/payments', [PaymentController::class, 'userPayments'])
                ->name('users.payments');
        });
    });
// });

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ROUTE EXAMPLES & USAGE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * PUBLIC ENDPOINTS:
 * -----------------
 * لا يوجد - جميع الـ endpoints محمية
 *
 *
 * AUTHENTICATED USER ENDPOINTS:
 * -----------------------------
 *
 * 1. عرض دفعاتي:
 *    GET /api/v1/payments/my-payments
 *    GET /api/v1/payments/my-payments?status=pending
 *    GET /api/v1/payments/my-payments?per_page=20
 *
 * 2. إنشاء دفعة:
 *    POST /api/v1/payments
 *    Body: {
 *      "amount": 150.00,
 *      "payable_type": "tour_bookings",
 *      "payable_id": 5
 *    }
 *
 * 3. عرض دفعة واحدة:
 *    GET /api/v1/payments/1
 *
 * 4. تحديث دفعة:
 *    PUT /api/v1/payments/1
 *    Body: {
 *      "amount": 175.00
 *    }
 *
 * 5. حذف دفعة:
 *    DELETE /api/v1/payments/1
 *
 *
 * ADMIN ONLY ENDPOINTS:
 * ---------------------
 *
 * 1. عرض جميع الدفعات:
 *    GET /api/v1/payments
 *    GET /api/v1/payments?status=pending
 *    GET /api/v1/payments?payable_type=tour_bookings
 *    GET /api/v1/payments?user_id=5
 *    GET /api/v1/payments?from_date=2025-01-01&to_date=2025-02-01
 *
 * 2. إحصائيات الدفعات:
 *    GET /api/v1/payments/statistics
 *
 * 3. الموافقة على دفعة:
 *    POST /api/v1/payments/1/approve
 *
 * 4. رفض دفعة:
 *    POST /api/v1/payments/1/reject
 *
 * 5. الموافقة الجماعية:
 *    POST /api/v1/payments/bulk-approve
 *    Body: {
 *      "payment_ids": [1, 2, 3, 4]
 *    }
 *
 * 6. دفعات مستخدم معين:
 *    GET /api/v1/users/5/payments
 *
 * 7. تحديث حالة دفعة (Admin):
 *    PUT /api/v1/payments/1
 *    Body: {
 *      "status": "approved",
 *      "amount": 200.00
 *    }
 *
 * ═══════════════════════════════════════════════════════════════════════
 */


/**
 * ═══════════════════════════════════════════════════════════════════════
 * TOUR BOOKING ROUTES
 * ═══════════════════════════════════════════════════════════════════════
 *
 * نظام إدارة حجوزات الرحلات الكامل
 *
 * السيناريو:
 * 1. السائح يحجز الرحلة → POST /tour-bookings (status: pending)
 * 2. السائح يدفع → POST /payments (مربوط بالـ booking_id)
 * 3. Admin يوافق على الدفع → POST /payments/{id}/approve
 *    → تلقائياً يتم تحديث الـ booking status إلى approved
 *
 * Protected Routes: تتطلب Sanctum Authentication
 * Guide/Admin Routes: تتطلب role = 'guide' or 'admin'
 * ═══════════════════════════════════════════════════════════════════════
 */

Route::prefix('v1')->group(function () {

    /**
     * ─────────────────────────────────────────────────────────────────
     * AUTHENTICATED USER ROUTES
     * ─────────────────────────────────────────────────────────────────
     */

    Route::middleware('auth:sanctum')->group(function () {

        /**
         * حجوزاتي (للسائح)
         */
        // GET /api/v1/tour-bookings/my-bookings - عرض جميع حجوزاتي
        Route::get('tour-bookings/my-bookings', [TourBookingController::class, 'myBookings'])
            ->name('tour-bookings.my-bookings');

        /**
         * الإحصائيات
         */
        // GET /api/v1/tour-bookings/statistics - إحصائيات حسب الدور
        Route::get('tour-bookings/statistics', [TourBookingController::class, 'statistics'])
            ->name('tour-bookings.statistics');

        /**
         * إنشاء حجز جديد (الخطوة 1)
         */
        // POST /api/v1/tour-bookings - إنشاء حجز جديد (tourist only)
        Route::post('tour-bookings', [TourBookingController::class, 'store'])
            ->name('tour-bookings.store');

        /**
         * عرض جميع الحجوزات (حسب الصلاحية)
         */
        // GET /api/v1/tour-bookings - عرض الحجوزات حسب الدور
        Route::get('tour-bookings', [TourBookingController::class, 'index'])
            ->name('tour-bookings.index');

        /**
         * عرض حجز واحد
         */
        // GET /api/v1/tour-bookings/{id} - عرض تفاصيل حجز واحد
        Route::get('tour-bookings/{id}', [TourBookingController::class, 'show'])
            ->name('tour-bookings.show');

        /**
         * تحديث الحجز
         */
        // PUT/PATCH /api/v1/tour-bookings/{id} - تحديث حجز
        Route::match(['put', 'patch'], 'tour-bookings/{booking}', [TourBookingController::class, 'update'])
            ->name('tour-bookings.update');

        /**
         * إلغاء/حذف الحجز
         */
        // DELETE /api/v1/tour-bookings/{id} - إلغاء حجز
        Route::delete('tour-bookings/{id}', [TourBookingController::class, 'destroy'])
            ->name('tour-bookings.destroy');

        /**
         * عرض حجوزات رحلة معينة (للمرشد/Admin)
         */
        // GET /api/v1/tours/{tourId}/bookings - عرض حجوزات رحلة معينة
        Route::get('tours/{tourId}/bookings', [TourBookingController::class, 'tourBookings'])
            ->name('tours.bookings');

        /**
         * الموافقة والرفض (للمرشد/Admin)
         */
        // POST /api/v1/tour-bookings/{id}/approve - الموافقة على حجز
        Route::post('tour-bookings/{id}/approve', [TourBookingController::class, 'approve'])
            ->name('tour-bookings.approve');

        // POST /api/v1/tour-bookings/{id}/reject - رفض حجز
        Route::post('tour-bookings/{id}/reject', [TourBookingController::class, 'reject'])
            ->name('tour-bookings.reject');
    });
});

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ROUTE EXAMPLES & USAGE
 * ═══════════════════════════════════════════════════════════════════════
 *
 * COMPLETE BOOKING FLOW (السيناريو الكامل):
 * ------------------------------------------
 *
 * الخطوة 1: إنشاء الحجز
 * POST /api/v1/tour-bookings
 * Body: {
 *   "tour_id": 5,
 *   "participants_count": 3
 * }
 * Response: {
 *   "success": true,
 *   "message": "Booking created successfully. Please proceed to payment.",
 *   "data": { booking details },
 *   "next_step": {
 *     "action": "create_payment",
 *     "booking_id": 10,
 *     "amount": 450.00
 *   }
 * }
 *
 * الخطوة 2: إنشاء الدفعة
 * POST /api/v1/payments
 * Body: {
 *   "amount": 450.00,
 *   "payable_type": "tour_bookings",
 *   "payable_id": 10
 * }
 * Response: {
 *   "success": true,
 *   "message": "Payment created successfully. Waiting for admin approval.",
 *   "next_step": {
 *     "action": "wait_for_approval"
 *   }
 * }
 *
 * الخطوة 3: Admin يوافق على الدفع
 * POST /api/v1/payments/{payment_id}/approve
 * Response: {
 *   "success": true,
 *   "message": "Payment approved successfully. Booking has been confirmed.",
 *   "booking_updated": true
 * }
 *
 * الآن الـ booking status = approved تلقائياً!
 *
 *
 * OTHER ENDPOINTS:
 * ----------------
 *
 * 1. عرض حجوزاتي:
 *    GET /api/v1/tour-bookings/my-bookings
 *    GET /api/v1/tour-bookings/my-bookings?status=pending
 *
 * 2. عرض جميع الحجوزات (Admin):
 *    GET /api/v1/tour-bookings
 *    GET /api/v1/tour-bookings?status=approved
 *    GET /api/v1/tour-bookings?tour_id=5
 *
 * 3. عرض حجوزات رحلاتي (Guide):
 *    GET /api/v1/tour-bookings (auto-filtered for guide's tours)
 *
 * 4. عرض حجز واحد:
 *    GET /api/v1/tour-bookings/10
 *
 * 5. تحديث عدد المشاركين (Tourist - pending only):
 *    PUT /api/v1/tour-bookings/10
 *    Body: {
 *      "participants_count": 5
 *    }
 *
 * 6. تحديث حالة الحجز (Guide/Admin):
 *    PUT /api/v1/tour-bookings/10
 *    Body: {
 *      "status": "approved"
 *    }
 *
 * 7. إلغاء حجز (Tourist - pending & no approved payment):
 *    DELETE /api/v1/tour-bookings/10
 *
 * 8. الموافقة على حجز (Guide/Admin - with approved payment):
 *    POST /api/v1/tour-bookings/10/approve
 *
 * 9. رفض حجز (Guide/Admin):
 *    POST /api/v1/tour-bookings/10/reject
 *
 * 10. عرض حجوزات رحلة معينة (Guide/Admin):
 *     GET /api/v1/tours/5/bookings
 *
 * 11. إحصائيات الحجوزات:
 *     GET /api/v1/tour-bookings/statistics
 *
 * ═══════════════════════════════════════════════════════════════════════
 */



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('forgot-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');


Route::get('/test-auth', function () {
    return response()->json(['ok' => true]);
});
