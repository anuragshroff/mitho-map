<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminStoryStatusRequest;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Story::query()
            ->with(['restaurant:id,name', 'creator:id,name'])
            ->latest('id');

        $search = trim($request->string('search')->toString());
        $isActive = $request->string('is_active')->toString();

        if ($search !== '') {
            $query->where(function ($storyQuery) use ($search): void {
                $storyQuery
                    ->where('caption', 'like', '%'.$search.'%')
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search): void {
                        $restaurantQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('creator', function ($creatorQuery) use ($search): void {
                        $creatorQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if (in_array($isActive, ['1', '0'], true)) {
            $query->where('is_active', $isActive === '1');
        }

        $stories = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (Story $story): array {
                return [
                    'id' => $story->id,
                    'restaurant_name' => $story->restaurant?->name,
                    'creator_name' => $story->creator?->name,
                    'caption' => $story->caption,
                    'media_url' => $story->media_url,
                    'expires_at' => $story->expires_at?->toIso8601String(),
                    'is_active' => $story->is_active,
                ];
            });

        return Inertia::render('admin/stories', [
            'stories' => $stories,
            'filters' => [
                'search' => $search,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function updateStatus(
        UpdateAdminStoryStatusRequest $request,
        Story $story,
    ): RedirectResponse {
        $story->is_active = $request->boolean('is_active');

        if ($request->filled('expires_at')) {
            $story->expires_at = $request->date('expires_at');
        }

        $story->save();

        return back();
    }

    public function destroy(Story $story): RedirectResponse
    {
        $story->delete();

        return back();
    }
}
