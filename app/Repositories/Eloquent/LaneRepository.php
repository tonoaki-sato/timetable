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
    public function findById(int $id): ?Lane
    {
        return Lane::find($id);
    }

    #[Override]
    public function findWithFixedStaff(int $id): ?Lane
    {
        return Lane::with('fixedStaff')->find($id);
    }

    #[Override]
    public function getByIdsWithFixedStaff(int $timetableId, array $laneIds): Collection
    {
        if (empty($laneIds)) {
            return new Collection;
        }

        return Lane::with('fixedStaff')
            ->where('timetable_id', $timetableId)
            ->whereIn('id', $laneIds)
            ->ordered()
            ->get();
    }

    #[Override]
    public function findByTimetableAndNameOrCleanName(int $timetableId, string $laneName, string $cleanName): ?Lane
    {
        return Lane::where('timetable_id', $timetableId)
            ->where(function ($q) use ($laneName, $cleanName): void {
                $q->where('name', $laneName)->orWhere('name', $cleanName);
            })
            ->first();
    }

    #[Override]
    public function findForTimetableById(Timetable $timetable, int $laneId): ?Lane
    {
        /** @var Lane|null $lane */
        $lane = $timetable->lanes()->where('id', $laneId)->first();

        return $lane;
    }

    #[Override]
    public function getAllOrdered(): Collection
    {
        return Lane::ordered()->get();
    }

    #[Override]
    public function getForTimetable(Timetable $timetable): Collection
    {
        return $timetable->lanes()
            ->with([
                'leadStaff',
                'fixedStaff',
                'tasks',
                'laneRequests.task',
                'laneRequests.requestedByStaff',
            ])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getForTimetablePrint(Timetable $timetable): Collection
    {
        return $timetable->lanes()
            ->with(['leadStaff'])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getOrderedWithLeadForTimetable(Timetable $timetable, ?array $laneIds = null): Collection
    {
        $query = $timetable->lanes()
            ->with('leadStaff')
            ->ordered();

        if (! empty($laneIds)) {
            $query->whereIn('id', $laneIds);
        }

        return $query->get();
    }

    #[Override]
    public function getLeadLanesForTimetable(Timetable $timetable): Collection
    {
        return $timetable->lanes()
            ->whereNotNull('lead_staff_id')
            ->get(['name', 'lead_staff_id']);
    }

    #[Override]
    public function findWithDetails(Timetable $timetable, int $laneId): ?Lane
    {
        /** @var Lane|null $lane */
        $lane = $timetable->lanes()
            ->with([
                'leadStaff',
                'fixedStaff',
                'tasks' => function ($query): void {
                    $query->with(['assignments'])->orderBy('start_time', 'asc');
                },
                'laneRequests' => function ($query): void {
                    $query->with(['requestedByStaff', 'task'])->orderByDesc('id');
                },
            ])
            ->where('id', $laneId)
            ->first();

        return $lane;
    }

    #[Override]
    public function create(Timetable $timetable, array $attributes): Lane
    {
        return $timetable->lanes()->create($attributes);
    }

    #[Override]
    public function update(Lane $lane, array $attributes): bool
    {
        return $lane->update($attributes);
    }

    #[Override]
    public function delete(Lane $lane): bool
    {
        return (bool) $lane->delete();
    }

    #[Override]
    public function reorder(Timetable $timetable, array $laneIds): void
    {
        DB::transaction(function () use ($timetable, $laneIds): void {
            $order = 10;
            foreach ($laneIds as $laneId) {
                $timetable->lanes()->where('id', $laneId)->update([
                    'display_order' => $order,
                ]);
                $order += 10;
            }
        });
    }

    #[Override]
    public function getMaxDisplayOrder(Timetable $timetable): int
    {
        return (int) ($timetable->lanes()->max('display_order') ?? 0);
    }

    #[Override]
    public function getFixedStaffIds(Lane $lane): array
    {
        return $lane->fixedStaff()
            ->pluck('staff.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    #[Override]
    public function syncFixedStaff(Lane $lane, array $staffIds): void
    {
        $lane->fixedStaff()->sync($staffIds);
    }

    #[Override]
    public function syncFixedStaffWithoutDetaching(Lane $lane, array $staffIds): void
    {
        $lane->fixedStaff()->syncWithoutDetaching($staffIds);
    }
}
