<?php

namespace App\View\Components\Dashboard;

use App\DTOs\GlobalDashboardDTO;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GlobalKpiStrip extends Component
{
    public function __construct(
        public GlobalDashboardDTO $overview
    ) {}

    public function render(): View
    {
        return view('components.dashboard.global-kpi-strip');
    }
}

