<?php

namespace App\View\Components\Timetable;

use App\DTOs\TimetableOverviewDTO;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ShowKpiStrip extends Component
{
    public function __construct(
        public readonly TimetableOverviewDTO $overview
    ) {}

    public function render(): View
    {
        return view('components.timetable.show-kpi-strip');
    }
}

