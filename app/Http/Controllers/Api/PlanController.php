<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plans;
use App\Models\User_activities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Eager-load all relationships needed for a full PlanResource response.
     */
    private function withAll(Plans $plan): Plans
    {
        return $plan->load(['user', 'places', 'planItems']);
    }

    /**
     * Sync plan_items from a validated array of [{place_id, day_index?}, ...].
     */
    private function syncPlanItems(Plans $plan, array $items): void
    {
        // Delete existing items and re-insert — keeps it simple and predictable.
        $plan->planItems()->delete();

        $rows = array_map(fn (array $item): array => [
            'plan_id'    => $plan->id,
            'place_id'   => $item['place_id'],
            'day_index'  => $item['day_index'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $items);

        if (!empty($rows)) {
            DB::table('plan_items')->insert($rows);
        }
    }

    /**
     * Log a plan-related activity to user_activities.
     */
    private function logActivity(
        int $userId,
        string $activityType,
        Plans $plan,
        ?int $placeId = null,
        ?string $details = null,
    ): void {
        User_activities::create([
            'user_id'       => $userId,
            'activity_type' => $activityType,
            'place_id'      => $placeId,
            'details'       => $details ?? $plan->summary(),
        ]);
    }

    // -------------------------------------------------------------------------
    // CRUD Actions
    // -------------------------------------------------------------------------

    /**
     * GET /api/plans
     *
     * List plans with optional query parameters:
     *   - search   (string)  : filter by title
     *   - user_id  (int)     : filter by owner
     *   - budget   (numeric) : only plans whose total ticket price ≤ budget
     *   - with_places (bool) : only plans that have at least one place
     *
     * Returns a paginated collection of PlanResource (15 per page).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if(auth()->user()->role == 'admin') {
            $query = Plans::with(['user', 'places', 'planItems'])
                     ->newest(); // ← scope: newest()
            if ($search = $request->string('search')->toString()) {
                $query->searchByTitle($search);
            }
            if ($userId = $request->integer('user_id')) {
                $query->forUser($userId);
            }
            if ($request->boolean('with_places')) {
                $query->withPlaces();
            }
            if ($budget = $request->float('budget')) {
                $query->withinBudget($budget);
            }
        }
        if(auth()->user()->role == 'guide') {
            $query = Plans::with(['user', 'places', 'planItems'])
                     ->newest() // ← scope: newest()
                     ->forUser(auth()->id());   // ← scope: forUser()
            if ($search = $request->string('search')->toString()) {
                $query->searchByTitle($search);
            }
            if ($request->boolean('with_places')) {
                $query->withPlaces();
            }
            if ($budget = $request->float('budget')) {
                $query->withinBudget($budget);
            }
        }
        if(auth()->user()->role == 'tourist') {
            // البدء بالاستعلام مع العلاقات المطلوبة والـ Scopes الأساسية
            $query = Plans::with(['user', 'places', 'planItems' , 'tours'])
                ->newest()
                ->withPlaces();

            // إضافة منطق الفلترة المزدوج: خططي الشخصية "أو" خطط مرتبطة برحلات
            $query->where(function ($q) {
                $q->where('user_id', auth()->id()) // الخطط التي أنشأها المستخدم بنفسه
                ->orWhereHas('tours');           // الخطط التي تم بناء رحلات (Tours) عليها
            });

            // فلترة البحث إذا وجدت
            if ($search = $request->string('search')->toString()) {
                $query->searchByTitle($search);
            }

            // فلترة الميزانية إذا وجدت
            if ($budget = $request->float('budget')) {
                $query->withinBudget($budget);
            }
        }



        return PlanResource::collection($query->paginate(15));
    }

    /**
     * POST /api/plans
     *
     * Create a new plan (and optional plan_items) for the authenticated user.
     * Logs a `plan_creation` activity.
     *
     * Returns the created PlanResource with HTTP 201.
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        
        $plan = DB::transaction(function () use ($request): Plans {
            /** @var Plans $plan */
            $plan = Plans::create([
                'user_id' => $request->user()->id,
                'title'   => $request->validated('title'),
            ]);

            if ($items = $request->validated('plan_items')) {
                $this->syncPlanItems($plan, $items);
            }

            return $plan;
        });

        // Load all relationships so helpers work correctly.
        $this->withAll($plan);

        // ── Helper: isComplete / summary / totalPrice / totalDays ────────────
        // All used inside logActivity via $plan->summary()
        $this->logActivity(
            userId:       $request->user()->id,
            activityType: 'plan_creation',
            plan:         $plan,
            details:      sprintf(
                'New plan created. %s | Complete: %s | Total EGP: %.2f | Days: %d',
                $plan->summary(),
                $plan->isComplete() ? 'Yes' : 'No',
                $plan->totalPrice(),
                $plan->totalDays(),
            ),
        );

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/plans/{plan}
     *
     * Retrieve a single plan with all relationships and computed fields.
     *
     * Returns PlanResource with HTTP 200.
     */
    public function show($planId): PlanResource|JsonResponse
    {
        $plan = Plans::with(['user', 'places', 'planItems' , 'tours'])->find($planId);
        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found.'
            ], 404);
        }
        if(auth()->user()->role == 'tourist') {

            if ((int) $plan->user_id !== (int) auth()->id() && !$plan->tours()->exists()) {
                return response()->json([
                    'message' => 'You are not allowed to view this plan.'
                ], 403);
            }
        }
        if(auth()->user()->role == 'guide') {
            if ((int) $plan->user_id !== (int) auth()->id()) {
                return response()->json([
                    'message' => 'You are not allowed to view this plan.'
                ], 403);
            }
        }
        return new PlanResource($plan);
    }

    /**
     * PUT|PATCH /api/plans/{plan}
     *
     * Update a plan's title and/or replace its plan_items.
     * Only the plan owner is authorised (enforced in UpdatePlanRequest).
     * Logs a `plan_creation` activity (re-used enum value; extend as needed).
     *
     * Returns the updated PlanResource with HTTP 200.
     */
    public function update(UpdatePlanRequest $request, Plans $plan): PlanResource
    {
        DB::transaction(function () use ($request, $plan): void {
            $plan->update($request->only('title'));

            if ($items = $request->validated('plan_items')) {
                $this->syncPlanItems($plan, $items);
            }
        });

        $this->withAll($plan);

        // ── All helpers exercised again on update ────────────────────────────
        $this->logActivity(
            userId:       $request->user()->id,
            activityType: 'plan_creation',      // closest available enum value
            plan:         $plan,
            details:      sprintf(
                'Plan updated. %s | Complete: %s | Total EGP: %.2f | Days: %d',
                $plan->summary(),
                $plan->isComplete() ? 'Yes' : 'No',
                $plan->totalPrice(),
                $plan->totalDays(),
            ),
        );

        return new PlanResource($plan);
    }

    /**
     * DELETE /api/plans/{plan}
     *
     * Delete a plan (cascades to plan_items via DB constraint).
     * Only the plan owner may delete it.
     *
     * Returns HTTP 204 No Content.
     */
    public function destroy(Request $request, $planId): JsonResponse
    {
        $plan = Plans::find($planId);
        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found.'
            ], 404);
        }
        // Authorization: only owner
        // abort_if(
        //     (int) $plan->user_id !== (int) $request->user()->id,
        //     403,
        //     'You are not allowed to delete this plan.'
        // );
        if((int) $plan->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'You are not allowed to delete this plan.'
            ], 403);
        }
        if($plan->tours()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a plan that has associated tours.'
            ], 400);
        }


        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted successfully.'
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Extra scoped endpoints
    // -------------------------------------------------------------------------

    /**
     * GET /api/plans/my
     *
     * Shortcut: return only the authenticated user's plans.
     * Demonstrates the `forUser` scope explicitly outside of index().
     *
     * Returns a paginated collection of PlanResource.
     */
    public function myPlans(Request $request): AnonymousResourceCollection
    {
        $plans = Plans::with(['user', 'places', 'planItems'])
                     ->forUser($request->user()->id)   // ← scope: forUser()
                     ->newest()                         // ← scope: newest()
                     ->paginate(15);

        return PlanResource::collection($plans);
    }
}
