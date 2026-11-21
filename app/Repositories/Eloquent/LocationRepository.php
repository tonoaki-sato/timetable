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
    public function findFirstByNameLike(string $keyword): ?Location
    {
        return Location::where('name', 'like', "%{$keyword}%")->first();
    }

    #[Override]
    public function findFirstByExactName(string $name): ?Location
    {
        return Location::where('name', $name)->first();
    }

    #[Override]
    public function getNameIdMap(): array
    {
        $map = [];
        $rows = Location::orderBy('id', 'asc')->get(['id', 'name']);

        foreach ($rows as $row) {
            if (! array_key_exists($row->name, $map)) {
                $map[$row->name] = (int) $row->id;
            }
        }

        return $map;
    }

    #[Override]
    public function findFirstByTimetableAndNameLike(?int $timetableId, string $keyword): ?Location
    {
        return $this->findFirstByNameLike($keyword);
    }

    #[Override]
    public function findFirstByTimetableAndExactName(?int $timetableId, string $name): ?Location
    {
        return $this->findFirstByExactName($name);
    }

    #[Override]
    public function getNameIdMapForTimetable(?int $timetableId = null): array
    {
        return $this->getNameIdMap();
    }

    #[Override]
    public function getAllOrdered(): Collection
    {
        return Location::with(['type', 'schedules' => function ($query): void {
            $query->orderBy('start_at', 'asc');
        }])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getForTimetable(?Timetable $timetable = null): Collection
    {
        return $this->getAllOrdered();
    }

    #[Override]
    public function getActiveOrdered(): Collection
    {
        return Location::active()->ordered()->get();
    }

    #[Override]
    public function getActiveOrderedForTimetable(?Timetable $timetable = null): Collection
    {
        return $this->getActiveOrdered();
    }

    #[Override]
    public function getActiveWithTypeAndSchedules(?Timetable $timetable = null): Collection
    {
        return Location::active()->ordered()->with(['type', 'schedules'])->get();
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
    public function create(array $attributes): Location
    {
        return Location::create($attributes);
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
    public function reorder(array $locationIds): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $locationIds)));
        if ($cleanIds === []) {
            return;
        }

        $cases = [];
        $order = 10;
        foreach ($cleanIds as $id) {
            $cases[] = "WHEN {$id} THEN {$order}";
            $order += 10;
        }
        $caseSql = 'CASE id '.implode(' ', $cases).' ELSE display_order END';

        DB::transaction(function () use ($cleanIds, $caseSql): void {
            Location::whereIn('id', $cleanIds)->update([
                'display_order' => DB::raw($caseSql),
            ]);
        });
    }

    #[Override]
    public function getMaxDisplayOrder(): int
    {
        return (int) (Location::max('display_order') ?? 0);
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

            if ($updatedIds === []) {
                $location->schedules()->delete();
            } else {
                $location->schedules()->whereNotIn('id', $updatedIds)->delete();
            }
        });
    }
}
