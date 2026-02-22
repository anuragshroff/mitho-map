<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\SystemSetting;
use App\Services\DistanceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryFeeController extends Controller
{
    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $restaurant = Restaurant::query()->findOrFail($request->integer('restaurant_id'));

        $distanceKm = DistanceCalculator::haversine(
            (float) $restaurant->latitude,
            (float) $restaurant->longitude,
            $request->float('latitude'),
            $request->float('longitude'),
        );

        $baseFee = SystemSetting::getInt('delivery_base_fee_cents', 3000);
        $perKm = SystemSetting::getInt('delivery_per_km_cents', 1500);
        $maxRadius = SystemSetting::getInt('delivery_max_radius_km', 15);

        if ($distanceKm > $maxRadius) {
            return response()->json([
                'data' => [
                    'available' => false,
                    'message' => 'Delivery is not available for this distance.',
                    'distance_km' => $distanceKm,
                    'max_radius_km' => $maxRadius,
                ],
            ]);
        }

        $feeCents = $baseFee + (int) ceil($distanceKm * $perKm);
        $estimatedMinutes = DistanceCalculator::estimateMinutes($distanceKm);

        return response()->json([
            'data' => [
                'available' => true,
                'distance_km' => $distanceKm,
                'fee_cents' => $feeCents,
                'estimated_minutes' => $estimatedMinutes,
                'base_fee_cents' => $baseFee,
                'per_km_cents' => $perKm,
            ],
        ]);
    }
}
