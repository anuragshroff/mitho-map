<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeliveryTrackingUpdateRequest;
use App\Http\Resources\DeliveryTrackingUpdateResource;
use App\Models\Order;
use App\Models\User;

class DriverTrackingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreDeliveryTrackingUpdateRequest $request, Order $order): DeliveryTrackingUpdateResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureDriverCanTrackOrder($order, $user);

        if ($order->driver_id === null && $user->role === UserRole::Driver) {
            $order->driver_id = $user->id;
            $order->save();
        }

        $trackingUpdate = $order->trackingUpdates()->create([
            'driver_id' => $order->driver_id ?? $user->id,
            'latitude' => $request->float('latitude'),
            'longitude' => $request->float('longitude'),
            'heading' => $request->has('heading') ? $request->float('heading') : null,
            'speed_kmh' => $request->has('speed_kmh') ? $request->float('speed_kmh') : null,
            'recorded_at' => $request->date('recorded_at') ?? now(),
        ]);

        DriverLocationUpdated::dispatch($order, $trackingUpdate);

        return new DeliveryTrackingUpdateResource($trackingUpdate);
    }

    protected function ensureDriverCanTrackOrder(Order $order, User $user): void
    {
        if ($user->role === UserRole::Admin) {
            return;
        }

        if ($user->role === UserRole::Driver && ($order->driver_id === null || $order->driver_id === $user->id)) {
            return;
        }

        abort(403);
    }
}
