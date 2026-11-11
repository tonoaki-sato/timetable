<?php

namespace App\Repositories\Eloquent;

use App\Models\Lane;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class TimetableTaskRepository implements TimetableTaskRepositoryInterface
{
    #[Override]
    public function findWithAssignments(int $id): ?TimetableTask
    {
        return TimetableTask::with(['timetable', 'laneRelation.leadStaff', 'assignments.staff'])->find($id);
    }

    #[Override]
    public function getByTimetableId(int $timetableId): Collection
    {
        return TimetableTask::with('assignments.staff')
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function getForTimelineByTimetableId(int $timetableId): Collection
    {
        return TimetableTask::with(['laneRelation.leadStaff', 'assignments.staff'])
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function getWithAssignmentsAndLaneByTimetableId(int $timetableId): Collection
    {
        return TimetableTask::with(['assignments.staff', 'laneRelation'])
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function getConflictingTasksForStaff(TimetableTask $task, int $staffId): Collection
    {
        return TimetableTask::where('timetable_id', $task->timetable_id)
            ->where('id', '!=', $task->id)
            ->whereHas('assignments', function ($query) use ($staffId): void {
                $query->where('staff_id', $staffId)
                    ->where('attendance_status', '!=', 'absent');
            })
            ->where(function ($query) use ($task): void {
                $query->where('start_time', '<', $task->end_time)
                    ->where('end_time', '>', $task->start_time);
            })
            ->with(['lane', 'assignments'])
            ->get();
    }

    #[Override]
    public function getOverlappingTasksForStaffIds(TimetableTask $task, array $staffIds): Collection
    {
        if (empty($staffIds)) {
            return new Collection;
        }

        return TimetableTask::where('timetable_id', $task->timetable_id)
            ->where('id', '!=', $task->id)
            ->where('start_time', '<', $task->end_time)
            ->where('end_time', '>', $task->start_time)
            ->whereHas('assignments', function ($query) use ($staffIds): void {
                $query->whereIn('staff_id', $staffIds)
                    ->where('attendance_status', '!=', 'absent');
            })
            ->with(['lane', 'assignments' => function ($query) use ($staffIds): void {
                $query->whereIn('staff_id', $staffIds)
                    ->where('attendance_status', '!=', 'absent');
            }])
            ->get();
    }

    #[Override]
    public function getActiveForLaneWithAssignments(int $laneId): Collection
    {
        return TimetableTask::where('lane_id', $laneId)
            ->where('status', '!=', 'cancelled')
            ->with('assignments')
            ->get();
    }

    #[Override]
    public function getActiveForLaneExcludingIds(int $laneId, array $ignoredTaskIds = []): Collection
    {
        return TimetableTask::where('lane_id', $laneId)
            ->where('status', '!=', 'cancelled')
            ->when(! empty($ignoredTaskIds), function ($query) use ($ignoredTaskIds): void {
                $query->whereNotIn('id', $ignoredTaskIds);
            })
            ->get();
    }

    #[Override]
    public function getActiveForLanes(array $laneIds): Collection
    {
        if (empty($laneIds)) {
            return new Collection;
        }

        return TimetableTask::whereIn('lane_id', $laneIds)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function countByLane(Lane $lane): int
    {
        return $lane->tasks()->count();
    }

    #[Override]
    public function migrateLaneTasks(Lane $sourceLane, Lane $targetLane): int
    {
        return $sourceLane->tasks()->update(['lane_id' => $targetLane->id]);
    }

    #[Override]
    public function deleteByLane(Lane $lane): int
    {
        return $lane->tasks()->delete();
    }

    #[Override]
    public function create(array $attributes): TimetableTask
    {
        return TimetableTask::create($attributes);
    }

    #[Override]
    public function createMany(array $tasksAttributes): Collection
    {
        return DB::transaction(function () use ($tasksAttributes): Collection {
            $createdTasks = new Collection;

            foreach ($tasksAttributes as $attributes) {
                $createdTasks->push(TimetableTask::create($attributes));
            }

            return $createdTasks;
        });
    }

    #[Override]
    public function update(TimetableTask $task, array $attributes): bool
    {
        return $task->update($attributes);
    }

    #[Override]
    public function delete(TimetableTask $task): bool
    {
        return (bool) $task->delete();
    }

    #[Override]
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

    #[Override]
    public function bulkAssignStaff(TimetableTask $task, array $staffIds, ?string $roleDescription = null): Collection
    {
        return DB::transaction(function () use ($task, $staffIds, $roleDescription): Collection {
            $assignments = new Collection;

            foreach ($staffIds as $staffId) {
                $assignment = TaskStaffAssignment::updateOrCreate(
                    [
                        'timetable_task_id' => $task->id,
                        'staff_id' => (int) $staffId,
                    ],
                    [
                        'role_description' => $roleDescription,
                        'attendance_status' => 'assigned',
                    ]
                );
                $assignments->push($assignment);
            }

            return $assignments;
        });
    }

    #[Override]
    public function removeStaff(TimetableTask $task, int $staffId): bool
    {
        return (bool) TaskStaffAssignment::where('timetable_task_id', $task->id)
            ->where('staff_id', $staffId)
            ->delete();
    }

    #[Override]
    public function updateAttendanceStatus(int $assignmentId, string $status): bool
    {
        $assignment = TaskStaffAssignment::find($assignmentId);
        if ($assignment === null) {
            return false;
        }

        return $assignment->update(['attendance_status' => $status]);
    }
}
