<?php

namespace App\Repositories\Eloquent;

use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TimetableTaskRepository implements TimetableTaskRepositoryInterface
{
    /**
     * IDによる取得（アサイン情報及び担当スタッフ情報付き）
     */
    public function findWithAssignments(int $id): ?TimetableTask
    {
        return TimetableTask::with(['timetable', 'assignments.staff'])->find($id);
    }

    /**
     * 指定タイムテーブルに紐づくタスク一覧を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getByTimetableId(int $timetableId): Collection
    {
        return TimetableTask::with('assignments.staff')
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * 新規タスク作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TimetableTask
    {
        return TimetableTask::create($attributes);
    }

    /**
     * タスク更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(TimetableTask $task, array $attributes): bool
    {
        return $task->update($attributes);
    }

    /**
     * タスク削除
     */
    public function delete(TimetableTask $task): bool
    {
        return $task->delete();
    }

    /**
     * スタッフをタスクへアサイン
     */
    public function assignStaff(TimetableTask $task, int $staffId, ?string $roleDescription = null): TaskStaffAssignment
    {
        return TaskStaffAssignment::updateOrCreate(
            [
                'timetable_task_id' => $task->id,
                'staff_id' => $staffId,
            ],
            [
                'role_description' => $roleDescription,
                'attendance_status' => 'assigned',
            ]
        );
    }

    /**
     * スタッフのアサイン解除
     */
    public function removeStaff(TimetableTask $task, int $staffId): bool
    {
        return (bool) TaskStaffAssignment::where('timetable_task_id', $task->id)
            ->where('staff_id', $staffId)
            ->delete();
    }

    /**
     * アサイン出欠ステータス更新
     */
    public function updateAttendanceStatus(int $assignmentId, string $status): bool
    {
        $assignment = TaskStaffAssignment::find($assignmentId);
        if ($assignment === null) {
            return false;
        }

        return $assignment->update(['attendance_status' => $status]);
    }
}
