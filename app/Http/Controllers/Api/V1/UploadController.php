<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Store an uploaded image and return its public URL.
     */
    public function store(StoreUploadRequest $request): JsonResponse
    {
        $file = $request->file('image');
        $directory = $request->input('directory', 'uploads');

        // Note: For production, this should ideally be configured to use S3 or similar cloud storage.
        // For development/local, it uses the 'public' disk.
        $path = $file->storePublicly($directory, 'public');

        if (! $path) {
            abort(500, 'Failed to upload file.');
        }

        $url = Storage::disk('public')->url($path);

        return response()->json([
            'message' => 'File uploaded successfully',
            'data' => [
                'path' => $path,
                'url' => $url,
            ],
        ], 201);
    }
}
