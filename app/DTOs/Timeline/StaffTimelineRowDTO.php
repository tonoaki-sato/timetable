<?php

namespace App\DTOs\Timeline;

use App\Models\Staff;

readonly class StaffTimelineRowDTO
{
    /**
     * @param  list<GanttBarDTO>  $routineBars
     * @param  list<GanttBarDTO>  $spotBars
     * @param  list<array{task_id: int, task_type: string, start_min: int, end_min: int, title: string}>  $assignmentSummary
     */
    public function __construct(
        public Staff $staff,
        public bool $isLeader,
        public bool $isCheckedIn,
        public string $leadLaneNames,
        public string $primaryGroupLane,
        public string $affiliationLabel,
        public int $assignedCount,
        public int $routineCount,
        public int $spotCount,
        public int $actualMinutes,
        public int $grossMinutes,
        public string $actualHoursFormatted,
        public string $grossHoursFormatted,
        public bool $hasSameTypeConflict,
        public int $routineTracksCount,
        public int $spotTracksCount,
        public array $routineBars,
        public array $spotBars,
        public array $assignmentSummary,
    ) {}
}
