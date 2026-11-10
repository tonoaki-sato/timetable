<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Models\LocationSchedule;
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

    #[Override]
    public function create(array $attributes): LocationSchedule
    {
        return LocationSchedule::create($attributes);
    }

    #[Override]
    public function update(LocationSchedule $schedule, array $attributes): bool
    {
        return $schedule->update($attributes);
    }

    #[Override]
    public function delete(LocationSchedule $schedule): bool
    {
        return (bool) $schedule->delete();
    }
}
