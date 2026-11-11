<?php

namespace App\DTOs\Staff;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\StaffCheckIn;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use Illuminate\Support\Collection;

readonly class MyShiftViewDataDTO
{
    /**
     * @param  Collection<int, MyShiftTimetableOptionDTO>  $timetableOptions
     * @param  Collection<int, TaskStaffAssignment>  $assignments
     * @param  Collection<string, Collection<int, TaskStaffAssignment>>  $groupedAssignments
     * @param  Collection<int, MyShiftLaneFilterOptionDTO>  $laneFilters
     * @param  Collection<int, Lane>  $ledLanes
     */
    public function __construct(
        public Staff $staff,
        public Collection $timetableOptions,
        public ?Timetable $activeTimetable,
        public ?StaffCheckIn $activeTimetableCheckIn,
        public bool $canCheckInActiveTimetable,
        public bool $canOperateActiveTimetable,
        public bool $isBeforeOperationDayActiveTimetable,
        public bool $allTimetablesReadOnly,
        public bool $showAllTimetables,
        public string $timetableFilterValue,
        public Collection $assignments,
        public Collection $groupedAssignments,
        public Collection $laneFilters,
        public ?TaskStaffAssignment $currentTask,
        public ?TaskStaffAssignment $nextTask,
        public Collection $ledLanes,
        public int $totalAssignmentCount,
        public int $completedAssignmentCount,
        public int $progressPercent,
        public int $pendingLeaderRequestCount,
    ) {}

    /**
     * ビューへ展開する配列形式に変換
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'viewData' => $this,
            'staff' => $this->staff,
            'timetableOptions' => $this->timetableOptions,
            'activeTimetable' => $this->activeTimetable,
            'activeTimetableCheckIn' => $this->activeTimetableCheckIn,
            'canCheckInActiveTimetable' => $this->canCheckInActiveTimetable,
            'canOperateActiveTimetable' => $this->canOperateActiveTimetable,
            'isBeforeOperationDayActiveTimetable' => $this->isBeforeOperationDayActiveTimetable,
            'allTimetablesReadOnly' => $this->allTimetablesReadOnly,
            'showAllTimetables' => $this->showAllTimetables,
            'timetableFilterValue' => $this->timetableFilterValue,
            'assignments' => $this->assignments,
            'groupedAssignments' => $this->groupedAssignments,
            'laneFilters' => $this->laneFilters,
            'currentTask' => $this->currentTask,
            'nextTask' => $this->nextTask,
            'ledLanes' => $this->ledLanes,
            'totalAssignmentCount' => $this->totalAssignmentCount,
            'completedAssignmentCount' => $this->completedAssignmentCount,
            'progressPercent' => $this->progressPercent,
            'pendingLeaderRequestCount' => $this->pendingLeaderRequestCount,
        ];
    }
}
