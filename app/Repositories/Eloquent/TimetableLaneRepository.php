<?php

namespace App\Repositories\Eloquent;

use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class TimetableLaneRepository implements TimetableLaneRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?TimetableLane
    {
        return TimetableLane::with('lane')->find($id);
    }

    #[Override]
    public function findWithFixedStaff(int $id): ?TimetableLane
    {
        return TimetableLane::with(['lane', 'fixedStaff'])->find($id);
    }

    #[Override]
    public function getByIdsWithFixedStaff(int $timetableId, array $timetableLaneIds): Collection
    {
        if (empty($timetableLaneIds)) {
            return new Collection;
        }

        return TimetableLane::with(['lane', 'fixedStaff'])
            ->where('timetable_id', $timetableId)
            ->whereIn('id', $timetableLaneIds)
            ->ordered()
            ->get();
    }

    #[Override]
    public function findByTimetableAndLaneId(int $timetableId, int $laneId): ?TimetableLane
    {
        return TimetableLane::with('lane')
            ->where('timetable_id', $timetableId)
            ->where('lane_id', $laneId)
            ->first();
    }

    #[Override]
    public function findByTimetableAndNameOrCleanName(int $timetableId, string $laneName, string $cleanName): ?TimetableLane
    {
        return TimetableLane::with('lane')
            ->where('timetable_id', $timetableId)
            ->whereHas('lane', function ($q) use ($laneName, $cleanName): void {
                $q->where('name', $laneName)->orWhere('name', $cleanName);
            })
            ->first();
    }

    #[Override]
    public function findForTimetableById(Timetable $timetable, int $timetableLaneId): ?TimetableLane
    {
        /** @var TimetableLane|null $timetableLane */
        $timetableLane = $timetable->timetableLanes()
            ->with('lane')
            ->where('id', $timetableLaneId)
            ->first();

        return $timetableLane;
    }

    #[Override]
    public function getAllOrdered(): Collection
    {
        return TimetableLane::with('lane')->ordered()->get();
    }

    #[Override]
    public function getForTimetable(Timetable $timetable): Collection
    {
        return $timetable->timetableLanes()
            ->with([
                'lane',
                'leadStaff',
                'fixedStaff',
                'tasks',
                'laneRequests.task',
                'laneRequests.requestedByStaff',
            ])
            ->withCount('tasks')
            ->ordered()
            ->get();
    }

    #[Override]
    public function getForTimetablePrint(Timetable $timetable): Collection
    {
        return $timetable->timetableLanes()
            ->with([
                'lane',
                'leadStaff',
                'fixedStaff',
                'tasks.assignments.staff',
            ])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getOrderedWithLeadForTimetable(Timetable $timetable, ?array $timetableLaneIds = null): Collection
    {
        $query = $timetable->timetableLanes()
            ->with(['lane', 'leadStaff'])
            ->ordered();

        if (! empty($timetableLaneIds)) {
            $query->whereIn('id', $timetableLaneIds);
        }

        return $query->get();
    }

    #[Override]
    public function getLeadTimetableLanesForTimetable(Timetable $timetable): Collection
    {
        return $timetable->timetableLanes()
            ->with('lane:id,name')
            ->whereNotNull('lead_staff_id')
            ->get(['id', 'lane_id', 'lead_staff_id']);
    }

    #[Override]
    public function findWithDetails(Timetable $timetable, int $timetableLaneId): ?TimetableLane
    {
        /** @var TimetableLane|null $timetableLane */
        $timetableLane = $timetable->timetableLanes()
            ->with([
                'lane',
                'leadStaff',
                'fixedStaff',
                'tasks' => function ($query): void {
                    $query->with(['assignments'])->orderBy('start_time', 'asc');
                },
                'laneRequests' => function ($query): void {
                    $query->with(['requestedByStaff', 'task'])->orderByDesc('id');
                },
            ])
            ->withCount('tasks')
            ->where('id', $timetableLaneId)
            ->first();

        return $timetableLane;
    }

    #[Override]
    public function create(Timetable $timetable, array $attributes): TimetableLane
    {
        return $timetable->timetableLanes()->create($attributes)->load('lane');
    }

    #[Override]
    public function update(TimetableLane $timetableLane, array $attributes): bool
    {
        return $timetableLane->update($attributes);
    }

    #[Override]
    public function delete(TimetableLane $timetableLane): bool
    {
        return (bool) $timetableLane->delete();
    }

    #[Override]
    public function reorder(Timetable $timetable, array $timetableLaneIds): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $timetableLaneIds)));
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

        DB::transaction(function () use ($timetable, $cleanIds, $caseSql): void {
            $timetable->timetableLanes()->whereIn('id', $cleanIds)->update([
                'display_order' => DB::raw($caseSql),
            ]);
        });
    }

    #[Override]
    public function getMaxDisplayOrder(Timetable $timetable): int
    {
        return (int) ($timetable->timetableLanes()->max('display_order') ?? 0);
    }

    #[Override]
    public function getFixedStaffIds(TimetableLane $timetableLane): array
    {
        return $timetableLane->fixedStaff()
            ->pluck('staff.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    #[Override]
    public function syncFixedStaff(TimetableLane $timetableLane, array $staffIds): void
    {
        $timetableLane->fixedStaff()->sync($staffIds);
    }

    #[Override]
    public function syncFixedStaffWithoutDetaching(TimetableLane $timetableLane, array $staffIds): void
    {
        $timetableLane->fixedStaff()->syncWithoutDetaching($staffIds);
    }
}
