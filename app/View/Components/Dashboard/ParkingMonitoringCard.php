<?php

namespace App\View\Components\Dashboard;

use App\DTOs\ParkingMonitoringContext;
use App\Services\ParkingConstraintService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ParkingMonitoringCard extends Component
{
    public readonly ParkingMonitoringContext $context;

    public readonly Carbon $now;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected ParkingConstraintService $parkingConstraintService,
        ?Carbon $now = null,
        ?ParkingMonitoringContext $context = null
    ) {
        $this->now = $now ?? Carbon::now('Asia/Tokyo');
        $this->context = $context ?? $this->parkingConstraintService->getMonitoringContext($this->now);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.parking-monitoring-card');
    }
}
