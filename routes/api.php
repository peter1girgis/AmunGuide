<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\CommentsController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\LikesController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TourBookingController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\TourController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are loaded by the RouteServiceProvider and assigned to the
| "api" middleware group. Versioned base URL: /api/v1
|
| Conventions applied in this file:
|   - Route ORDER is identical to the original file.
|   - Every {parameter} route has ->where() and ->missing() guards.
|   - All comments and documentation are in English only.
|
*/


/*
|==========================================================================
| Places · Tours · Comments · Likes
| Prefix: v1
|==========================================================================
*/
Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/places/search
    |----------------------------------------------------------------------
    | Description : Search for places by title or keyword.
    | Method      : GET
    | URL         : /api/v1/places/search
    | Auth        : None
    | Query Params: q (string, required)
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('places/search', [PlaceController::class, 'search'])
        ->name('places.search');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/places/trending
    |----------------------------------------------------------------------
    | Description : Return the most-visited or highest-rated places.
    | Method      : GET
    | URL         : /api/v1/places/trending
    | Auth        : None
    | Query Params: limit (integer, optional, default: 10)
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('places/trending', [PlaceController::class, 'trending'])
        ->name('places.trending');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/places/filter
    |----------------------------------------------------------------------
    | Description : Filter places by price range, rating, or category.
    | Method      : GET
    | URL         : /api/v1/places/filter
    | Auth        : None
    | Query Params: min_price, max_price, rating (all optional)
    | Success (200): { "success": true, "data": [...], "pagination": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('places/filter', [PlaceController::class, 'filter'])
        ->name('places.filter');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/places
    |----------------------------------------------------------------------
    | Description : Return a paginated list of all places.
    | Method      : GET
    | URL         : /api/v1/places
    | Auth        : None
    | Query Params: page, per_page, sort (all optional)
    | Success (200): { "success": true, "data": [...], "pagination": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('places', [PlaceController::class, 'index'])
        ->name('places.index');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/places/{place}
    |----------------------------------------------------------------------
    | Description : Retrieve full details of a single place by its ID.
    | Method      : GET
    | URL         : /api/v1/places/{place}
    | Auth        : None
    | URL Params  : place (integer) — numeric place ID
    | Success (200): { "success": true, "data": {...} }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::get('places/{place}', [PlaceController::class, 'show'])
        ->name('places.show')
        ->where(['place' => '[0-9]+'])
        ->missing(function () {
            return response()->json(['message' => 'Record not found'], 404);
        });

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/places
        |----------------------------------------------------------------------
        | Description : Create a new place (admin only).
        | Method      : POST
        | URL         : /api/v1/places
        | Auth        : Required (Bearer Token)
        | Request Body: title (string), description (string), ticket_price (numeric)
        | Success (201): { "success": true, "data": {...} }
        | Error   (422): { "message": "...", "errors": {...} }
        |----------------------------------------------------------------------
        */
        Route::post('places', [PlaceController::class, 'store'])
            ->name('places.store');

        /*
        |----------------------------------------------------------------------
        | PUT /api/v1/places/{place}
        |----------------------------------------------------------------------
        | Description : Update an existing place (admin only).
        | Method      : PUT
        | URL         : /api/v1/places/{place}
        | Auth        : Required (Bearer Token)
        | URL Params  : place (integer) — numeric place ID
        | Request Body: title, description, ticket_price, image (all optional)
        | Success (200): { "success": true, "data": {...} }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::put('places/{place}', [PlaceController::class, 'update'])
            ->name('places.update')
            ->where(['place' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/places/{place}
        |----------------------------------------------------------------------
        | Description : Permanently delete a place (admin only).
        | Method      : DELETE
        | URL         : /api/v1/places/{place}
        | Auth        : Required (Bearer Token)
        | URL Params  : place (integer) — numeric place ID
        | Success (200): { "success": true, "message": "Place deleted." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('places/{place}', [PlaceController::class, 'destroy'])
            ->name('places.destroy')
            ->where(['place' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });
    });


    // =========================================================================
    // PUBLIC TOUR ROUTES (Guest + Authenticated with activity tracking)
    // =========================================================================

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/tours
    |----------------------------------------------------------------------
    | Description : Return a paginated list of active tours. Activity is
    |               tracked automatically when the caller is authenticated.
    | Method      : GET
    | URL         : /api/v1/tours
    | Auth        : None
    | Query Params: guide_id, min_price, max_price, plan_id, sort, per_page
    | Success (200): { "success": true, "data": [...], "pagination": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('tours', [TourController::class, 'index'])
        ->name('tours.index');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/tours/search
    |----------------------------------------------------------------------
    | Description : Search active tours by title or description.
    | Method      : GET
    | URL         : /api/v1/tours/search
    | Auth        : None
    | Query Params: q (string, required, min 3 chars), per_page (optional)
    | Success (200): { "success": true, "data": [...], "pagination": {...} }
    | Error   (400): { "success": false, "message": "Query too short." }
    |----------------------------------------------------------------------
    */
    Route::get('tours/search', [TourController::class, 'search'])
        ->name('tours.search');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/tours/filter
    |----------------------------------------------------------------------
    | Description : Filter active tours by price, guide, start date, or
    |               linked plan. Activity is tracked when authenticated.
    | Method      : GET
    | URL         : /api/v1/tours/filter
    | Auth        : None
    | Query Params: min_price, max_price, guide_id, start_date, plan_id, per_page
    | Success (200): { "success": true, "data": [...], "pagination": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('tours/filter', [TourController::class, 'filter'])
        ->name('tours.filter');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/tours/popular
    |----------------------------------------------------------------------
    | Description : Return the most-booked active tours.
    | Method      : GET
    | URL         : /api/v1/tours/popular
    | Auth        : None
    | Query Params: limit (integer, optional, default: 10)
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('tours/popular', [TourController::class, 'popular'])
        ->name('tours.popular');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/tours/{tour}
    |----------------------------------------------------------------------
    | Description : Retrieve full detail of a single tour including guide,
    |               places, and linked plan. Logs a "visit" activity when
    |               the caller is authenticated.
    | Method      : GET
    | URL         : /api/v1/tours/{tour}
    | Auth        : None (activity tracked when authenticated)
    | URL Params  : tour (integer) — numeric tour ID
    | Success (200): { "success": true, "data": {...} }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::get('tours/{tour}', [TourController::class, 'show'])
        ->name('tours.show')
        ->where(['tour' => '[0-9]+'])
        ->missing(function () {
            return response()->json(['message' => 'Record not found'], 404);
        });

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/tours/guide/{guide_id}
    |----------------------------------------------------------------------
    | Description : List all active tours belonging to a specific guide
    |               (public profile view).
    | Method      : GET
    | URL         : /api/v1/tours/guide/{guide_id}
    | Auth        : None
    | URL Params  : guide_id (integer) — numeric user ID of the guide
    | Success (200): { "success": true, "data": [...], "pagination": {...} }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::get('tours/guide/{guide_id}', [TourController::class, 'getGuideToursPublic'])
        ->name('tours.guide.public')
        ->where(['guide_id' => '[0-9]+'])
        ->missing(function () {
            return response()->json(['message' => 'Record not found'], 404);
        });

    // =========================================================================
    // PROTECTED TOUR ROUTES (Authentication Required)
    // =========================================================================
    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/tours
        |----------------------------------------------------------------------
        | Description : Create a new tour (guide only). Optionally attaches
        |               places in sequence and links to a plan via plan_id.
        | Method      : POST
        | URL         : /api/v1/tours
        | Auth        : Required (Bearer Token — guide role)
        | Request Body: title (required), price (required), description,
        |               start_date, plan_id (nullable), places[] (all optional)
        | Success (201): { "success": true, "message": "...", "data": {...} }
        | Error   (422): { "message": "...", "errors": {...} }
        |----------------------------------------------------------------------
        */
        Route::post('tours', [TourController::class, 'store'])
            ->name('tours.store');

        /*
        |----------------------------------------------------------------------
        | PUT /api/v1/tours/{tour}
        |----------------------------------------------------------------------
        | Description : Update an existing tour. Only the owner guide may update.
        |               Sending plan_id as null detaches the linked plan.
        |               Authorization is enforced inside the Form Request.
        | Method      : PUT
        | URL         : /api/v1/tours/{tour}
        | Auth        : Required (Bearer Token — tour owner)
        | URL Params  : tour (integer) — numeric tour ID
        | Request Body: title, price, description, start_date, plan_id, places[]
        | Success (200): { "success": true, "message": "...", "data": {...} }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::put('tours/{tour}', [TourController::class, 'update'])
            ->name('tours.update')
            ->where(['tour' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/tours/{tour}
        |----------------------------------------------------------------------
        | Description : Permanently delete a tour and detach all linked places.
        |               Authorization is enforced inside the Controller.
        | Method      : DELETE
        | URL         : /api/v1/tours/{tour}
        | Auth        : Required (Bearer Token — tour owner or admin)
        | URL Params  : tour (integer) — numeric tour ID
        | Success (200): { "success": true, "message": "Tour deleted." }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('tours/{tour}', [TourController::class, 'destroy'])
            ->name('tours.destroy')
            ->where(['tour' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/my-tours
        |----------------------------------------------------------------------
        | Description : Return all tours created by the authenticated guide.
        | Method      : GET
        | URL         : /api/v1/my-tours
        | Auth        : Required (Bearer Token — guide role)
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        |----------------------------------------------------------------------
        */
        Route::get('my-tours', [TourController::class, 'myTours'])
            ->name('tours.my-tours');

        // Route::get('tours/{tour_id}/bookings', [TourController::class, 'getTourBookings'])
        //     ->name('tours.bookings');
    });


    // =========================================================================
    // COMMENTS — Public endpoints
    // =========================================================================

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/comments/{commentableType}/{commentableId}
    |----------------------------------------------------------------------
    | Description : Return all comments for a specific resource.
    | Method      : GET
    | URL         : /api/v1/comments/{commentableType}/{commentableId}
    | Auth        : None
    | URL Params  : commentableType (tours|places|plans),
    |               commentableId (integer) — numeric resource ID
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('comments/{commentableType}/{commentableId}',
        [CommentsController::class, 'index'])
        ->name('comments.index')
        ->where(['commentableType' => 'tours|places|plans', 'commentableId' => '[0-9]+']);

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/{commentableType}/{commentableId}/comments
    |----------------------------------------------------------------------
    | Description : Alternate URL pattern to retrieve comments for a resource
    |               (identical behaviour to the route above).
    | Method      : GET
    | URL         : /api/v1/{commentableType}/{commentableId}/comments
    | Auth        : None
    | URL Params  : commentableType (tours|places|plans),
    |               commentableId (integer) — numeric resource ID
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('{commentableType}/{commentableId}/comments',
        [CommentsController::class, 'index'])
        ->where(['commentableType' => 'tours|places|plans', 'commentableId' => '[0-9]+']);

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/{commentableType}/{commentableId}/comments/count
    |----------------------------------------------------------------------
    | Description : Return the total comment count for a given resource.
    | Method      : GET
    | URL         : /api/v1/{commentableType}/{commentableId}/comments/count
    | Auth        : None
    | URL Params  : commentableType (tours|places|plans),
    |               commentableId (integer) — numeric resource ID
    | Success (200): { "success": true, "count": 42 }
    |----------------------------------------------------------------------
    */
    Route::get('{commentableType}/{commentableId}/comments/count',
        [CommentsController::class, 'count'])
        ->where(['commentableType' => 'tours|places|plans', 'commentableId' => '[0-9]+']);

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/comments/{comment}
    |----------------------------------------------------------------------
    | Description : Retrieve a single comment by its ID.
    | Method      : GET
    | URL         : /api/v1/comments/{comment}
    | Auth        : None
    | URL Params  : comment (integer) — numeric comment ID
    | Success (200): { "success": true, "data": {...} }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::get('comments/{comment}', [CommentsController::class, 'show'])
        ->name('comments.show')
        ->where(['comment' => '[0-9]+'])
        ->missing(function () {
            return response()->json(['message' => 'Record not found'], 404);
        });

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/user/{userId}/comments
    |----------------------------------------------------------------------
    | Description : Return all comments authored by a specific user.
    | Method      : GET
    | URL         : /api/v1/user/{userId}/comments
    | Auth        : None
    | URL Params  : userId (integer) — numeric user ID
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('user/{userId}/comments', [CommentsController::class, 'userComments'])
        ->name('comments.user')
        ->where(['userId' => '[0-9]+']);

    // -- Protected comment endpoints (auth required) -----------------------
    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/comments
        |----------------------------------------------------------------------
        | Description : Add a new comment to any supported resource.
        | Method      : POST
        | URL         : /api/v1/comments
        | Auth        : Required (Bearer Token)
        | Request Body: content (string, required),
        |               commentable_type (tours|places|plans, required),
        |               commentable_id (integer, required)
        | Success (201): { "success": true, "data": {...} }
        | Error   (422): { "message": "...", "errors": {...} }
        |----------------------------------------------------------------------
        */
        Route::post('comments', [CommentsController::class, 'store'])
            ->name('comments.store');

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/{commentableType}/{commentableId}/comments
        |----------------------------------------------------------------------
        | Description : Alternate URL — add a comment directly on a resource
        |               without specifying commentable fields in the body.
        | Method      : POST
        | URL         : /api/v1/{commentableType}/{commentableId}/comments
        | Auth        : Required (Bearer Token)
        | URL Params  : commentableType (tours|places|plans),
        |               commentableId (integer) — numeric resource ID
        | Request Body: content (string, required)
        | Success (201): { "success": true, "data": {...} }
        |----------------------------------------------------------------------
        */
        Route::post('{commentableType}/{commentableId}/comments',
            [CommentsController::class, 'storeOnResource'])
            ->where(['commentableType' => 'tours|places|plans', 'commentableId' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | PUT /api/v1/comments/{id}
        |----------------------------------------------------------------------
        | Description : Update the content of a comment (owner only).
        | Method      : PUT
        | URL         : /api/v1/comments/{id}
        | Auth        : Required (Bearer Token — comment owner)
        | URL Params  : id (integer) — numeric comment ID
        | Request Body: content (string, required)
        | Success (200): { "success": true, "data": {...} }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        |----------------------------------------------------------------------
        */
        Route::put('comments/{id}', [CommentsController::class, 'update'])
            ->name('comments.update')
            ->where(['id' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/comments/{comment}
        |----------------------------------------------------------------------
        | Description : Delete a comment (owner or admin only).
        | Method      : DELETE
        | URL         : /api/v1/comments/{comment}
        | Auth        : Required (Bearer Token — owner or admin)
        | URL Params  : comment (integer) — numeric comment ID
        | Success (200): { "success": true, "message": "Comment deleted." }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('comments/{comment}', [CommentsController::class, 'destroy'])
            ->name('comments.destroy')
            ->where(['comment' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });
    });


    // =========================================================================
    // LIKES — Public endpoints
    // =========================================================================

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/{likeableType}/{likeableId}/likes
    |----------------------------------------------------------------------
    | Description : Return all likes for a given resource.
    | Method      : GET
    | URL         : /api/v1/{likeableType}/{likeableId}/likes
    | Auth        : None
    | URL Params  : likeableType (tours|places|plans),
    |               likeableId (integer) — numeric resource ID
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */
    Route::get('{likeableType}/{likeableId}/likes',
        [LikesController::class, 'index'])
        ->where(['likeableType' => 'tours|places|plans', 'likeableId' => '[0-9]+']);

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/{likeableType}/{likeableId}/likes/count
    |----------------------------------------------------------------------
    | Description : Return the total like count for a resource, and whether
    |               the authenticated user has already liked it.
    | Method      : GET
    | URL         : /api/v1/{likeableType}/{likeableId}/likes/count
    | Auth        : None (liked_by_me only populated when authenticated)
    | URL Params  : likeableType (tours|places|plans),
    |               likeableId (integer) — numeric resource ID
    | Success (200): { "success": true, "count": 14, "liked_by_me": false }
    |----------------------------------------------------------------------
    */
    Route::get('{likeableType}/{likeableId}/likes/count',
        [LikesController::class, 'count'])
        ->where(['likeableType' => 'tours|places|plans', 'likeableId' => '[0-9]+']);

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/user/{userId}/likes
    |----------------------------------------------------------------------
    | Description : Return all likes given by a specific user.
    | Method      : GET
    | URL         : /api/v1/user/{userId}/likes
    | Auth        : None
    | URL Params  : userId (integer) — numeric user ID
    | Success (200): { "success": true, "data": [...] }
    |----------------------------------------------------------------------
    */


    // -- Protected like endpoints (auth required) --------------------------
    Route::middleware('auth:sanctum')->group(function () {

    Route::get('user/likes', [LikesController::class, 'userLikes'])
            ->name('likes.user');
        /*
        |----------------------------------------------------------------------
        | POST /api/v1/likes
        |----------------------------------------------------------------------
        | Description : Add a like on a resource.
        | Method      : POST
        | URL         : /api/v1/likes
        | Auth        : Required (Bearer Token)
        | Request Body: likeable_type (tours|places|plans), likeable_id (integer)
        | Success (201): { "success": true, "data": {...} }
        | Error   (409): { "success": false, "message": "Already liked." }
        |----------------------------------------------------------------------
        */
        Route::post('likes', [LikesController::class, 'store'])
            ->name('likes.store');

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/likes/toggle
        |----------------------------------------------------------------------
        | Description : Toggle a like — adds if absent, removes if present.
        |               Preferred for frontend use.
        | Method      : POST
        | URL         : /api/v1/likes/toggle
        | Auth        : Required (Bearer Token)
        | Request Body: likeable_type (tours|places|plans), likeable_id (integer)
        | Success (200): { "success": true, "liked": true, "count": 15 }
        |----------------------------------------------------------------------
        */
        Route::post('likes/toggle', [LikesController::class, 'toggle'])
            ->name('likes.toggle');

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/likes/{like}
        |----------------------------------------------------------------------
        | Description : Remove a like by its own record ID (owner only).
        | Method      : DELETE
        | URL         : /api/v1/likes/{like}
        | Auth        : Required (Bearer Token — like owner)
        | URL Params  : like (integer) — numeric like ID
        | Success (200): { "success": true, "message": "Like removed." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('likes/{like}', [LikesController::class, 'destroy'])
            ->name('likes.destroy')
            ->where(['like' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });
    });

    /*
    |----------------------------------------------------------------------
    | DELETE /api/v1/{likeableType}/{likeableId}/likes
    |----------------------------------------------------------------------
    | Description : Remove the authenticated user's like from a resource
    |               via the resource URL (no like record ID needed).
    | Method      : DELETE
    | URL         : /api/v1/{likeableType}/{likeableId}/likes
    | Auth        : Required (Bearer Token)
    | URL Params  : likeableType (tours|places|plans),
    |               likeableId (integer) — numeric resource ID
    | Success (200): { "success": true, "message": "Like removed." }
    |----------------------------------------------------------------------
    */
    Route::delete('{likeableType}/{likeableId}/likes',
        [LikesController::class, 'removeFromResource'])
        ->where(['likeableType' => 'tours|places|plans', 'likeableId' => '[0-9]+']);

}); // end prefix('v1')


/*
|==========================================================================
| Analysis
| Prefix: v1/analysis
|==========================================================================
*/
Route::prefix('v1/analysis')->group(function () {

    /*
    |----------------------------------------------------------------------
    | POST /api/v1/analysis/user_activity
    |----------------------------------------------------------------------
    | Description : Return activity analysis data for the authenticated user.
    | Method      : POST
    | URL         : /api/v1/analysis/user_activity
    | Auth        : Required (Bearer Token)
    | Success (200): { "success": true, "data": {...} }
    |----------------------------------------------------------------------
    */
    Route::post('/user_activity', [AnalysisController::class, 'getMyData'])
        ->name('analysis.user.single');

    /*
    |----------------------------------------------------------------------
    | GET /api/v1/analysis/users-all
    |----------------------------------------------------------------------
    | Description : Return aggregated activity analysis for all users
    |               (admin only).
    | Method      : GET
    | URL         : /api/v1/analysis/users-all
    | Auth        : Required (Bearer Token — admin role)
    | Success (200): { "success": true, "data": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('/users-all', [AnalysisController::class, 'getAllUsersData'])
        ->name('analysis.users.global');

}); // end prefix('v1/analysis')


/*
|--------------------------------------------------------------------------
| General API Notes
|--------------------------------------------------------------------------
|
| 1. Route Order: Static routes (e.g. /places/search) must always be
|    registered before dynamic routes (e.g. /places/{id}) within the same
|    prefix group so Laravel does not treat keywords as IDs.
|
| 2. Auth Middleware:
|    - Public endpoints   : no middleware
|    - Protected endpoints: middleware('auth:sanctum')
|
| 3. Authorization: Role checks (admin / guide / owner) are enforced
|    inside the relevant Form Request or Controller method.
|
| 4. Rate Limiting (optional): append ->middleware('throttle:60,1').
|
| 5. Pagination defaults: page=1, per_page=15 (max 100).
|
*/


/*
|==========================================================================
| Payments
| Prefix: v1
| All payment routes require authentication. No public endpoints exist.
| Note: route::middleware('role:admin') is commented out below —
|       uncomment that wrapper when the role middleware is available.
|==========================================================================
*/
Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/payments/my-payments
        |----------------------------------------------------------------------
        | Description : List all payments created by the authenticated user.
        | Method      : GET
        | URL         : /api/v1/payments/my-payments
        | Auth        : Required (Bearer Token)
        | Query Params: status (pending|approved|failed), per_page (optional)
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        |----------------------------------------------------------------------
        */
        Route::get('payments/my-payments', [PaymentController::class, 'myPayments'])
            ->name('payments.my-payments');

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/payments
        |----------------------------------------------------------------------
        | Description : Create a new payment record linked to a payable resource.
        | Method      : POST
        | URL         : /api/v1/payments
        | Auth        : Required (Bearer Token)
        | Request Body: amount (numeric, required), payable_type (string, required),
        |               payable_id (integer, required), payment_method,
        |               transaction_id, receipt_image, notes (all optional)
        | Success (201): { "success": true, "message": "...", "data": {...} }
        |----------------------------------------------------------------------
        */
        Route::post('payments', [PaymentController::class, 'store'])
            ->name('payments.store');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/payments/{id}
        |----------------------------------------------------------------------
        | Description : Retrieve a single payment record by its ID.
        | Method      : GET
        | URL         : /api/v1/payments/{id}
        | Auth        : Required (Bearer Token)
        | URL Params  : id (integer) — numeric payment ID
        | Success (200): { "success": true, "data": {...} }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::get('payments/{id}', [PaymentController::class, 'show'])
            ->name('payments.show')
            ->where(['id' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | PUT|PATCH /api/v1/payments/{payment}
        |----------------------------------------------------------------------
        | Description : Update a payment record (admin only).
        | Method      : PUT or PATCH
        | URL         : /api/v1/payments/{payment}
        | Auth        : Required (Bearer Token — admin role)
        | URL Params  : payment (integer) — numeric payment ID
        | Request Body: status (approved|failed|pending), amount (optional)
        | Success (200): { "success": true, "data": {...} }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::match(['put', 'patch'], 'payments/{payment}', [PaymentController::class, 'update'])
            ->name('payments.update')
            ->where(['payment' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/payments/{id}
        |----------------------------------------------------------------------
        | Description : Delete a payment record (admin only).
        | Method      : DELETE
        | URL         : /api/v1/payments/{id}
        | Auth        : Required (Bearer Token — admin role)
        | URL Params  : id (integer) — numeric payment ID
        | Success (200): { "success": true, "message": "Payment deleted." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('payments/{id}', [PaymentController::class, 'destroy'])
            ->name('payments.destroy')
            ->where(['id' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        // -- Admin-only routes (uncomment middleware wrapper when ready) ----
        // Route::middleware('role:admin')->group(function () {

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/payments
        |----------------------------------------------------------------------
        | Description : List all payments with optional filters (admin only).
        | Method      : GET
        | URL         : /api/v1/payments
        | Auth        : Required (Bearer Token — admin role)
        | Query Params: status, payable_type, user_id, from_date, to_date, per_page
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        |----------------------------------------------------------------------
        */
        Route::get('payments', [PaymentController::class, 'index'])
            ->name('payments.index');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/payments/statistics
        |----------------------------------------------------------------------
        | Description : Return aggregated payment statistics (admin only).
        | Method      : GET
        | URL         : /api/v1/payments/statistics
        | Auth        : Required (Bearer Token — admin role)
        | Success (200): { "success": true, "data": { "total": 0, ... } }
        |----------------------------------------------------------------------
        */
        Route::get('payments/statistics', [PaymentController::class, 'statistics'])
            ->name('payments.statistics');

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/payments/{id}/approve
        |----------------------------------------------------------------------
        | Description : Approve a pending payment. Automatically updates the
        |               linked booking status to "approved" (admin only).
        | Method      : POST
        | URL         : /api/v1/payments/{id}/approve
        | Auth        : Required (Bearer Token — admin role)
        | URL Params  : id (integer) — numeric payment ID
        | Success (200): { "success": true, "message": "...", "booking_updated": true }
        |----------------------------------------------------------------------
        */
        Route::post('payments/{id}/approve', [PaymentController::class, 'approve'])
            ->name('payments.approve')
            ->where(['id' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/payments/{id}/reject
        |----------------------------------------------------------------------
        | Description : Reject a pending payment (admin only).
        | Method      : POST
        | URL         : /api/v1/payments/{id}/reject
        | Auth        : Required (Bearer Token — admin role)
        | URL Params  : id (integer) — numeric payment ID
        | Success (200): { "success": true, "message": "Payment rejected." }
        |----------------------------------------------------------------------
        */
        Route::post('payments/{id}/reject', [PaymentController::class, 'reject'])
            ->name('payments.reject')
            ->where(['id' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/payments/bulk-approve
        |----------------------------------------------------------------------
        | Description : Approve multiple payments in a single request (admin only).
        | Method      : POST
        | URL         : /api/v1/payments/bulk-approve
        | Auth        : Required (Bearer Token — admin role)
        | Request Body: payment_ids (array of integers, required)
        | Success (200): { "success": true, "approved_count": 3 }
        |----------------------------------------------------------------------
        */
        Route::post('payments/bulk-approve', [PaymentController::class, 'bulkApprove'])
            ->name('payments.bulk-approve');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/users/{userId}/payments
        |----------------------------------------------------------------------
        | Description : Return all payments made by a specific user (admin only).
        | Method      : GET
        | URL         : /api/v1/users/{userId}/payments
        | Auth        : Required (Bearer Token — admin role)
        | URL Params  : userId (integer) — numeric user ID
        | Success (200): { "success": true, "data": [...] }
        |----------------------------------------------------------------------
        */
        Route::get('users/{userId}/payments', [PaymentController::class, 'userPayments'])
            ->name('users.payments')
            ->where(['userId' => '[0-9]+']);

        // }); // end role:admin group
    });

}); // end prefix('v1') — Payments


/*
|==========================================================================
| Tour Bookings
| Prefix: v1
|
| Complete booking lifecycle:
|   1. Tourist creates booking → POST /tour-bookings          (status: pending)
|   2. Tourist pays            → POST /payments
|   3. Admin approves payment  → POST /payments/{id}/approve
|      booking status is automatically updated to "approved"
|==========================================================================
*/
Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/tour-bookings/my-bookings
        |----------------------------------------------------------------------
        | Description : Return all bookings belonging to the authenticated tourist.
        | Method      : GET
        | URL         : /api/v1/tour-bookings/my-bookings
        | Auth        : Required (Bearer Token — tourist role)
        | Query Params: status (pending|approved|cancelled), per_page (optional)
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        |----------------------------------------------------------------------
        */
        Route::get('tour-bookings/my-bookings', [TourBookingController::class, 'myBookings'])
            ->name('tour-bookings.my-bookings');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/tour-bookings/statistics
        |----------------------------------------------------------------------
        | Description : Return booking statistics scoped to the caller's role:
        |               admin = all, guide = own tours, tourist = own bookings.
        | Method      : GET
        | URL         : /api/v1/tour-bookings/statistics
        | Auth        : Required (Bearer Token)
        | Success (200): { "success": true, "data": { "total": 0, ... } }
        |----------------------------------------------------------------------
        */
        Route::get('tour-bookings/statistics', [TourBookingController::class, 'statistics'])
            ->name('tour-bookings.statistics');

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/tour-bookings
        |----------------------------------------------------------------------
        | Description : Create a new tour booking (tourist only). Booking is
        |               created with status "pending".
        | Method      : POST
        | URL         : /api/v1/tour-bookings
        | Auth        : Required (Bearer Token — tourist role)
        | Request Body: tour_id (integer, required),
        |               participants_count (integer, required, min: 1)
        | Success (201): { "success": true, "data": {...},
        |                  "next_step": { "action": "create_payment", ... } }
        | Error   (422): { "message": "...", "errors": {...} }
        |----------------------------------------------------------------------
        */
        Route::post('tour-bookings', [TourBookingController::class, 'store'])
            ->name('tour-bookings.store');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/tour-bookings
        |----------------------------------------------------------------------
        | Description : List bookings filtered automatically by caller's role:
        |               admin = all, guide = own tour bookings, tourist = own.
        | Method      : GET
        | URL         : /api/v1/tour-bookings
        | Auth        : Required (Bearer Token)
        | Query Params: status, tour_id, per_page (all optional)
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        |----------------------------------------------------------------------
        */
        Route::get('tour-bookings', [TourBookingController::class, 'index'])
            ->name('tour-bookings.index');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/tour-bookings/{id}
        |----------------------------------------------------------------------
        | Description : Retrieve details of a single booking. Owner, guide,
        |               or admin may access.
        | Method      : GET
        | URL         : /api/v1/tour-bookings/{id}
        | Auth        : Required (Bearer Token)
        | URL Params  : id (integer) — numeric booking ID
        | Success (200): { "success": true, "data": {...} }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::get('tour-bookings/{id}', [TourBookingController::class, 'show'])
            ->name('tour-bookings.show')
            ->where(['id' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | PUT|PATCH /api/v1/tour-bookings/{booking}
        |----------------------------------------------------------------------
        | Description : Update a booking. Tourist may change participants_count
        |               (pending status only). Guide/admin may update status.
        | Method      : PUT or PATCH
        | URL         : /api/v1/tour-bookings/{booking}
        | Auth        : Required (Bearer Token)
        | URL Params  : booking (integer) — numeric booking ID
        | Request Body (tourist)     : participants_count (integer)
        | Request Body (guide/admin) : status (approved|cancelled)
        | Success (200): { "success": true, "data": {...} }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::match(['put', 'patch'], 'tour-bookings/{booking}', [TourBookingController::class, 'update'])
            ->name('tour-bookings.update')
            ->where(['booking' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/tour-bookings/{id}
        |----------------------------------------------------------------------
        | Description : Cancel a booking. Tourist only, pending status, and
        |               no approved payment must exist.
        | Method      : DELETE
        | URL         : /api/v1/tour-bookings/{id}
        | Auth        : Required (Bearer Token — tourist, pending status only)
        | URL Params  : id (integer) — numeric booking ID
        | Success (200): { "success": true, "message": "Booking cancelled." }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('tour-bookings/{id}', [TourBookingController::class, 'destroy'])
            ->name('tour-bookings.destroy')
            ->where(['id' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/tours/{tourId}/bookings
        |----------------------------------------------------------------------
        | Description : List all bookings for a specific tour (guide owner or
        |               admin only).
        | Method      : GET
        | URL         : /api/v1/tours/{tourId}/bookings
        | Auth        : Required (Bearer Token — guide owner or admin)
        | URL Params  : tourId (integer) — numeric tour ID
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        |----------------------------------------------------------------------
        */
        Route::get('tours/{tourId}/bookings', [TourBookingController::class, 'tourBookings'])
            ->name('tours.bookings')
            ->where(['tourId' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/tour-bookings/{id}/approve
        |----------------------------------------------------------------------
        | Description : Approve a booking once payment is confirmed
        |               (guide owner or admin only).
        | Method      : POST
        | URL         : /api/v1/tour-bookings/{id}/approve
        | Auth        : Required (Bearer Token — guide or admin)
        | URL Params  : id (integer) — numeric booking ID
        | Success (200): { "success": true, "message": "Booking approved." }
        | Error   (403): { "success": false, "message": "Unauthorized." }
        |----------------------------------------------------------------------
        */
        Route::post('tour-bookings/{id}/approve', [TourBookingController::class, 'approve'])
            ->name('tour-bookings.approve')
            ->where(['id' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/tour-bookings/{id}/reject
        |----------------------------------------------------------------------
        | Description : Reject a booking (guide owner or admin only).
        | Method      : POST
        | URL         : /api/v1/tour-bookings/{id}/reject
        | Auth        : Required (Bearer Token — guide or admin)
        | URL Params  : id (integer) — numeric booking ID
        | Success (200): { "success": true, "message": "Booking rejected." }
        |----------------------------------------------------------------------
        */
        Route::post('tour-bookings/{id}/reject', [TourBookingController::class, 'reject'])
            ->name('tour-bookings.reject')
            ->where(['id' => '[0-9]+']);
    });

}); // end prefix('v1') — Tour Bookings


/*
|==========================================================================
| Chatbot Conversations
| Prefix: v1
|
| Complete conversation lifecycle:
|   1. Start conversation  → POST /conversations
|   2. Exchange messages   → POST /conversations/{id}/messages
|   3. Bot generates image → auto-stored in generated_images
|   4. View conversation   → GET  /conversations/{id}
|   5. Delete conversation → DELETE /conversations/{id} (cascade)
|==========================================================================
*/
Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/conversations
        |----------------------------------------------------------------------
        | Description : Start a new chatbot conversation session.
        | Method      : POST
        | URL         : /api/v1/conversations
        | Auth        : Required (Bearer Token)
        | Request Body: context (string, optional) —
        |               image_generation | travel_plan | info_request |
        |               general | place_inquiry | tour_inquiry
        | Success (201): { "success": true, "data": { "id": 1, "context": "..." } }
        |----------------------------------------------------------------------
        */
        Route::post('conversations', [ConversationController::class, 'store'])
            ->name('conversations.store');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/conversations
        |----------------------------------------------------------------------
        | Description : List all chatbot conversations for the authenticated user.
        | Method      : GET
        | URL         : /api/v1/conversations
        | Auth        : Required (Bearer Token)
        | Query Params: context (optional), with_images (boolean), per_page (optional)
        | Success (200): { "success": true, "data": [...], "pagination": {...} }
        |----------------------------------------------------------------------
        */
        Route::get('conversations', [ConversationController::class, 'index'])
            ->name('conversations.index');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/conversations/statistics
        |----------------------------------------------------------------------
        | Description : Return aggregated statistics about the authenticated
        |               user's chatbot conversations.
        | Method      : GET
        | URL         : /api/v1/conversations/statistics
        | Auth        : Required (Bearer Token)
        | Success (200): { "success": true, "data": { "total": 0, "by_context": {...} } }
        |----------------------------------------------------------------------
        */
        Route::get('conversations/statistics', [ConversationController::class, 'statistics'])
            ->name('conversations.statistics');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/conversations/{id}
        |----------------------------------------------------------------------
        | Description : Retrieve a single conversation with all its messages
        |               and generated images (owner only).
        | Method      : GET
        | URL         : /api/v1/conversations/{id}
        | Auth        : Required (Bearer Token — conversation owner)
        | URL Params  : id (integer) — numeric conversation ID
        | Success (200): { "success": true, "data": { "messages": [...], ... } }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::get('conversations/{id}', [ConversationController::class, 'show'])
            ->name('conversations.show')
            ->where(['id' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | DELETE /api/v1/conversations/{id}
        |----------------------------------------------------------------------
        | Description : Delete a conversation and cascade-delete all its
        |               messages and generated images (owner only).
        | Method      : DELETE
        | URL         : /api/v1/conversations/{id}
        | Auth        : Required (Bearer Token — conversation owner)
        | URL Params  : id (integer) — numeric conversation ID
        | Success (200): { "success": true, "message": "Conversation deleted." }
        | Error   (404): { "message": "Record not found" }
        |----------------------------------------------------------------------
        */
        Route::delete('conversations/{id}', [ConversationController::class, 'destroy'])
            ->name('conversations.destroy')
            ->where(['id' => '[0-9]+'])
            ->missing(function () {
                return response()->json(['message' => 'Record not found'], 404);
            });

        /*
        |----------------------------------------------------------------------
        | POST /api/v1/conversations/{id}/messages
        |----------------------------------------------------------------------
        | Description : Send a message within an existing conversation. When
        |               sender="bot" and image_url is provided, the image is
        |               automatically saved to generated_images.
        | Method      : POST
        | URL         : /api/v1/conversations/{id}/messages
        | Auth        : Required (Bearer Token)
        | URL Params  : id (integer) — numeric conversation ID
        | Request Body: sender (user|bot, required), message (string, required),
        |               image_url (string, optional), place_id (integer, optional)
        | Success (201): { "success": true, "data": { "message": {...},
        |                  "generated_image": {...} } }
        |----------------------------------------------------------------------
        */
        Route::post('conversations/{id}/messages', [ConversationController::class, 'storeMessage'])
            ->where(['id' => '[0-9]+'])
            ->name('conversations.messages.store');

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/conversations/{id}/messages
        |----------------------------------------------------------------------
        | Description : Return all messages in a specific conversation.
        | Method      : GET
        | URL         : /api/v1/conversations/{id}/messages
        | Auth        : Required (Bearer Token — conversation owner)
        | URL Params  : id (integer) — numeric conversation ID
        | Success (200): { "success": true, "data": [...] }
        |----------------------------------------------------------------------
        */
        Route::get('conversations/{id}/messages', [ConversationController::class, 'getMessages'])
            ->name('conversations.messages.index')
            ->where(['id' => '[0-9]+']);

        /*
        |----------------------------------------------------------------------
        | GET /api/v1/conversations/{id}/images
        |----------------------------------------------------------------------
        | Description : Return all AI-generated images produced within a
        |               specific conversation.
        | Method      : GET
        | URL         : /api/v1/conversations/{id}/images
        | Auth        : Required (Bearer Token — conversation owner)
        | URL Params  : id (integer) — numeric conversation ID
        | Success (200): { "success": true, "data": [...] }
        |----------------------------------------------------------------------
        */
        Route::get('conversations/{id}/images', [ConversationController::class, 'getImages'])
            ->name('conversations.images.index')
            ->where(['id' => '[0-9]+']);
    });

}); // end prefix('v1') — Chatbot Conversations


/*
|==========================================================================
| Plan Management
| No v1 prefix — base URL: /api   (preserved from original file)
| All routes protected by auth:sanctum.
|==========================================================================
*/
Route::middleware('auth:sanctum')->group(function (): void {

    /*
    |----------------------------------------------------------------------
    | GET /api/plans/my
    |----------------------------------------------------------------------
    | Description : List only the authenticated user's own plans.
    |               Uses the forUser + newest model scopes internally.
    | Method      : GET
    | URL         : /api/plans/my
    | Auth        : Required (Bearer Token)
    | Success (200): { "data": [ PlanResource, ... ], "links": {...}, "meta": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('plans/my', [PlanController::class, 'myPlans'])
        ->name('plans.my');

    /*
    |----------------------------------------------------------------------
    | GET /api/plans
    |----------------------------------------------------------------------
    | Description : Paginated list of plans with optional filters.
    |               Uses scopes: searchByTitle, forUser, withPlaces,
    |               withinBudget, newest.
    | Method      : GET
    | URL         : /api/plans
    | Auth        : Required (Bearer Token)
    | Query Params: search (string), user_id (integer), with_places (boolean),
    |               budget (numeric) — all optional
    | Success (200): { "data": [ PlanResource, ... ], "links": {...}, "meta": {...} }
    |----------------------------------------------------------------------
    */
    Route::get('plans', [PlanController::class, 'index'])
        ->name('plans.index');

    /*
    |----------------------------------------------------------------------
    | POST /api/plans
    |----------------------------------------------------------------------
    | Description : Create a new plan (and optional plan_items) for the
    |               authenticated user. Logs a plan_creation activity.
    | Method      : POST
    | URL         : /api/plans
    | Auth        : Required (Bearer Token)
    | Request Body: title (string, required, max: 255),
    |               plan_items[].place_id (integer, required),
    |               plan_items[].day_index (integer 1-365, optional)
    | Success (201): { "data": { "id": 1, "title": "...", "total_price": 150.00,
    |                 "total_days": 2, "is_complete": true, "summary": "...",
    |                 "user": {...}, "places": [...],
    |                 "created_at": "...", "updated_at": "..." } }
    | Error   (422): { "message": "...", "errors": {...} }
    |----------------------------------------------------------------------
    */
    Route::post('plans', [PlanController::class, 'store'])
        ->name('plans.store');

    /*
    |----------------------------------------------------------------------
    | GET /api/plans/{plan}
    |----------------------------------------------------------------------
    | Description : Retrieve a single plan with all relationships and
    |               computed fields: total_price, total_days, is_complete,
    |               summary.
    | Method      : GET
    | URL         : /api/plans/{plan}
    | Auth        : Required (Bearer Token)
    | URL Params  : plan (integer) — numeric plan ID
    | Success (200): { "data": PlanResource }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::get('plans/{plan}', [PlanController::class, 'show'])
        ->name('plans.show')
        ->where(['plan' => '[0-9]+'])
        ->missing(function () {
            return response()->json(['message' => 'Record not found'], 404);
        });

    /*
    |----------------------------------------------------------------------
    | PUT|PATCH /api/plans/{plan}
    |----------------------------------------------------------------------
    | Description : Update a plan's title and/or replace its plan_items.
    |               Only the plan's owner is authorised.
    |               Logs a plan_creation activity on success.
    | Method      : PUT or PATCH
    | URL         : /api/plans/{plan}
    | Auth        : Required (Bearer Token — must be plan owner)
    | URL Params  : plan (integer) — numeric plan ID
    | Request Body: title (string, optional),
    |               plan_items[].place_id (integer, required if sent),
    |               plan_items[].day_index (integer 1-365, optional)
    | Success (200): { "data": PlanResource }
    | Error   (403): { "message": "This action is unauthorized." }
    | Error   (422): { "message": "...", "errors": {...} }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::match(['put', 'patch'], 'plans/{plan}', [PlanController::class, 'update'])
        ->where(['plan' => '[0-9]+'])
        ->missing(function (Request $request) {
            return response()->json(['message' => 'Record not found'], 404);
        })
        ->name('plans.update');

    /*
    |----------------------------------------------------------------------
    | DELETE /api/plans/{plan}
    |----------------------------------------------------------------------
    | Description : Permanently delete a plan and its plan_items.
    |               Cascade is handled at DB level. Owner only.
    | Method      : DELETE
    | URL         : /api/plans/{plan}
    | Auth        : Required (Bearer Token — must be plan owner)
    | URL Params  : plan (integer) — numeric plan ID
    | Success (204): (empty body)
    | Error   (403): { "message": "You are not allowed to delete this plan." }
    | Error   (404): { "message": "Record not found" }
    |----------------------------------------------------------------------
    */
    Route::delete('plans/{plan}', [PlanController::class, 'destroy'])
        ->name('plans.destroy')
        ->where(['plan' => '[0-9]+'])
        ->missing(function () {
            return response()->json(['message' => 'Record not found'], 404);
        });

}); // end auth:sanctum — Plans


/*
|==========================================================================
| Miscellaneous / Auth Routes — no prefix
|==========================================================================
*/

/*
|----------------------------------------------------------------------
| GET /api/user
|----------------------------------------------------------------------
| Description : Return the currently authenticated user's profile.
| Method      : GET
| URL         : /api/user
| Auth        : Required (Bearer Token)
| Success (200): { "id": 1, "name": "...", "email": "..." }
| Error   (401): { "message": "Unauthenticated." }
|----------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|----------------------------------------------------------------------
| POST /api/login
|----------------------------------------------------------------------
| Description : Authenticate a user and return a Sanctum API token.
| Method      : POST
| URL         : /api/login
| Auth        : None
| Request Body: email (string, required), password (string, required)
| Success (200): { "token": "...", "user": { "id": 1, ... } }
| Error   (401): { "message": "Invalid credentials." }
|----------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->name('login');

/*
|----------------------------------------------------------------------
| POST /api/register
|----------------------------------------------------------------------
| Description : Register a new user account.
| Method      : POST
| URL         : /api/register
| Auth        : None
| Request Body: name (string), email (string, unique), password (string, min:8)
| Success (201): { "token": "...", "user": { "id": 1, ... } }
| Error   (422): { "message": "...", "errors": {...} }
|----------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register'])->name('register');

/*
|----------------------------------------------------------------------
| POST /api/forgot-password
|----------------------------------------------------------------------
| Description : Send a password-reset link to the given email address.
| Method      : POST
| URL         : /api/forgot-password
| Auth        : None
| Request Body: email (string, required)
| Success (200): { "message": "Reset link sent." }
| Error   (422): { "message": "...", "errors": {...} }
|----------------------------------------------------------------------
*/
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('forgot-password');

/*
|----------------------------------------------------------------------
| POST /api/reset-password
|----------------------------------------------------------------------
| Description : Reset a user's password using the emailed token.
| Method      : POST
| URL         : /api/reset-password
| Auth        : None
| Request Body: token (string), email (string), password (string, min:8)
| Success (200): { "message": "Password reset successfully." }
| Error   (422): { "message": "Invalid or expired token." }
|----------------------------------------------------------------------
*/
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

/*
|----------------------------------------------------------------------
| GET /api/test-auth
|----------------------------------------------------------------------
| Description : Lightweight connectivity check — always returns ok.
| Method      : GET
| URL         : /api/test-auth
| Auth        : None
| Success (200): { "ok": true }
|----------------------------------------------------------------------
*/
Route::get('/test-auth', function () {
    return response()->json(['ok' => true]);
});
