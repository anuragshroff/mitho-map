<?php

namespace App\Services;

class DistanceCalculator
{
    /**
     * Calculate the distance between two coordinates using the Haversine formula.
     *
     * @return float Distance in kilometers.
     */
    public static function haversine(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
    ): float {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    /**
     * Calculate estimated delivery time in minutes based on distance and vehicle speed.
     *
     * @param  float  $distanceKm  Distance in kilometers
     * @param  float  $speedKmh  Average speed in km/h (default 30)
     * @return int Estimated minutes
     */
    public static function estimateMinutes(float $distanceKm, float $speedKmh = 30.0): int
    {
        if ($speedKmh <= 0) {
            return 0;
        }

        return (int) ceil(($distanceKm / $speedKmh) * 60);
    }
}
