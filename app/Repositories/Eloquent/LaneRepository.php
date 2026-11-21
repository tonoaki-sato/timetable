<?php

namespace App\Repositories\Eloquent;

use App\Models\Lane;
use App\Models\Timetable;
use App\Repositories\Contracts\LaneRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class LaneRepository implements LaneRepositoryInterface
{
    #[Override]
    public function getAllWithCountsAndTimetables(): Collection
    {
        return Lane::query()
            ->with(['timetableLanes.timetable'])
            ->withCount(['timetableLanes', 'tasks'])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getActiveOrdered(): Collection
    {
        return Lane::active()->ordered()->get();
    }

    #[Override]
    public function getAvailableForTimetable(Timetable $timetable): Collection
    {
        return Lane::active()
            ->whereDoesntHave('timetableLanes', function ($query) use ($timetable): void {
                $query->where('timetable_id', $timetable->id);
            })
            ->ordered()
            ->get();
    }

    #[Override]
    public function findById(int $id): ?Lane
    {
        return Lane::find($id);
    }

    #[Override]
    public function findByNameOrCleanName(string $name, string $cleanName): ?Lane
    {
        return Lane::query()
            ->where(function ($query) use ($name, $cleanName): void {
                $query->where('name', $name)->orWhere('name', $cleanName);
            })
            ->first();
    }

    #[Override]
    public function getMaxDisplayOrder(): int
    {
        return (int) (Lane::max('display_order') ?? 0);
    }

    #[Override]
    public function create(array $attributes): Lane
    {
        $lane = Lane::create($attributes);

        return $lane->fresh(['timetableLanes.timetable'])
            ?->loadCount(['timetableLanes', 'tasks']) ?? $lane;
    }

    #[Override]
    public function update(Lane $lane, array $attributes): Lane
    {
        $lane->update($attributes);

        return $lane->fresh(['timetableLanes.timetable'])
            ?->loadCount(['timetableLanes', 'tasks']) ?? $lane;
    }

    #[Override]
    public function delete(Lane $lane): bool
    {
        return (bool) $lane->delete();
    }

    #[Override]
    public function countTimetableLanes(Lane $lane): int
    {
        return $lane->timetableLanes()->count();
    }

    #[Override]
    public function reorder(array $laneIds): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $laneIds)));
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
            Lane::whereIn('id', $cleanIds)->update([
                'display_order' => DB::raw($caseSql),
            ]);
        });
    }
}
