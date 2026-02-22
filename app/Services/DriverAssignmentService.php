<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Models\DeliveryTrackingUpdate;
use App\Models\Order;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverAssignmentService
{
    /**
     * Attempt to assign the best available driver to the given order.
     */
    public function assignBestDriver(Order $order): ?User
    {
        $restaurant = $order->restaurant;

        if ($restaurant === null || $restaurant->latitude === null || $restaurant->longitude === null) {
            Log::warning("[DriverAssignment] Order #{$order->id}: Restaurant has no coordinates.");

            return null;
        }

        $maxRadius = SystemSetting::getInt('driver_max_radius_km', 10);
        $onlineTimeout = SystemSetting::getInt('driver_online_timeout_minutes', 15);

        $candidates = $this->findOnlineDrivers($onlineTimeout);

        if ($candidates->isEmpty()) {
            Log::info("[DriverAssignment] Order #{$order->id}: No online drivers found.");

            return null;
        }

        $scored = $candidates
            ->map(function (User $driver) use ($restaurant) {
                $lastUpdate = $driver->latestTrackingUpdate;

                if ($lastUpdate === null) {
                    return null;
                }

                $distanceKm = DistanceCalculator::haversine(
                    (float) $lastUpdate->latitude,
                    (float) $lastUpdate->longitude,
                    (float) $restaurant->latitude,
                    (float) $restaurant->longitude,
                );

                $vehicleType = $driver->vehicle_type ?? VehicleType::Scooter;
                $etaMinutes = DistanceCalculator::estimateMinutes($distanceKm, $vehicleType->averageSpeedKmh());

                return [
                    'driver' => $driver,
                    'distance_km' => $distanceKm,
                    'eta_minutes' => $etaMinutes,
                ];
            })
            ->filter()
            ->filter(fn (array $item) => $item['distance_km'] <= $maxRadius)
            ->sortBy('eta_minutes');

        if ($scored->isEmpty()) {
            Log::info("[DriverAssignment] Order #{$order->id}: No drivers within {$maxRadius}km radius.");

            return null;
        }

        $best = $scored->first();
        $driver = $best['driver'];

        $order->driver_id = $driver->id;
        $order->assigned_by = null; // system assignment
        $order->assigned_at = now();
        $order->save();

        Log::info("[DriverAssignment] Order #{$order->id}: Assigned to driver #{$driver->id} ({$driver->name}), distance: {$best['distance_km']}km, ETA: {$best['eta_minutes']}min.");

        return $driver;
    }

    /**
     * Find all online drivers (have a tracking update within the timeout window).
     *
     * @return Collection<int, User>
     */
    protected function findOnlineDrivers(int $timeoutMinutes): Collection
    {
        $cutoff = now()->subMinutes($timeoutMinutes);

        return User::query()
            ->where('role', UserRole::Driver)
            ->where('is_available', true)
            ->whereHas('trackingUpdates', function ($query) use ($cutoff): void {
                $query->where('recorded_at', '>=', $cutoff);
            })
            ->with(['latestTrackingUpdate'])
            ->get();
    }
}
