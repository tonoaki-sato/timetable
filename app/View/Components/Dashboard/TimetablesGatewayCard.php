<?php

namespace App\View\Components\Dashboard;

use App\Models\Timetable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class TimetablesGatewayCard extends Component
{
    /**
     * @param  Collection<int, Timetable>  $timetables
     */
    public function __construct(
        public Collection $timetables
    ) {}

    public function render(): View
    {
        return view('components.dashboard.timetables-gateway-card');
    }
}

