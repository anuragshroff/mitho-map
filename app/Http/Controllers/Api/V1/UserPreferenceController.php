<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    /**
     * Display the authenticated user's preferences.
     */
    public function show(Request $request): JsonResponse
    {
        $preferences = $request->user()->preferences()->first();

        return response()->json([
            'data' => $preferences ?: new \stdClass(),
        ]);
    }

    /**
     * Update or create the authenticated user's preferences.
     */
    public function update(UpdateUserPreferenceRequest $request): JsonResponse
    {
        $preferences = $request->user()->preferences()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );

        return response()->json([
            'data' => $preferences,
            'message' => 'Preferences updated successfully.',
        ]);
    }
}
