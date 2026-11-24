<?php

namespace App\Repositories\Eloquent;

use App\Models\LaneRequest;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Support\NavigationCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class TimetableRepository implements TimetableRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?Timetable
    {
        return Timetable::find($id);
    }

    #[Override]
    public function findOrFail(int $id): Timetable
    {
        return Timetable::findOrFail($id);
    }

    #[Override]
    public function findFirstActiveOrEarliest(?Carbon $now = null): ?Timetable
    {
        $selectable = $this->getSelectableTimetables();

        if ($selectable->isEmpty()) {
            return Timetable::orderBy('target_date', 'desc')->first()
                ?? Timetable::orderBy('target_date', 'asc')->first();
        }

        $now ??= Carbon::now();

        return $selectable->sortBy(function (Timetable $t) use ($now): int {
            return $this->calculateTimeDistanceToNow($t, $now);
        })->first();
    }

    #[Override]
    public function getSelectableTimetables(): Collection
    {
        return app(NavigationCache::class)->getNavTimetables(function (): Collection {
            return Timetable::whereIn('status', ['draft', 'active'])
                ->orderBy('target_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();
        });
    }

    /**
     * 運行情報と現在日時との時間差（距離・秒数）を算出
     */
    protected function calculateTimeDistanceToNow(Timetable $timetable, Carbon $now): int
    {
        $targetDateStr = $timetable->target_date?->format('Y-m-d') ?? (string) $timetable->target_date;
        $startDateTime = Carbon::parse($targetDateStr.' '.$timetable->start_time);
        $endDateTime = Carbon::parse($targetDateStr.' '.$timetable->end_time);

        if ($timetable->status === 'active' || ($now->gte($startDateTime) && $now->lte($endDateTime))) {
            return 0;
        }

        return min(
            abs($now->diffInSeconds($startDateTime, false)),
            abs($now->diffInSeconds($endDateTime, false))
        );
    }

    #[Override]
    public function getAll(): Collection
    {
        return Timetable::orderBy('target_date', 'asc')
            ->get();
    }

    #[Override]
    public function getForNavigation(): Collection
    {
        return $this->getSelectableTimetables();
    }

    #[Override]
    public function getFiltered(?string $status = null): Collection
    {
        $query = Timetable::query()
            ->orderBy('target_date', 'asc');

        if ($status) {
            $query->status($status);
        }

        return $query->get();
    }

    #[Override]
    public function getForMonth(int $year, int $month, ?string $search = null): Collection
    {
        $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
        $nextMonthStart = Carbon::createFromDate($year, $month, 1)->addMonth()->format('Y-m-d');

        $query = Timetable::where('target_date', '>=', $startOfMonth)
            ->where('target_date', '<', $nextMonthStart);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('target_date')->orderBy('start_time')->get();
    }

    #[Override]
    public function findForDatePrioritizingActive(string $dateStr): ?Timetable
    {
        $nextDayStr = Carbon::parse($dateStr)->addDay()->format('Y-m-d');

        return Timetable::where('target_date', '>=', $dateStr)
            ->where('target_date', '<', $nextDayStr)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    #[Override]
    public function findWithDetails(int $id): ?Timetable
    {
        return Timetable::with([
            'creator',
            'timetableLanes' => function ($q): void {
                $q->ordered()->with(['lane', 'leadStaff', 'fixedStaff']);
            },
            'tasks' => function ($q): void {
                $q->orderBy('start_time', 'asc')
                    ->with(['timetableLane.lane', 'assignments.staff']);
            },
            'logisticsPlans.equipment',
        ])->find($id);
    }

    #[Override]
    public function findByDate(string $date): ?Timetable
    {
        return Timetable::where('target_date', $date)->first();
    }

    #[Override]
    public function create(array $attributes): Timetable
    {
        return Timetable::create($attributes);
    }

    #[Override]
    public function update(Timetable $timetable, array $attributes): bool
    {
        return $timetable->update($attributes);
    }

    #[Override]
    public function delete(Timetable $timetable): bool
    {
        return DB::transaction(function () use ($timetable): bool {
            $taskIds = $timetable->tasks()->pluck('id');

            if ($taskIds->isNotEmpty()) {
                TaskStaffAssignment::whereIn('timetable_task_id', $taskIds)->delete();
            }

            // タスクを削除（これにより timetable_lanes.id への RESTRICT 外部キー制約が解除される）
            $timetable->tasks()->delete();

            // 日別担当班編成の固定スタッフ紐付けおよび編成本体を削除
            foreach ($timetable->timetableLanes as $timetableLane) {
                $timetableLane->fixedStaff()->detach();
                $timetableLane->delete();
            }

            // レーンリクエストを削除
            LaneRequest::where('timetable_id', $timetable->id)->delete();

            // タイムテーブル本体を削除
            $deleted = (bool) $timetable->delete();

            $cache = app(NavigationCache::class);
            $cache->forgetTimetables();
            $cache->forgetPendingLaneRequests();

            return $deleted;
        });
    }

    #[Override]
    public function loadForAssignmentsPrint(Timetable $timetable): Timetable
    {
        return $timetable->load([
            'timetableLanes' => function ($query): void {
                $query->ordered();
            },
            'timetableLanes.lane',
            'timetableLanes.leadStaff',
            'timetableLanes.tasks' => function ($query): void {
                $query->orderBy('start_time')->orderBy('end_time');
            },
            'timetableLanes.tasks.assignments.staff',
        ]);
    }

    #[Override]
    public function loadForAssignmentWorkspace(Timetable $timetable): Timetable
    {
        return $timetable->load([
            'timetableLanes' => function ($q): void {
                $q->ordered()->with(['lane', 'leadStaff']);
            },
            'tasks' => function ($q): void {
                $q->orderBy('start_time')
                    ->orderBy('end_time')
                    ->with(['timetableLane.lane', 'assignments.staff']);
            },
        ]);
    }

    #[Override]
    public function loadForTasksIndex(Timetable $timetable): Timetable
    {
        return $timetable->load([
            'timetableLanes' => function ($q): void {
                $q->ordered()->with([
                    'lane',
                    'leadStaff',
                    'tasks' => function ($tQuery): void {
                        $tQuery->orderBy('start_time', 'asc')
                            ->orderBy('end_time', 'asc');
                    },
                ]);
            },
        ]);
    }

    #[Override]
    public function findForClone(int $id): ?Timetable
    {
        return Timetable::withCount([
            'timetableLanes',
            'tasks',
            'logisticsPlans',
            'layoutItems',
            'placements',
        ])->find($id);
    }
}
