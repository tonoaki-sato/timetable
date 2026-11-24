<?php

namespace App\Repositories\Eloquent;

use App\Models\TaskStaffAssignment;
use App\Models\TimetableLane;
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
        return TimetableTask::with(['timetable', 'timetableLane.lane', 'timetableLane.leadStaff', 'assignments.staff'])->find($id);
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
        return TimetableTask::with(['timetableLane.lane', 'timetableLane.leadStaff', 'assignments.staff'])
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function getWithAssignmentsAndLaneByTimetableId(int $timetableId): Collection
    {
        return TimetableTask::with(['assignments.staff', 'timetableLane.lane'])
            ->where('timetable_id', $timetableId)
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function getConflictingTasksForStaff(TimetableTask $task, int $staffId): Collection
    {
        return TimetableTask::where('timetable_id', $task->timetable_id)
            ->where('id', '!=', $task->id)
            ->where('task_type', $task->task_type_enum->value)
            ->whereHas('assignments', function ($query) use ($staffId): void {
                $query->where('staff_id', $staffId)
                    ->where('attendance_status', '!=', 'absent');
            })
            ->where(function ($query) use ($task): void {
                $query->where('start_time', '<', $task->end_time)
                    ->where('end_time', '>', $task->start_time);
            })
            ->with(['timetableLane.lane', 'assignments'])
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
            ->where('task_type', $task->task_type_enum->value)
            ->where('start_time', '<', $task->end_time)
            ->where('end_time', '>', $task->start_time)
            ->whereHas('assignments', function ($query) use ($staffIds): void {
                $query->whereIn('staff_id', $staffIds)
                    ->where('attendance_status', '!=', 'absent');
            })
            ->with(['timetableLane.lane', 'assignments' => function ($query): void {
                $query->where('attendance_status', '!=', 'absent');
            }])
            ->get();
    }

    #[Override]
    public function getActiveForLaneWithAssignments(int $timetableLaneId): Collection
    {
        return TimetableTask::where('timetable_lane_id', $timetableLaneId)
            ->where('status', '!=', 'cancelled')
            ->with('assignments')
            ->get();
    }

    #[Override]
    public function getActiveForLaneExcludingIds(int $timetableLaneId, array $ignoredTaskIds = []): Collection
    {
        return TimetableTask::where('timetable_lane_id', $timetableLaneId)
            ->where('status', '!=', 'cancelled')
            ->when(! empty($ignoredTaskIds), function ($query) use ($ignoredTaskIds): void {
                $query->whereNotIn('id', $ignoredTaskIds);
            })
            ->get();
    }

    #[Override]
    public function getActiveForLanes(array $timetableLaneIds): Collection
    {
        if (empty($timetableLaneIds)) {
            return new Collection;
        }

        return TimetableTask::whereIn('timetable_lane_id', $timetableLaneIds)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();
    }

    #[Override]
    public function countByTimetableLane(TimetableLane $timetableLane): int
    {
        return $timetableLane->tasks()->count();
    }

    #[Override]
    public function migrateTimetableLaneTasks(TimetableLane $sourceTimetableLane, TimetableLane $targetTimetableLane): int
    {
        return $sourceTimetableLane->tasks()->update(['timetable_lane_id' => $targetTimetableLane->id]);
    }

    #[Override]
    public function deleteByTimetableLane(TimetableLane $timetableLane): int
    {
        return $timetableLane->tasks()->delete();
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
        $cleanIds = array_values(array_unique(array_map('intval', $staffIds)));
        if ($cleanIds === []) {
            return new Collection;
        }

        return DB::transaction(function () use ($task, $cleanIds, $roleDescription): Collection {
            $now = now()->format('Y-m-d H:i:s');
            $rows = array_map(fn (int $staffId): array => [
                'timetable_task_id' => $task->id,
                'staff_id' => $staffId,
                'role_description' => $roleDescription,
                'attendance_status' => 'assigned',
                'created_at' => $now,
                'updated_at' => $now,
            ], $cleanIds);

            TaskStaffAssignment::upsert(
                $rows,
                ['timetable_task_id', 'staff_id'],
                ['role_description', 'attendance_status', 'updated_at']
            );

            return TaskStaffAssignment::where('timetable_task_id', $task->id)
                ->whereIn('staff_id', $cleanIds)
                ->get();
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
    public function searchQuick(string $keyword, int $limit = 5): Collection
    {
        return TimetableTask::with(['timetable', 'timetableLane.lane'])
            ->where(function ($q) use ($keyword): void {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%");
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
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
