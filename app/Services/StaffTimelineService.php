<?php

namespace App\Services;

use App\DTOs\Timeline\GanttBarDTO;
use App\DTOs\Timeline\StaffTimelineRowDTO;
use App\Enums\TimetableTaskStatus;
use App\Enums\TimetableTaskType;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Presenters\LanePresenter;
use App\Repositories\Contracts\StaffCheckInRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Support\TimeSlotHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffTimelineService
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected TimetableLaneRepositoryInterface $timetableLaneRepository,
        protected StaffCheckInRepositoryInterface $staffCheckInRepository,
    ) {}

    /**
     * スタッフ別リソースタイムライン画面用の全表示データを取得
     *
     * @return array<string, mixed>
     */
    public function getStaffTimelineViewData(Timetable $timetable): array
    {
        $activeStaff = $this->staffRepository->getActiveOrderedByAffiliation();
        $staffIds = $activeStaff->pluck('id')->all();

        $tasks = $this->taskRepository->getForTimelineByTimetableId($timetable->id);
        $timetableLanes = $this->timetableLaneRepository->getForTimetable($timetable);

        $assignmentsByStaff = [];
        foreach ($staffIds as $sId) {
            $assignmentsByStaff[$sId] = collect();
        }

        foreach ($tasks as $task) {
            foreach ($task->assignments as $assignment) {
                if ($assignment->attendance_status === 'absent') {
                    continue;
                }
                if (isset($assignmentsByStaff[$assignment->staff_id])) {
                    $assignmentsByStaff[$assignment->staff_id]->push([
                        'task' => $task,
                        'assignment' => $assignment,
                    ]);
                }
            }
        }

        $checkedInStaffIds = $this->staffCheckInRepository->getStaffIdsCheckedInForTimetable($timetable->id);
        $leadLanes = $this->timetableLaneRepository->getLeadTimetableLanesForTimetable($timetable);
        $leadStaffIds = $leadLanes->pluck('lead_staff_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $laneNamesByLeadStaff = $leadLanes->groupBy('lead_staff_id')
            ->map(fn ($lanes): string => $lanes->pluck('name')->join('・'))
            ->all();
        $firstLeadLaneNameByStaff = $leadLanes->groupBy('lead_staff_id')
            ->map(fn ($lanes): string => (string) ($lanes->first()?->name ?? ''))
            ->all();

        $fixedLaneNameByStaff = [];
        foreach ($timetableLanes as $tl) {
            foreach ($tl->fixedStaff as $fs) {
                $fixedLaneNameByStaff[(int) $fs->id] ??= (string) $tl->name;
            }
        }

        $bounds = TimeSlotHelper::resolveTimelineBoundsMinutes($timetable, $tasks);
        $periodBands = TimeSlotHelper::extractPeriodBands($timetable, $bounds['start_min'], $bounds['end_min']);

        /** @var list<StaffTimelineRowDTO> $staffRows */
        $staffRows = [];
        foreach ($activeStaff as $staff) {
            $staffId = (int) $staff->id;
            /** @var Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}> $staffAssignments */
            $staffAssignments = $assignmentsByStaff[$staffId] ?? collect();
            $isCheckedIn = in_array($staffId, $checkedInStaffIds, true);
            $isLeader = in_array($staffId, $leadStaffIds, true);

            $routineItems = [];
            $spotItems = [];
            $allIntervals = [];
            $grossMinutes = 0;
            $assignmentSummary = [];
            $laneCounts = [];

            foreach ($staffAssignments as $item) {
                $t = $item['task'];
                $a = $item['assignment'];

                $sMin = TimeSlotHelper::hhmmToMinutes(substr((string) $t->start_time, 0, 5));
                $eMin = max($sMin + 15, TimeSlotHelper::hhmmToMinutes(substr((string) $t->end_time, 0, 5)));
                $rawDuration = max(0, TimeSlotHelper::hhmmToMinutes(substr((string) $t->end_time, 0, 5)) - $sMin);

                $grossMinutes += $rawDuration;
                $allIntervals[] = [$sMin, TimeSlotHelper::hhmmToMinutes(substr((string) $t->end_time, 0, 5))];

                $laneName = $t->presenter()->laneNameLabel();
                $laneCounts[$laneName] = ($laneCounts[$laneName] ?? 0) + 1;

                $assignmentSummary[] = [
                    'task_id' => (int) $t->id,
                    'task_type' => $t->task_type_enum->value,
                    'start_min' => $sMin,
                    'end_min' => $eMin,
                    'title' => (string) $t->title,
                ];

                $packedInput = [
                    'id' => (int) $t->id,
                    'start_min' => $sMin,
                    'end_min' => $eMin,
                    'task' => $t,
                    'assignment' => $a,
                ];

                if ($t->isRoutine()) {
                    $routineItems[] = $packedInput;
                } else {
                    $spotItems[] = $packedInput;
                }
            }

            $packedRoutine = TimeSlotHelper::packIntervalsIntoTracks($routineItems);
            $packedSpot = TimeSlotHelper::packIntervalsIntoTracks($spotItems);

            $hasSameTypeConflict = false;
            $buildBars = static function (array $packedItems, bool $isRoutine) use ($staffId, &$hasSameTypeConflict): array {
                return array_map(static function (array $packed) use ($staffId, $isRoutine, &$hasSameTypeConflict): GanttBarDTO {
                    /** @var TimetableTask $t */
                    $t = $packed['task'];
                    /** @var TaskStaffAssignment $a */
                    $a = $packed['assignment'];
                    $laneRel = $t->timetableLane;
                    $laneColor = (string) ($laneRel?->color ?? 'indigo');
                    $isSelfTaskLeader = $laneRel && (int) $laneRel->lead_staff_id === $staffId;
                    $hasOverlap = (bool) $packed['has_overlap'];
                    if ($hasOverlap) {
                        $hasSameTypeConflict = true;
                    }

                    $barClass = $isRoutine
                        ? LanePresenter::timelineRoutineBarClassFor($laneColor)
                        : LanePresenter::timelineSpotBarClassFor($laneColor);

                    return GanttBarDTO::fromTask(
                        task: $t,
                        subTrack: (int) $packed['sub_track'],
                        startMin: (int) $packed['start_min'],
                        endMin: (int) $packed['end_min'],
                        barClass: $barClass,
                        hasSameTypeOverlap: $hasOverlap,
                        assignmentId: (int) $a->id,
                        isSelfTaskLeader: $isSelfTaskLeader,
                    );
                }, $packedItems);
            };

            $routineBars = $buildBars($packedRoutine['items'], true);
            $spotBars = $buildBars($packedSpot['items'], false);

            $actualMinutes = TimeSlotHelper::calculateMergedDurationMinutes($allIntervals);
            $actualHoursFormatted = number_format($actualMinutes / 60, 1).'h';
            $grossHoursFormatted = number_format($grossMinutes / 60, 1).'h';

            // グルーピング用主担当班（班長 > 固定スタッフ > 最頻アサイン班 > 所属/未配属）
            $primaryGroupLane = $firstLeadLaneNameByStaff[$staffId]
                ?? $fixedLaneNameByStaff[$staffId]
                ?? '';
            if ($primaryGroupLane === '' && $laneCounts !== []) {
                arsort($laneCounts);
                $primaryGroupLane = (string) array_key_first($laneCounts);
            }
            if ($primaryGroupLane === '') {
                $primaryGroupLane = '未配属・フリー';
            }

            $staffRows[] = new StaffTimelineRowDTO(
                staff: $staff,
                isLeader: $isLeader,
                isCheckedIn: $isCheckedIn,
                leadLaneNames: (string) ($laneNamesByLeadStaff[$staffId] ?? ''),
                primaryGroupLane: $primaryGroupLane,
                affiliationLabel: (string) ($staff->affiliation ?: '所属なし'),
                assignedCount: $staffAssignments->count(),
                routineCount: count($routineBars),
                spotCount: count($spotBars),
                actualMinutes: $actualMinutes,
                grossMinutes: $grossMinutes,
                actualHoursFormatted: $actualHoursFormatted,
                grossHoursFormatted: $grossHoursFormatted,
                hasSameTypeConflict: $hasSameTypeConflict,
                routineTracksCount: $packedRoutine['tracks_count'],
                spotTracksCount: $packedSpot['tracks_count'],
                routineBars: $routineBars,
                spotBars: $spotBars,
                assignmentSummary: $assignmentSummary,
            );
        }

        $totalStaffCount = $activeStaff->count();
        $assignedStaffCount = collect($assignmentsByStaff)->filter(fn ($items): bool => $items->isNotEmpty())->count();
        $unassignedStaffCount = $totalStaffCount - $assignedStaffCount;
        $checkedInCount = $activeStaff->filter(
            fn (Staff $s): bool => in_array((int) $s->id, $checkedInStaffIds, true)
        )->count();

        $allTasksData = $tasks->filter(fn (TimetableTask $t): bool => $t->status !== 'cancelled')
            ->map(function (TimetableTask $t): array {
                return [
                    'id' => $t->id,
                    'timetable_lane_id' => $t->timetable_lane_id,
                    'task_type' => $t->task_type_enum->value,
                    'task_type_icon' => $t->presenter()->taskTypeIcon(),
                    'task_type_short' => $t->presenter()->taskTypeShortLabel(),
                    'title' => $t->title,
                    'lane' => $t->presenter()->laneNameLabel(),
                    'start_time' => substr((string) $t->start_time, 0, 5),
                    'end_time' => substr((string) $t->end_time, 0, 5),
                    'start_min' => TimeSlotHelper::hhmmToMinutes(substr((string) $t->start_time, 0, 5)),
                    'end_min' => TimeSlotHelper::hhmmToMinutes(substr((string) $t->end_time, 0, 5)),
                    'required_people_count' => $t->required_people_count,
                    'assigned_count' => $t->assigned_count,
                    'shortage_count' => $t->shortage_count,
                    'is_satisfied' => $t->is_satisfied,
                    'assigned_staff_ids' => $t->assignments->where('attendance_status', '!=', 'absent')->pluck('staff_id')->map(fn ($id): int => (int) $id)->values()->all(),
                ];
            })->values();

        $tasksWithShortage = $allTasksData->filter(fn (array $t): bool => $t['shortage_count'] > 0)->values();

        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');

        return [
            'timetable' => $timetable,
            'timetableLanes' => $timetableLanes,
            'activeStaff' => $activeStaff,
            'staffRows' => $staffRows,
            'bounds' => $bounds,
            'roadBands' => $periodBands['road_bands'],
            'festivalBands' => $periodBands['festival_bands'],
            'nowTime' => $nowTime,
            'nowMin' => TimeSlotHelper::hhmmToMinutes($nowTime),
            'allTasksData' => $allTasksData,
            'tasksWithShortage' => $tasksWithShortage,
            'totalStaffCount' => $totalStaffCount,
            'assignedStaffCount' => $assignedStaffCount,
            'unassignedStaffCount' => $unassignedStaffCount,
            'checkedInCount' => $checkedInCount,
            'leadStaffIds' => $leadStaffIds,
            'laneNamesByLeadStaff' => $laneNamesByLeadStaff,
            'colorOptions' => LanePresenter::COLOR_OPTIONS,
            'statuses' => TimetableTaskStatus::options(),
            'taskTypes' => TimetableTaskType::options(),
        ];
    }
}
