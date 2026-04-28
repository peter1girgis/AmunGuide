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
 * ✅ Single endpoint  POST /api/v1/chat/rag-message
 * ✅ Auth required    Sanctum token
 * ✅ Optional filter  { "filter": ["places", "tours", "plans"] }
 *    If no filter sent → returns everything
 */
class RagMessageController extends Controller
{
    /**
     * POST /api/v1/chat/rag-message
     *
     * Returns full (or filtered) project data snapshot for the RAG AI pipeline.
     *
     * Headers required:
     *   Authorization: Bearer {sanctum_token}
     *   Accept:        application/json
     *   Content-Type:  application/json
     *
     * Body (optional):
     * {
     *   "filter": ["places", "tours", "plans"]
     * }
     * If "filter" is not provided → all sections are returned.
     */
    public function index(RagDataRequest $request): JsonResponse
    {
        try {
            $authUser = auth('sanctum')->user();

            // ── Determine which sections to filter ────────────────────────
            // input() بيقرأ الـ request body مباشرةً بغض النظر عن الـ validation
            // لو مفيش filter في الـ body → يرجع كل حاجة بالـ default
            $filter = $request->input('filter', ['places', 'tours', 'plans']);

            // ── 1. Load authenticated user with their own plans ──────────
            $authUser->load([
                'plans.planItems.place',
            ]);

            // ── 2. Load ALL places (only if requested) ────────────────────
            $places = in_array('places', $filter)
                ? Places::with([
                    'comments.user',
                    'likes.user',
                ])->latest()->get()
                : collect();

            // ── 3. Load ALL tours (only if requested) ─────────────────────
            $tours = in_array('tours', $filter)
                ? Tours::with([
                    'guide',
                    'plan',
                    'tourPlaces.place',
                    'comments.user',
                    'likes.user',
                    'payments',
                ])->latest()->get()
                : collect();

            // ── 4. Load ALL plans (only if requested) ─────────────────────
            $allPlans = in_array('plans', $filter)
                ? Plans::with([
                    'user',
                    'planItems.place',
                ])->latest()->get()
                : collect();

            // ── 5. Build resource payload ─────────────────────────────────
            $resourcePayload = [
                'user'      => $authUser,
                'places'    => $places,
                'tours'     => $tours,
                'all_plans' => $allPlans,
                'filter'    => $filter,
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
