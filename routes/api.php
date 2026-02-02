<?php

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
