<?php

namespace App\DTOs;

use App\Enums\LocationDailyAvailabilityStatus;
use App\Models\Location;
use App\Models\LocationSchedule;
use Illuminate\Support\Collection;

class LocationListItemData
{
    /**
     * @param  Collection<int, LocationSchedule>  $todaySchedules
     */
    public function __construct(
        public Location $location,
        public LocationStatusEvaluation $currentEvaluation,
        public LocationDailyAvailabilityStatus $dailyStatus,
        public Collection $todaySchedules,
    ) {}
}
