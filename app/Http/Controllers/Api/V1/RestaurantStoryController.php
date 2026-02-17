<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStoryRequest;
use App\Http\Resources\StoryResource;
use App\Models\Restaurant;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantStoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Story::query()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['restaurant', 'creator'])
            ->latest('id');

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->integer('restaurant_id'));
        }

        return StoryResource::collection($query->paginate(20));
    }

    public function store(StoreStoryRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        $restaurant = Restaurant::query()->findOrFail($validated['restaurant_id']);

        if ($user->role === UserRole::Restaurant && $restaurant->owner_id !== $user->id) {
            abort(403);
        }

        $story = Story::query()->create([
            'restaurant_id' => $restaurant->id,
            'created_by' => $user->id,
            'media_url' => $validated['media_url'],
            'caption' => $validated['caption'] ?? null,
            'expires_at' => $validated['expires_at'] ?? now()->addDay(),
            'is_active' => true,
        ]);

        $story->load(['restaurant', 'creator']);

        return (new StoryResource($story))->response()->setStatusCode(201);
    }
}
