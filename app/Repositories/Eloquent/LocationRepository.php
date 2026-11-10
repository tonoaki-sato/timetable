<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Models\Timetable;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class LocationRepository implements LocationRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?Location
    {
        return Location::find($id);
    }

    #[Override]
    public function findFirstByTimetableAndNameLike(int $timetableId, string $keyword): ?Location
    {
        return Location::where('timetable_id', $timetableId)
            ->where('name', 'like', "%{$keyword}%")
            ->first();
    }

    #[Override]
    public function findFirstByTimetableAndExactName(int $timetableId, string $name): ?Location
    {
        return Location::where('timetable_id', $timetableId)
            ->where('name', $name)
            ->first();
    }

    #[Override]
    public function getForTimetable(Timetable $timetable): Collection
    {
        return $timetable->locations()
            ->with(['type', 'schedules' => function ($query): void {
                $query->orderBy('start_at', 'asc');
            }])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getActiveOrderedForTimetable(Timetable $timetable): Collection
    {
        return Location::forTimetable($timetable)
            ->active()
            ->ordered()
            ->get();
    }

    #[Override]
    public function getActiveWithTypeAndSchedules(?Timetable $timetable = null): Collection
    {
        $query = Location::active()->ordered()->with(['type', 'schedules']);
        if ($timetable) {
            $query->where('timetable_id', $timetable->id);
        }

        return $query->get();
    }

    #[Override]
    public function getUniqueNamesOrdered(): Collection
    {
        /** @var Collection<int, Location> $unique */
        $unique = Location::select('id', 'name')
            ->orderBy('display_order')
            ->get()
            ->unique('name')
            ->values();

        return $unique;
    }

    #[Override]
    public function getTotalCount(): int
    {
        return Location::count();
    }

    #[Override]
    public function findWithDetails(Timetable $timetable, int $locationId): ?Location
    {
        /** @var Location|null $location */
        $location = $timetable->locations()
            ->with([
                'type',
                'schedules' => function ($query): void {
                    $query->orderBy('start_at', 'asc');
                },
                'plansTo.equipment',
                'plansFrom.equipment',
            ])
            ->where('id', $locationId)
            ->first();

        return $location;
    }

    #[Override]
    public function create(Timetable $timetable, array $attributes): Location
    {
        return $timetable->locations()->create($attributes);
    }

    #[Override]
    public function update(Location $location, array $attributes): bool
    {
        return $location->update($attributes);
    }

    #[Override]
    public function delete(Location $location): bool
    {
        return (bool) $location->delete();
    }

    #[Override]
    public function reorder(Timetable $timetable, array $locationIds): void
    {
        DB::transaction(function () use ($timetable, $locationIds): void {
            $order = 10;
            foreach ($locationIds as $locationId) {
                $timetable->locations()->where('id', $locationId)->update([
                    'display_order' => $order,
                ]);
                $order += 10;
            }
        });
    }

    #[Override]
    public function getMaxDisplayOrder(Timetable $timetable): int
    {
        return (int) ($timetable->locations()->max('display_order') ?? 0);
    }

    #[Override]
    public function syncSchedules(Location $location, array $schedulesData): void
    {
        DB::transaction(function () use ($location, $schedulesData): void {
            $updatedIds = [];

            foreach ($schedulesData as $item) {
                if (! empty($item['id'])) {
                    $schedule = $location->schedules()->where('id', $item['id'])->first();
                    if ($schedule) {
                        $schedule->update([
                            'start_at' => $item['start_at'],
                            'end_at' => $item['end_at'],
                            'availability_status' => $item['availability_status'],
                            'title' => $item['title'] ?? null,
                            'note' => $item['note'] ?? null,
                        ]);
                        $updatedIds[] = $schedule->id;
                    }
                } else {
                    $created = $location->schedules()->create([
                        'start_at' => $item['start_at'],
                        'end_at' => $item['end_at'],
                        'availability_status' => $item['availability_status'],
                        'title' => $item['title'] ?? null,
                        'note' => $item['note'] ?? null,
                    ]);
                    $updatedIds[] = $created->id;
                }
            }

            $location->schedules()->whereNotIn('id', $updatedIds)->delete();
        });
    }
}
