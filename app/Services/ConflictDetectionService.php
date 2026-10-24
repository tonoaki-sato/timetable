<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\TimetableTask;
use Illuminate\Database\Eloquent\Collection;

class ConflictDetectionService
{
    /**
     * 指定したタスクと時間帯が重複し、同一スタッフが割り当てられているタスク一覧を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getConflictingTasks(TimetableTask $task, int $staffId): Collection
    {
        if (! $task->start_time || ! $task->end_time) {
            return new Collection;
        }

        // 同一運行日（timetable_id）かつ自分以外のタスクで、該当スタッフが有効アサイン（absent以外）されているもの
        return TimetableTask::where('timetable_id', $task->timetable_id)
            ->where('id', '!=', $task->id)
            ->whereHas('assignments', function ($query) use ($staffId) {
                $query->where('staff_id', $staffId)
                    ->where('attendance_status', '!=', 'absent');
            })
            ->where(function ($query) use ($task) {
                // 時間重複条件:
                // task.start_time < other.end_time AND task.end_time > other.start_time
                $query->where('start_time', '<', $task->end_time)
                    ->where('end_time', '>', $task->start_time);
            })
            ->with(['lane'])
            ->get();
    }

    /**
     * 重複が存在するかチェックし、最初の重複タスクを返却（重複なしはnull）
     */
    public function checkConflict(TimetableTask $task, int $staffId): ?TimetableTask
    {
        return $this->getConflictingTasks($task, $staffId)->first();
    }

    /**
     * スタッフリスト全体について、該当タスクに対する重複タスクをスタッフIDをキーとしたマップとして取得
     *
     * @param  Collection<int, Staff>  $activeStaff
     * @return array<int, TimetableTask>
     */
    public function getConflictsMapForStaffList(TimetableTask $task, Collection $activeStaff): array
    {
        if (! $task->start_time || ! $task->end_time || $activeStaff->isEmpty()) {
            return [];
        }

        $staffIds = $activeStaff->pluck('id')->all();

        // 該当運行日・該当時間帯にアサインされているタスクを取得
        $overlappingTasks = TimetableTask::where('timetable_id', $task->timetable_id)
            ->where('id', '!=', $task->id)
            ->where('start_time', '<', $task->end_time)
            ->where('end_time', '>', $task->start_time)
            ->whereHas('assignments', function ($query) use ($staffIds) {
                $query->whereIn('staff_id', $staffIds)
                    ->where('attendance_status', '!=', 'absent');
            })
            ->with(['lane', 'assignments' => function ($query) use ($staffIds) {
                $query->whereIn('staff_id', $staffIds)
                    ->where('attendance_status', '!=', 'absent');
            }])
            ->get();

        $conflictsMap = [];

        foreach ($overlappingTasks as $ovTask) {
            foreach ($ovTask->assignments as $assignment) {
                // 最初の重複タスクを保持
                if (! isset($conflictsMap[$assignment->staff_id])) {
                    $conflictsMap[$assignment->staff_id] = $ovTask;
                }
            }
        }

        return $conflictsMap;
    }
}
