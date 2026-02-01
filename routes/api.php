<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlaceController;
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
    Route::get('places/{place}', [PlaceController::class, 'show'])->name('places.show');


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('places', [PlaceController::class, 'store'])->name('places.store');
        Route::put('places/{place}', [PlaceController::class, 'update'])->name('places.update');
        Route::delete('places/{place}', [PlaceController::class, 'destroy'])->name('places.destroy');
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
