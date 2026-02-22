<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = $user->notifications()
            ->whereKey($notification)
            ->first();

        if ($record === null) {
            abort(404);
        }

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return response()->json([
            'data' => [
                'id' => $record->id,
                'read_at' => $record->read_at?->toIso8601String(),
            ],
        ]);
    }
}
