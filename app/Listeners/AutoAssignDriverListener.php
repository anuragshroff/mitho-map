<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusUpdated;
use App\Services\DriverAssignmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AutoAssignDriverListener implements ShouldQueue
{
    public function __construct(
        private DriverAssignmentService $driverAssignmentService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        // Only assign when order transitions to 'confirmed'
        if ($order->status !== OrderStatus::Confirmed) {
            return;
        }

        // Skip if a driver is already assigned
        if ($order->driver_id !== null) {
            return;
        }

        $driver = $this->driverAssignmentService->assignBestDriver($order);

        if ($driver === null) {
            Log::warning("[AutoAssignDriver] Order #{$order->id}: No suitable driver found. Order remains unassigned.");
        }
    }
}
