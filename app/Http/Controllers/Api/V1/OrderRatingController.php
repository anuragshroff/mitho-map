<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRatingRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderRatingController extends Controller
{
    public function store(StoreRatingRequest $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($order->customer_id !== $user->id) {
            abort(403, 'You can only rate your own orders.');
        }

        if ($order->status !== OrderStatus::Delivered) {
            abort(400, 'You can only rate orders that have been delivered.');
        }

        if ($order->rating()->exists()) {
            abort(400, 'You have already rated this order.');
        }

        $validated = $request->validated();

        $rating = $order->rating()->create([
            'user_id' => $user->id,
            'restaurant_id' => $order->restaurant_id,
            'food_rating' => $validated['food_rating'],
            'delivery_rating' => $validated['delivery_rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Rating submitted successfully.',
            'data' => $rating,
        ], 201);
    }
}
