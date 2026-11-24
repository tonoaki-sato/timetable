<?php

namespace App\DTOs\Timeline;

use App\Enums\TimetableTaskType;
use App\Models\TimetableTask;

readonly class GanttBarDTO
{
    /**
     * @param  array<string, mixed>  $modalPayload
     * @param  list<string>  $assigneeNames
     */
    public function __construct(
        public int $taskId,
        public ?int $assignmentId,
        public int $timetableLaneId,
        public string $laneName,
        public string $laneIcon,
        public string $laneColor,
        public TimetableTaskType $taskType,
        public int $subTrack,
        public int $startMin,
        public int $endMin,
        public string $startTimeLabel,
        public string $endTimeLabel,
        public string $title,
        public ?string $location,
        public int $requiredPeopleCount,
        public int $assignedCount,
        public int $shortageCount,
        public bool $isSatisfied,
        public bool $isCritical,
        public string $status,
        public string $barClass,
        public bool $hasSameTypeOverlap,
        public bool $isSelfTaskLeader,
        public ?string $taskLeaderName,
        public array $assigneeNames,
        public array $modalPayload,
    ) {}

    public static function fromTask(
        TimetableTask $task,
        int $subTrack,
        int $startMin,
        int $endMin,
        string $barClass,
        bool $hasSameTypeOverlap = false,
        ?int $assignmentId = null,
        bool $isSelfTaskLeader = false,
    ): self {
        $laneRel = $task->timetableLane;
        $assigneeNames = $task->assignments
            ->filter(fn ($a): bool => $a->attendance_status !== 'absent' && $a->staff !== null)
            ->map(fn ($a): string => (string) $a->staff->name)
            ->values()
            ->all();

        return new self(
            taskId: (int) $task->id,
            assignmentId: $assignmentId,
            timetableLaneId: (int) $task->timetable_lane_id,
            laneName: $task->presenter()->laneNameLabel(),
            laneIcon: $task->presenter()->laneIconLabel(),
            laneColor: (string) ($laneRel?->color ?? 'indigo'),
            taskType: $task->task_type_enum,
            subTrack: $subTrack,
            startMin: $startMin,
            endMin: $endMin,
            startTimeLabel: substr((string) $task->start_time, 0, 5),
            endTimeLabel: substr((string) $task->end_time, 0, 5),
            title: (string) $task->title,
            location: $task->location,
            requiredPeopleCount: (int) $task->required_people_count,
            assignedCount: (int) $task->assigned_count,
            shortageCount: (int) $task->shortage_count,
            isSatisfied: (bool) $task->is_satisfied,
            isCritical: (bool) $task->is_critical,
            status: (string) $task->status,
            barClass: $barClass,
            hasSameTypeOverlap: $hasSameTypeOverlap,
            isSelfTaskLeader: $isSelfTaskLeader,
            taskLeaderName: $laneRel?->leadStaff?->name,
            assigneeNames: $assigneeNames,
            modalPayload: $task->presenter()->toModalPayload($laneRel),
        );
    }
}
