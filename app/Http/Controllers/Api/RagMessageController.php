<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RagDataRequest;
use App\Http\Resources\RagDataResource;
use App\Models\Places;
use App\Models\Tours;
use App\Models\Plans;
use Illuminate\Http\JsonResponse;

/**
 * RagMessageController - RAG Context Data Endpoint
 *
 * Scenario:
 * The RAG AI needs a full snapshot of the project data to answer user queries.
 * This endpoint collects and returns that snapshot in a structured format:
 *   - All places (with comments + likes)
 *   - All tours (with their places + linked plan)
 *   - All plans in the system
 *   - Authenticated user's own plans
 *
 * ✅ Single endpoint  GET /api/v1/chat/rag-message
 * ✅ Auth required    Sanctum token
 * ✅ No input body    Only returns data — no message processing here
 */
class RagMessageController extends Controller
{
    /**
     * GET /api/v1/chat/rag-message
     *
     * Returns full project data snapshot for the RAG AI pipeline.
     *
     * Headers required:
     *   Authorization: Bearer {sanctum_token}
     *   Accept:        application/json
     */
    public function index(RagDataRequest $request): JsonResponse
    {
        try {
            $authUser = auth('sanctum')->user();

            // ── 1. Load authenticated user with their own plans ──────────
            $authUser->load([
                'plans.planItems.place',
            ]);

            // ── 2. Load ALL places with comments (+ author) and likes ────
            $places = Places::with([
                'comments.user',
                'likes.user',
            ])->latest()->get();

            // ── 3. Load ALL tours with guide, tourPlaces, linked plan, comments and likes ─
            $tours = Tours::with([
                'guide',
                'plan',
                'tourPlaces.place',
                'comments.user',
                'likes.user',
            ])->latest()->get();

            // ── 4. Load ALL plans with owner + items + places ─────────────
            $allPlans = Plans::with([
                'user',
                'planItems.place',
            ])->latest()->get();

            // ── 5. Build resource payload ─────────────────────────────────
            $resourcePayload = [
                'user'      => $authUser,
                'places'    => $places,
                'tours'     => $tours,
                'all_plans' => $allPlans,
            ];

            return response()->json([
                'success' => true,
                'data'    => RagDataResource::make($resourcePayload),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching RAG data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
