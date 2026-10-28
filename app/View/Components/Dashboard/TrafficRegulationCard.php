<?php

namespace App\View\Components\Dashboard;

use App\DTOs\TrafficRegulationContext;
use App\Services\TrafficRegulationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TrafficRegulationCard extends Component
{
    public readonly TrafficRegulationContext $context;

    public readonly Carbon $now;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected TrafficRegulationService $trafficRegulationService,
        ?Carbon $now = null,
        ?TrafficRegulationContext $trafficRegulationContext = null
    ) {
        $this->now = $now ?? Carbon::now('Asia/Tokyo');
        $this->context = $trafficRegulationContext ?? $this->trafficRegulationService->getCurrentContext($this->now);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.traffic-regulation-card');
    }
}
