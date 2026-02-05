<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\CommentsController;
use App\Http\Controllers\Api\LikesController;
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
| be assigned to the "api" middleware group. Make something great!
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
        Route::get('tours/{tour_id}/bookings', [TourController::class, 'getTourBookings'])
            ->name('tours.bookings');
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
        Route::put('comments/{comment}', [CommentsController::class, 'update'])
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
