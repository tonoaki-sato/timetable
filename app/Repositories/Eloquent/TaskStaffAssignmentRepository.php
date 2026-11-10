<?php

namespace App\Repositories\Eloquent;

use App\Models\TaskStaffAssignment;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class TaskStaffAssignmentRepository implements TaskStaffAssignmentRepositoryInterface
{
    #[Override]
    public function getActiveByTimetableAndStaffIds(int $timetableId, array $staffIds): Collection
    {
        if (empty($staffIds)) {
            return new Collection;
        }

        return TaskStaffAssignment::whereHas('task', function ($query) use ($timetableId): void {
            $query->where('timetable_id', $timetableId);
        })
            ->whereIn('staff_id', $staffIds)
            ->where('attendance_status', '!=', 'absent')
            ->with('task')
            ->get();
    }

    #[Override]
    public function findFirstTodayAssignmentForStaff(int $staffId, string $todayDate): ?TaskStaffAssignment
    {
        return TaskStaffAssignment::where('staff_id', $staffId)
            ->whereHas('task.timetable', function ($q) use ($todayDate): void {
                $q->whereDate('target_date', $todayDate);
            })
            ->join('timetable_tasks', 'task_staff_assignments.timetable_task_id', '=', 'timetable_tasks.id')
            ->orderBy('timetable_tasks.start_time', 'asc')
            ->select('task_staff_assignments.*')
            ->first();
    }

    #[Override]
    public function findForStaffById(int $staffId, int $assignmentId): ?TaskStaffAssignment
    {
        return TaskStaffAssignment::where('staff_id', $staffId)->find($assignmentId);
    }

    #[Override]
    public function findByTaskAndStaff(int $taskId, int $staffId): ?TaskStaffAssignment
    {
        return TaskStaffAssignment::where('timetable_task_id', $taskId)
            ->where('staff_id', $staffId)
            ->first();
    }

    #[Override]
    public function existsByTaskAndStaff(int $taskId, int $staffId): bool
    {
        return TaskStaffAssignment::where('timetable_task_id', $taskId)
            ->where('staff_id', $staffId)
            ->exists();
    }

    #[Override]
    public function getStaffIdsForTask(int $taskId): array
    {
        return TaskStaffAssignment::where('timetable_task_id', $taskId)
            ->pluck('staff_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    #[Override]
    public function firstOrCreate(array $attributes, array $values = []): TaskStaffAssignment
    {
        return TaskStaffAssignment::firstOrCreate($attributes, $values);
    }

    #[Override]
    public function create(array $attributes): TaskStaffAssignment
    {
        return TaskStaffAssignment::create($attributes);
    }

    #[Override]
    public function update(TaskStaffAssignment $assignment, array $attributes): bool
    {
        return $assignment->update($attributes);
    }

    #[Override]
    public function delete(TaskStaffAssignment $assignment): bool
    {
        return (bool) $assignment->delete();
    }

    #[Override]
    public function deleteByTaskAndStaffAndRole(int $taskId, int $staffId, string $roleDescription): int
    {
        return TaskStaffAssignment::where('timetable_task_id', $taskId)
            ->where('staff_id', $staffId)
            ->where('role_description', $roleDescription)
            ->delete();
    }

    #[Override]
    public function deleteByTaskIds(iterable $taskIds): int
    {
        return TaskStaffAssignment::whereIn('timetable_task_id', $taskIds)->delete();
    }
}
