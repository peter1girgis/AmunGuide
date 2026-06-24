<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Place\StorePlaceMediaRequest;
use App\Http\Resources\PlaceMediaResource;
use App\Models\Place_media;
use App\Models\Places;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * PlaceMediaController - Place Media Management (Admin Only)
 *
 * ✅ Upload multiple images/media for a place
 * ✅ Delete a specific media file
 * ✅ List all media for a place
 */
class PlaceMediaController extends Controller
{
    /**
     * GET /api/v1/places/{place}/media
     *
     * List all media for a specific place
     * ✅ Available for guests and authenticated users
     */
    public function index(Places $place): JsonResponse
    {
        try {
            $media = $place->media()->latest()->get();
            dd($media);

            return response()->json([
                'success' => true,
                'place'   => [
                    'id'    => $place->id,
                    'title' => $place->title,
                ],
                'data'  => PlaceMediaResource::collection($media),
                'total' => $media->count(),
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to fetch place media', [
                'place_id' => $place->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // هيعرضلك الخطأ الحقيقي هنا
                'trace'   => $e->getTrace()
            ], 500);
        }
    }

    /**
     * POST /api/v1/places/{place}/media
     *
     * Upload one or more media files for a place
     * ✅ Admin only
     * ✅ Accepts multiple files in a single request
     */
    public function store(StorePlaceMediaRequest $request, Places $place): JsonResponse
    {
        // ✅ Admin check
        if (auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        try {
            $uploaded = [];

            foreach ($request->file('files') as $file) {
                // Store file under place-specific folder
                $path = $file->store("places/{$place->id}/media", 'public');

                $media = Place_media::create([
                    'place_id'  => $place->id,
                    'type'      => $request->input('type', 'image'),
                    'file_path' => $path,
                ]);

                $uploaded[] = new PlaceMediaResource($media);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded) . ' file(s) uploaded successfully.',
                'data'    => $uploaded,
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Failed to upload place media', [
                'place_id' => $place->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/places/{place}/media/{media}
     *
     * Delete a specific media file
     * ✅ Admin only
     * ✅ Removes the file from storage and DB record
     */
    public function destroy(Places $place, Place_media $media): JsonResponse
    {
        // ✅ Admin check
        if (auth('sanctum')->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        // ✅ Make sure this media belongs to the given place
        if ((int) $media->place_id !== (int) $place->id) {
            return response()->json([
                'success' => false,
                'message' => 'This media does not belong to the specified place.',
            ], 404);
        }

        try {
            // Delete the actual file from storage
            if (Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }

            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            \Log::error('Failed to delete place media', [
                'place_id' => $place->id,
                'media_id' => $media->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media.',
            ], 500);
        }
    }
}
