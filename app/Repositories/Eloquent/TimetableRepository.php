<?php

namespace App\Repositories\Eloquent;

use App\Models\LaneRequest;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
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
    public function findFirstActiveOrEarliest(): ?Timetable
    {
        return Timetable::where('status', 'active')->orderBy('target_date', 'asc')->first()
            ?? Timetable::orderBy('target_date', 'asc')->first();
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
        return Timetable::where('status', '!=', 'completed')
            ->orderBy('target_date', 'asc')
            ->get();
    }

    #[Override]
    public function getFiltered(?string $status = null): Collection
    {
        $query = Timetable::query()->orderBy('target_date', 'asc');

        if ($status) {
            $query->status($status);
        }

        return $query->get();
    }

    #[Override]
    public function getForMonth(int $year, int $month, ?string $search = null): Collection
    {
        $query = Timetable::whereYear('target_date', $year)
            ->whereMonth('target_date', $month);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('target_date')->orderBy('start_time')->get();
    }

    #[Override]
    public function findForDatePrioritizingActive(string $dateStr): ?Timetable
    {
        return Timetable::whereDate('target_date', $dateStr)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    #[Override]
    public function getAllWithLanesAndLeadStaff(): Collection
    {
        return Timetable::with(['lanes.leadStaff'])
            ->orderBy('target_date', 'asc')
            ->get();
    }

    #[Override]
    public function getAllWithLogisticsPlansCount(): Collection
    {
        return Timetable::withCount('logisticsPlans')
            ->orderBy('target_date', 'asc')
            ->get();
    }

    #[Override]
    public function findWithDetails(int $id): ?Timetable
    {
        return Timetable::with([
            'creator',
            'lanes' => function ($q): void {
                $q->orderBy('display_order', 'asc')->with(['leadStaff', 'fixedStaff']);
            },
            'tasks' => function ($q): void {
                $q->orderBy('start_time', 'asc')
                    ->with(['assignments.staff']);
            },
            'logisticsPlans.equipment',
        ])->withCount(['logisticsPlans'])->find($id);
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

            // タスクを削除（これにより lanes.id への RESTRICT 外部キー制約が解除される）
            $timetable->tasks()->delete();

            // レーンの固定スタッフ紐付けおよびレーン本体を削除
            foreach ($timetable->lanes as $lane) {
                $lane->fixedStaff()->detach();
                $lane->delete();
            }

            // レーンリクエストを削除
            LaneRequest::where('timetable_id', $timetable->id)->delete();

            // タイムテーブル本体を削除
            return (bool) $timetable->delete();
        });
    }

    #[Override]
    public function getActiveAndDraftWithWorkflowDetails(): Collection
    {
        return Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->where('status', '!=', 'completed')
            ->orderBy('target_date', 'asc')
            ->get();
    }

    #[Override]
    public function getTotalCount(): int
    {
        return Timetable::count();
    }

    #[Override]
    public function findWithWorkflowDetails(int $id): ?Timetable
    {
        return Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->find($id);
    }

    #[Override]
    public function loadForAssignmentsPrint(Timetable $timetable): Timetable
    {
        return $timetable->load([
            'lanes' => function ($query): void {
                $query->orderBy('display_order')->orderBy('id');
            },
            'lanes.leadStaff',
            'lanes.tasks' => function ($query): void {
                $query->orderBy('start_time')->orderBy('end_time');
            },
            'lanes.tasks.assignments.staff',
        ]);
    }

    #[Override]
    public function loadForAssignmentWorkspace(Timetable $timetable): Timetable
    {
        return $timetable->load([
            'lanes' => function ($q): void {
                $q->orderBy('display_order')->with('leadStaff');
            },
            'tasks' => function ($q): void {
                $q->orderBy('start_time')
                    ->orderBy('end_time')
                    ->with(['laneRelation', 'assignments.staff']);
            },
        ]);
    }

    #[Override]
    public function loadForTasksIndex(Timetable $timetable): Timetable
    {
        return $timetable->load([
            'lanes' => function ($q): void {
                $q->orderBy('display_order')->with([
                    'leadStaff',
                    'tasks' => function ($tQuery): void {
                        $tQuery->orderBy('start_time', 'asc')
                            ->orderBy('end_time', 'asc');
                    },
                ]);
            },
        ]);
    }
}
