<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
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

        $assignmentsByStaff = [];
        foreach ($staffIds as $sId) {
            $assignmentsByStaff[$sId] = collect();
        }

        foreach ($tasks as $task) {
            foreach ($task->assignments as $assignment) {
                if (isset($assignmentsByStaff[$assignment->staff_id])) {
                    $assignmentsByStaff[$assignment->staff_id]->push([
                        'task' => $task,
                        'assignment' => $assignment,
                    ]);
                }
            }
        }

        $timeSlots = TimeSlotHelper::generateForTimetable($timetable);
        $timelineGrid = $this->buildTimelineGrid($timeSlots, $activeStaff, $assignmentsByStaff);

        $checkedInStaffIds = $this->staffCheckInRepository->getStaffIdsCheckedInForTimetable($timetable->id);
        $totalStaffCount = $activeStaff->count();
        $assignedStaffCount = collect($assignmentsByStaff)->filter(fn ($items): bool => $items->isNotEmpty())->count();
        $unassignedStaffCount = $totalStaffCount - $assignedStaffCount;
        $checkedInCount = $activeStaff->filter(
            fn (Staff $s): bool => in_array((int) $s->id, $checkedInStaffIds, true)
        )->count();

        $tasksWithShortage = $tasks->filter(fn (TimetableTask $t): bool => $t->shortage_count > 0)->map(function (TimetableTask $t): array {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'lane' => $t->lane_name_label,
                'start_time' => substr($t->start_time, 0, 5),
                'end_time' => substr($t->end_time, 0, 5),
                'shortage_count' => $t->shortage_count,
                'assigned_staff_ids' => $t->assignments->pluck('staff_id')->all(),
            ];
        })->values();

        $leadLanes = $this->timetableLaneRepository->getLeadTimetableLanesForTimetable($timetable);
        $leadStaffIds = $leadLanes->pluck('lead_staff_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $laneNamesByLeadStaff = $leadLanes->groupBy('lead_staff_id')
            ->map(fn ($lanes): string => $lanes->pluck('name')->join('・'))
            ->all();

        $staffMeta = $this->buildStaffRowMeta($activeStaff, $assignmentsByStaff, $leadStaffIds, $checkedInStaffIds);

        return [
            'timetable' => $timetable,
            'activeStaff' => $activeStaff,
            'timeSlots' => $timeSlots,
            'timelineGrid' => $timelineGrid,
            'assignmentsByStaff' => $assignmentsByStaff,
            'staffMeta' => $staffMeta,
            'tasksWithShortage' => $tasksWithShortage,
            'totalStaffCount' => $totalStaffCount,
            'assignedStaffCount' => $assignedStaffCount,
            'unassignedStaffCount' => $unassignedStaffCount,
            'checkedInCount' => $checkedInCount,
            'leadStaffIds' => $leadStaffIds,
            'laneNamesByLeadStaff' => $laneNamesByLeadStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ];
    }

    /**
     * スタッフ × 時間スロットのグリッドセル（横結合 colspan）を算出
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Staff>  $activeStaff
     * @param  array<int, Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}>>  $assignmentsByStaff
     * @return array<int, array<int, array{type: string, colspan?: int, task?: TimetableTask, assignment?: TaskStaffAssignment, badge_bg?: string, is_self_task_leader?: bool, task_leader_name?: ?string}>>
     */
    public function buildTimelineGrid(array $timeSlots, Collection $activeStaff, array $assignmentsByStaff): array
    {
        $grid = [];
        $totalSlots = count($timeSlots);

        $slotStartMinutes = [];
        foreach ($timeSlots as $sIdx => $slot) {
            $slotStartMinutes[$sIdx] = TimeSlotHelper::hhmmToMinutes((string) $slot['time']);
        }

        foreach ($activeStaff as $staff) {
            $staffGrid = [];
            $coveredUntil = -1;
            $staffAssignments = ($assignmentsByStaff[$staff->id] ?? collect())->map(function (array $item): array {
                $t = $item['task'];

                return [
                    'task' => $t,
                    'assignment' => $item['assignment'],
                    'start_min' => TimeSlotHelper::hhmmToMinutes(substr((string) $t->start_time, 0, 5)),
                    'end_min' => TimeSlotHelper::hhmmToMinutes(substr((string) $t->end_time, 0, 5)),
                ];
            });

            foreach ($timeSlots as $sIdx => $slot) {
                if ($coveredUntil >= $sIdx) {
                    $staffGrid[$sIdx] = ['type' => 'skip'];

                    continue;
                }

                $slotStartMin = $slotStartMinutes[$sIdx];
                $slotEndMin = $slotStartMin + 30;

                // このスロットの時間帯に開始または進行中のタスクを検索
                $match = $staffAssignments->first(
                    fn (array $item): bool => $item['start_min'] >= $slotStartMin && $item['start_min'] < $slotEndMin
                );

                if (! $match) {
                    $match = $staffAssignments->first(
                        fn (array $item): bool => $item['start_min'] <= $slotStartMin && $item['end_min'] > $slotStartMin
                    );
                }

                if ($match) {
                    $task = $match['task'];
                    $assignment = $match['assignment'];
                    $diffMinutes = max(30, $match['end_min'] - $slotStartMin);
                    $span = (int) ceil($diffMinutes / 30);
                    $span = min($span, $totalSlots - $sIdx);
                    $span = max(1, $span);

                    $coveredUntil = $sIdx + $span - 1;

                    $laneRel = $task->timetableLane;
                    $badgeBg = match ($laneRel?->color ?? 'indigo') {
                        'amber' => 'bg-amber-100 border-amber-300 text-amber-900',
                        'sky' => 'bg-sky-100 border-sky-300 text-sky-900',
                        'emerald' => 'bg-emerald-100 border-emerald-300 text-emerald-900',
                        'purple' => 'bg-purple-100 border-purple-400/30 text-purple-900',
                        'pink' => 'bg-pink-100 border-pink-300 text-pink-900',
                        'red' => 'bg-rose-100 border-rose-300 text-rose-900',
                        'orange' => 'bg-orange-100 border-orange-300 text-orange-900',
                        'teal' => 'bg-teal-100 border-teal-300 text-teal-900',
                        default => 'bg-indigo-100 border-indigo-300 text-indigo-900',
                    };

                    $isSelfTaskLeader = $laneRel && (int) $laneRel->lead_staff_id === (int) $staff->id;
                    $taskLeaderName = $laneRel?->leadStaff?->name;

                    $staffGrid[$sIdx] = [
                        'type' => 'task',
                        'colspan' => $span,
                        'task' => $task,
                        'assignment' => $assignment,
                        'badge_bg' => $badgeBg,
                        'is_self_task_leader' => $isSelfTaskLeader,
                        'task_leader_name' => $taskLeaderName,
                    ];
                } else {
                    $staffGrid[$sIdx] = [
                        'type' => 'empty',
                        'colspan' => 1,
                    ];
                }
            }

            $grid[$staff->id] = $staffGrid;
        }

        return $grid;
    }

    /**
     * スタッフ別タイムライン左列の表示メタ情報を構築
     *
     * @param  Collection<int, Staff>  $activeStaff
     * @param  array<int, Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}>>  $assignmentsByStaff
     * @param  array<int, int>  $leadStaffIds
     * @param  array<int, int>|null  $checkedInStaffIds
     * @return array<int, array{
     *     assigned_count: int,
     *     is_checked_in: bool,
     *     is_leader: bool,
     *     total_hours_formatted: string
     * }>
     */
    public function buildStaffRowMeta(
        Collection $activeStaff,
        array $assignmentsByStaff,
        array $leadStaffIds,
        ?array $checkedInStaffIds = null,
    ): array {
        $meta = [];

        foreach ($activeStaff as $staff) {
            $staffAssignments = $assignmentsByStaff[$staff->id] ?? collect();
            $assignedCount = $staffAssignments->count();
            $isCheckedIn = $checkedInStaffIds !== null
                ? in_array((int) $staff->id, $checkedInStaffIds, true)
                : (bool) $staff->is_checked_in;
            $isLeader = in_array($staff->id, $leadStaffIds, true);

            $totalMinutes = 0;
            foreach ($staffAssignments as $item) {
                $t = $item['task'];
                if ($t->start_time && $t->end_time) {
                    $sC = Carbon::parse($t->start_time);
                    $eC = Carbon::parse($t->end_time);
                    $totalMinutes += max(0, $sC->diffInMinutes($eC));
                }
            }
            $totalHoursFormatted = round($totalMinutes / 60, 1).'h';

            $meta[$staff->id] = [
                'assigned_count' => $assignedCount,
                'is_checked_in' => $isCheckedIn,
                'is_leader' => $isLeader,
                'total_hours_formatted' => $totalHoursFormatted,
            ];
        }

        return $meta;
    }
}
