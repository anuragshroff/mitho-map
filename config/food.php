<?php

return [
    'delivery_fee_cents' => (int) env('FOOD_DELIVERY_FEE_CENTS', 500),
    'driver_max_active_assignments' => (int) env('FOOD_DRIVER_MAX_ACTIVE_ASSIGNMENTS', 1),
];
