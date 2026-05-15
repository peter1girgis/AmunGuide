<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * Display a listing of all users.
     *
     * GET /api/admin/users
     */
    public function index(): AnonymousResourceCollection
    {
        $users = User::latest()->paginate(15);

        return UserResource::collection($users);
    }

    /**
     * Display the specified user.
     *
     * GET /api/admin/users/{user}
     */
    public function show($id): UserResource | JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        return new UserResource($user);
    }

    /**
     * Update the specified user.
     *
     * PUT/PATCH /api/admin/users/{user}
     */
    public function update(UpdateUserRequest $request, $id): UserResource | JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }
        $validated = $request->validated();

        // Handle password: only update if a new one was provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Remove password key entirely so it is not touched in the DB
            unset($validated['password']);
        }

        $user->update($validated);

        return new UserResource($user->fresh());
    }

    /**
     * Remove the specified user from storage.
     *
     * DELETE /api/admin/users/{user}
     */
    public function destroy($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }
        // Prevent an admin from deleting their own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ], 200);
    }
}
