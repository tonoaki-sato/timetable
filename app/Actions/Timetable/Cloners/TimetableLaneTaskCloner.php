<?php

namespace App\Actions\Timetable\Cloners;

use App\Models\Timetable;
use App\Repositories\Contracts\TimetableCloneRepositoryInterface;

class TimetableLaneTaskCloner
{
    public function __construct(
        protected TimetableCloneRepositoryInterface $cloneRepository,
    ) {}

    /**
     * 担当班（レーン）および固定スタッフを複製し、旧ID→新IDのマップを返却
     *
     * @return array<int, int>
     */
    public function cloneLanes(Timetable $source, Timetable $newTimetable, bool $copyLaneStaff): array
    {
        $laneIdMap = [];
        $sourceLanes = $this->cloneRepository->getLanesWithFixedStaff($source->id);

        foreach ($sourceLanes as $sourceLane) {
            $fixedStaffIds = $copyLaneStaff && $sourceLane->fixedStaff->isNotEmpty()
                ? $sourceLane->fixedStaff->pluck('id')->all()
                : [];

            $newLane = $this->cloneRepository->createTimetableLane([
                'timetable_id' => $newTimetable->id,
                'lane_id' => $sourceLane->lane_id,
                'lead_staff_id' => $copyLaneStaff ? $sourceLane->lead_staff_id : null,
                'display_order' => $sourceLane->display_order,
                'notes' => $sourceLane->notes,
            ], $fixedStaffIds);

            $laneIdMap[$sourceLane->id] = $newLane->id;
        }

        return $laneIdMap;
    }

    /**
     * 作業タスクおよびスタッフアサインを複製し、作成件数 [tasks, assignments] を返却
     *
     * @param  array<int, int>  $laneIdMap
     * @return array{0: int, 1: int}
     */
    public function cloneTasks(
        Timetable $source,
        Timetable $newTimetable,
        array $laneIdMap,
        bool $copyTaskAssignments
    ): array {
        $tasksCount = 0;
        $assignmentsCount = 0;

        $sourceTasks = $this->cloneRepository->getTasksWithAssignments($source->id);
        $assignmentRows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($sourceTasks as $sourceTask) {
            $newLaneId = $sourceTask->timetable_lane_id ? ($laneIdMap[$sourceTask->timetable_lane_id] ?? null) : null;
            if (! $newLaneId) {
                continue;
            }

            $newTask = $this->cloneRepository->createTimetableTask([
                'timetable_id' => $newTimetable->id,
                'timetable_lane_id' => $newLaneId,
                'task_type' => $sourceTask->task_type_enum->value,
                'title' => $sourceTask->title,
                'content' => $sourceTask->content,
                'location' => $sourceTask->location,
                'start_time' => $sourceTask->start_time,
                'end_time' => $sourceTask->end_time,
                'required_people_count' => $sourceTask->required_people_count,
                'is_at_least' => $sourceTask->is_at_least,
                'is_critical' => $sourceTask->is_critical,
                'status' => 'not_started',
            ]);
            $tasksCount++;

            if ($copyTaskAssignments) {
                foreach ($sourceTask->assignments as $assignment) {
                    if ($assignment->attendance_status === 'absent') {
                        continue;
                    }

                    $assignmentRows[] = [
                        'timetable_task_id' => $newTask->id,
                        'staff_id' => $assignment->staff_id,
                        'role_description' => $assignment->role_description,
                        'attendance_status' => 'assigned',
                        'notes' => $assignment->notes,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $assignmentsCount++;
                }
            }
        }

        $this->cloneRepository->insertTaskAssignments($assignmentRows);

        return [$tasksCount, $assignmentsCount];
    }
}
