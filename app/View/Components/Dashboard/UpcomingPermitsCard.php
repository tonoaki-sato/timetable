<?php

namespace App\View\Components\Dashboard;

use App\Models\Schedule;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class UpcomingPermitsCard extends Component
{
    /**
     * @param  Collection<int, Schedule>  $permits
     */
    public function __construct(
        public Collection $permits
    ) {}

    public function render(): View
    {
        return view('components.dashboard.upcoming-permits-card');
    }
}
