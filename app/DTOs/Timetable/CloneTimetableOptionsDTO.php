<?php

namespace App\DTOs\Timetable;

readonly class CloneTimetableOptionsDTO
{
    public function __construct(
        public int $sourceTimetableId,
        public bool $copyLanes = true,
        public bool $copyLaneStaff = true,
        public bool $copyTasks = true,
        public bool $copyTaskAssignments = false,
        public bool $copyLocations = true,
        public bool $copyLogisticsPlans = true,
        public bool $copySiteLayouts = true,
    ) {}

    /**
     * バリデーション済み入力配列から複製オプションDTOを生成
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $copyLanes = (bool) ($validated['copy_lanes'] ?? true);
        $copyLaneStaff = $copyLanes && (bool) ($validated['copy_lane_staff'] ?? true);
        $copyTasks = $copyLanes && (bool) ($validated['copy_tasks'] ?? true);
        $copyTaskAssignments = $copyTasks && (bool) ($validated['copy_task_assignments'] ?? false);

        return new self(
            sourceTimetableId: (int) $validated['source_timetable_id'],
            copyLanes: $copyLanes,
            copyLaneStaff: $copyLaneStaff,
            copyTasks: $copyTasks,
            copyTaskAssignments: $copyTaskAssignments,
            copyLocations: (bool) ($validated['copy_locations'] ?? true),
            copyLogisticsPlans: (bool) ($validated['copy_logistics_plans'] ?? true),
            copySiteLayouts: (bool) ($validated['copy_site_layouts'] ?? true),
        );
    }
}
