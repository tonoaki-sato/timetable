<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Repositories\Contracts\LocationScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class LocationScheduleRepository implements LocationScheduleRepositoryInterface
{
    #[Override]
    public function getForLocationOrdered(Location $location): Collection
    {
        return $location->schedules()->orderBy('start_at', 'asc')->get();
    }
}
