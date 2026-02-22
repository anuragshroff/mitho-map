<?php

namespace App\Enums;

enum VehicleType: string
{
    case Bike = 'bike';
    case Scooter = 'scooter';
    case Car = 'car';

    /**
     * Average speed in km/h used for ETA calculations.
     */
    public function averageSpeedKmh(): float
    {
        return match ($this) {
            self::Bike => 20.0,
            self::Scooter => 30.0,
            self::Car => 40.0,
        };
    }
}
