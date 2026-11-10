<?php

namespace App\Repositories\Eloquent;

use App\Models\LocationType;
use App\Repositories\Contracts\LocationTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class LocationTypeRepository implements LocationTypeRepositoryInterface
{
    #[Override]
    public function getAllWithLocationsCountOrdered(): Collection
    {
        return LocationType::withCount('locations')->ordered()->get();
    }

    #[Override]
    public function getActiveOrdered(): Collection
    {
        return LocationType::active()->ordered()->get();
    }

    #[Override]
    public function hasLocations(LocationType $locationType): bool
    {
        return $locationType->locations()->exists();
    }

    #[Override]
    public function create(array $attributes): LocationType
    {
        return LocationType::create($attributes);
    }

    #[Override]
    public function update(LocationType $locationType, array $attributes): bool
    {
        return $locationType->update($attributes);
    }

    #[Override]
    public function delete(LocationType $locationType): bool
    {
        return (bool) $locationType->delete();
    }
}
