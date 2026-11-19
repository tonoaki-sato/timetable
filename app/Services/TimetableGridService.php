<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\StaffCheckInRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableGridService
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository,
        protected LaneRepositoryInterface $laneRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected StaffRepositoryInterface $staffRepository,
        protected StaffCheckInRepositoryInterface $staffCheckInRepository,
    ) {}

    /**
     * 当日進行表（マトリクス画面）用の全表示データを取得
     *
     * @return array<string, mixed>
     */
    public function getMatrixViewData(?int $selectedTimetableId = null): array
    {
        $timetables = $this->timetableRepository->getSelectableTimetables();

        $selectedTimetable = null;
        if ($selectedTimetableId && $selectedTimetableId > 0) {
            $selectedTimetable = $this->timetableRepository->findById($selectedTimetableId);
        }

        if ($selectedTimetable === null) {
            $selectedTimetable = $this->timetableRepository->findFirstActiveOrEarliest();
        }

        $tasksByLane = [];
        $lanes = collect();
        $totalRequired = 0;
        $totalAssigned = 0;
        $satisfiedTasksCount = 0;
        $tasksCount = 0;
        $shortageTasks = collect();

        if ($selectedTimetable !== null) {
            $lanes = $this->laneRepository->getOrderedWithLeadForTimetable($selectedTimetable);
            $tasks = $this->taskRepository->getWithAssignmentsAndLaneByTimetableId($selectedTimetable->id);

            $tasksCount = $tasks->count();
            $satisfiedTasksCount = $tasks->filter(fn (TimetableTask $t): bool => $t->is_satisfied)->count();
            $shortageTasks = $tasks->filter(fn (TimetableTask $t): bool => ! $t->is_satisfied)->values();
            $totalRequired = $tasks->sum('required_people_count');
            $totalAssigned = $tasks->sum(fn (TimetableTask $t): int => $t->assigned_count);

            foreach ($lanes as $lane) {
                $tasksByLane[$lane->id] = $tasks->filter(function (TimetableTask $t) use ($lane): bool {
                    return $t->lane_id === $lane->id;
                })->values();
            }
        }

        $timeSlots = $this->generateTimeSlots($selectedTimetable);
        $grid = $this->buildMatrixGrid($timeSlots, $lanes, $tasksByLane);
        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');
        $activeStaff = $this->staffRepository->getActiveOrderedByAffiliation();

        return [
            'timetables' => $timetables,
            'selectedTimetable' => $selectedTimetable,
            'hasRoad' => $selectedTimetable !== null && $selectedTimetable->hasRoadPeriods(),
            'hasFestival' => $selectedTimetable !== null && $selectedTimetable->hasFestivalPeriods(),
            'lanes' => $lanes,
            'tasksByLane' => $tasksByLane,
            'timeSlots' => $timeSlots,
            'grid' => $grid,
            'nowTime' => $nowTime,
            'tasksCount' => $tasksCount,
            'satisfiedTasksCount' => $satisfiedTasksCount,
            'shortageTasks' => $shortageTasks,
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ];
    }

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

        $timeSlots = $this->generateTimeSlots($timetable);
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
                'lane' => $t->lane ?: ($t->laneRelation?->name ?? '担当班未設定'),
                'start_time' => substr($t->start_time, 0, 5),
                'end_time' => substr($t->end_time, 0, 5),
                'shortage_count' => $t->shortage_count,
                'assigned_staff_ids' => $t->assignments->pluck('staff_id')->all(),
            ];
        })->values();

        $leadLanes = $this->laneRepository->getLeadLanesForTimetable($timetable);
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
     * 運行タイムテーブルの開始・終了時刻から30分刻みの時間スロット配列を生成
     *
     * @return array<int, array{
     *     time: string,
     *     label: string,
     *     road: array{status: string, label: string},
     *     festival: array{status: string, label: string}
     * }>
     */
    public function generateTimeSlots(?Timetable $timetable): array
    {
        $startTime = '07:00';
        $endTime = '19:30';

        if ($timetable !== null) {
            if (! empty($timetable->start_time)) {
                $sH = (int) substr($timetable->start_time, 0, 2);
                $sM = (int) substr($timetable->start_time, 3, 2);
                $sM = $sM >= 30 ? 30 : 0;
                $startTime = sprintf('%02d:%02d', $sH, $sM);
            }
            if (! empty($timetable->end_time)) {
                $eH = (int) substr($timetable->end_time, 0, 2);
                $eM = (int) substr($timetable->end_time, 3, 2);
                if ($eM > 30) {
                    $eH++;
                    $eM = 0;
                } elseif ($eM > 0) {
                    $eM = 30;
                }
                $endTime = sprintf('%02d:%02d', $eH, $eM);
            }
        }

        $slots = [];
        $current = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        $count = 0;
        while ($current->lte($end) && $count < 48) {
            $timeStr = $current->format('H:i');

            $roadData = $timetable !== null
                ? $timetable->getRoadStatusAt($timeStr)
                : ['status' => 'open', 'label' => '-'];

            $festivalData = $timetable !== null
                ? $timetable->getFestivalStatusAt($timeStr)
                : ['status' => 'inactive', 'label' => '-'];

            $slots[] = [
                'time' => $timeStr,
                'label' => $timeStr,
                'road' => $roadData,
                'festival' => $festivalData,
            ];

            $current->addMinutes(30);
            $count++;
        }

        return $slots;
    }

    /**
     * 同一班のタスクリストから、時間帯の全部または一部が重なり合うタスク群（並行タスククラスタ）を構築
     *
     * @param  Collection<int, TimetableTask>  $laneTasks
     * @return array<int, array{start: Carbon, end: Carbon, tasks: Collection<int, TimetableTask>}>
     */
    public function buildLaneTaskClusters(Collection $laneTasks): array
    {
        $sorted = $laneTasks
            ->filter(fn (TimetableTask $t): bool => ! empty($t->start_time) && ! empty($t->end_time))
            ->sort(function (TimetableTask $a, TimetableTask $b): int {
                $sA = substr((string) $a->start_time, 0, 5);
                $sB = substr((string) $b->start_time, 0, 5);
                if ($sA !== $sB) {
                    return $sA <=> $sB;
                }

                $eA = substr((string) $a->end_time, 0, 5);
                $eB = substr((string) $b->end_time, 0, 5);
                if ($eA !== $eB) {
                    return $eA <=> $eB;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        $clusters = [];
        $currentCluster = null;

        foreach ($sorted as $task) {
            $tStart = Carbon::createFromFormat('H:i', substr((string) $task->start_time, 0, 5));
            $tEnd = Carbon::createFromFormat('H:i', substr((string) $task->end_time, 0, 5));

            if ($tStart === false || $tEnd === false) {
                continue;
            }

            if ($currentCluster === null) {
                $currentCluster = [
                    'start' => $tStart,
                    'end' => $tEnd,
                    'tasks' => new Collection([$task]),
                ];

                continue;
            }

            // 前のタスク群の最遅終了時刻より前に開始する場合は同一クラスタへ統合（全部・一部重複対応）
            if ($tStart->lt($currentCluster['end'])) {
                $currentCluster['tasks']->push($task);
                if ($tEnd->gt($currentCluster['end'])) {
                    $currentCluster['end'] = $tEnd;
                }
            } else {
                $clusters[] = $currentCluster;
                $currentCluster = [
                    'start' => $tStart,
                    'end' => $tEnd,
                    'tasks' => new Collection([$task]),
                ];
            }
        }

        if ($currentCluster !== null) {
            $clusters[] = $currentCluster;
        }

        return $clusters;
    }

    /**
     * スロット × レーンのセル情報（タスク・並行タスククラスタ、空枠、rowspan縦結合）を算出
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Lane>  $lanes
     * @param  array<int, Collection<int, TimetableTask>>  $tasksByLane
     * @return array<int, array<int, array{
     *     type: string,
     *     rowspan?: int,
     *     task?: TimetableTask,
     *     tasks?: Collection<int, TimetableTask>
     * }>>
     */
    public function buildMatrixGrid(array $timeSlots, Collection $lanes, array $tasksByLane): array
    {
        $grid = [];
        $coveredUntil = [];
        $clustersByLane = [];
        $renderedClusters = [];

        foreach ($lanes as $lane) {
            $coveredUntil[$lane->id] = -1;
            $clustersByLane[$lane->id] = $this->buildLaneTaskClusters($tasksByLane[$lane->id] ?? new Collection);
            $renderedClusters[$lane->id] = [];
        }

        $totalSlots = count($timeSlots);

        foreach ($timeSlots as $sIdx => $slot) {
            $slotTime = $slot['time'];
            $slotCarbon = Carbon::createFromFormat('H:i', $slotTime);
            $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

            $grid[$sIdx] = [];

            foreach ($lanes as $lane) {
                // すでに上位スロットのタスククラスタによって縦結合でカバーされている場合
                if (($coveredUntil[$lane->id] ?? -1) >= $sIdx) {
                    $grid[$sIdx][$lane->id] = [
                        'type' => 'skip',
                    ];

                    continue;
                }

                $matchedCluster = null;
                $matchedIdx = null;
                foreach ($clustersByLane[$lane->id] as $cIdx => $cluster) {
                    if (isset($renderedClusters[$lane->id][$cIdx])) {
                        continue;
                    }
                    if ($cluster['start']->lt($slotEndCarbon) && $cluster['end']->gt($slotCarbon)) {
                        $matchedCluster = $cluster;
                        $matchedIdx = $cIdx;
                        break;
                    }
                }

                if ($matchedCluster !== null && $matchedIdx !== null) {
                    $renderedClusters[$lane->id][$matchedIdx] = true;

                    $diffMinutes = max(30, $slotCarbon->diffInMinutes($matchedCluster['end'], false));
                    $span = (int) ceil($diffMinutes / 30);

                    // 最大スロット数を超えないように調整
                    $span = min($span, $totalSlots - $sIdx);
                    $span = max(1, $span);

                    $coveredUntil[$lane->id] = $sIdx + $span - 1;

                    $grid[$sIdx][$lane->id] = [
                        'type' => 'task',
                        'rowspan' => $span,
                        'task' => $matchedCluster['tasks']->first(),
                        'tasks' => $matchedCluster['tasks'],
                    ];
                } else {
                    $grid[$sIdx][$lane->id] = [
                        'type' => 'empty',
                        'rowspan' => 1,
                    ];
                }
            }
        }

        return $grid;
    }

    /**
     * PDF出力用：DomPDFの改ページ跨ぎrowspan欠損を防ぐスロット × レーンのグリッドセルを算出
     * 全行・全列に必ずセルを出力し、継続タスク・並行タスククラスタは task_continue として表現
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Lane>  $lanes
     * @param  array<int, Collection<int, TimetableTask>>  $tasksByLane
     * @return array<int, array<int, array{
     *     type: string,
     *     task?: TimetableTask,
     *     tasks?: Collection<int, TimetableTask>,
     *     has_next?: bool
     * }>>
     */
    public function buildPdfMatrixGrid(array $timeSlots, Collection $lanes, array $tasksByLane): array
    {
        $grid = [];
        $activeCluster = [];
        $clustersByLane = [];
        $renderedClusters = [];

        foreach ($lanes as $lane) {
            $clustersByLane[$lane->id] = $this->buildLaneTaskClusters($tasksByLane[$lane->id] ?? new Collection);
            $renderedClusters[$lane->id] = [];
        }

        foreach ($timeSlots as $sIdx => $slot) {
            $slotCarbon = Carbon::createFromFormat('H:i', $slot['time']);
            $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

            $grid[$sIdx] = [];

            foreach ($lanes as $lane) {
                // 継続中のタスククラスタがあるかチェック
                if (isset($activeCluster[$lane->id])) {
                    $cluster = $activeCluster[$lane->id];
                    if ($cluster['end']->gt($slotCarbon)) {
                        $hasNext = $cluster['end']->gt($slotEndCarbon);
                        $grid[$sIdx][$lane->id] = [
                            'type' => 'task_continue',
                            'task' => $cluster['tasks']->first(),
                            'tasks' => $cluster['tasks'],
                            'has_next' => $hasNext,
                        ];

                        continue;
                    }

                    // クラスタ終了
                    unset($activeCluster[$lane->id]);
                }

                // このスロットの時間帯に開始・該当する未描画クラスタを探索
                $matchedCluster = null;
                $matchedIdx = null;
                foreach ($clustersByLane[$lane->id] as $cIdx => $cluster) {
                    if (isset($renderedClusters[$lane->id][$cIdx])) {
                        continue;
                    }
                    if ($cluster['start']->lt($slotEndCarbon) && $cluster['end']->gt($slotCarbon)) {
                        $matchedCluster = $cluster;
                        $matchedIdx = $cIdx;
                        break;
                    }
                }

                if ($matchedCluster !== null && $matchedIdx !== null) {
                    $renderedClusters[$lane->id][$matchedIdx] = true;
                    $activeCluster[$lane->id] = $matchedCluster;
                    $hasNext = $matchedCluster['end']->gt($slotEndCarbon);

                    $grid[$sIdx][$lane->id] = [
                        'type' => 'task',
                        'task' => $matchedCluster['tasks']->first(),
                        'tasks' => $matchedCluster['tasks'],
                        'has_next' => $hasNext,
                    ];

                    continue;
                }

                $grid[$sIdx][$lane->id] = [
                    'type' => 'empty',
                ];
            }
        }

        return $grid;
    }

    /**
     * スタッフ × 時間スロットのグリッドセル（横結合 colspan）を算出
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Staff>  $activeStaff
     * @param  array<int, Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}>>  $assignmentsByStaff
     * @return array<int, array<int, array{type: string, colspan?: int, task?: TimetableTask, assignment?: TaskStaffAssignment}>>
     */
    public function buildTimelineGrid(array $timeSlots, Collection $activeStaff, array $assignmentsByStaff): array
    {
        $grid = [];
        $totalSlots = count($timeSlots);

        $slotStartMinutes = [];
        foreach ($timeSlots as $sIdx => $slot) {
            $slotStartMinutes[$sIdx] = $this->hhmmToMinutes((string) $slot['time']);
        }

        foreach ($activeStaff as $staff) {
            $staffGrid = [];
            $coveredUntil = -1;
            $staffAssignments = ($assignmentsByStaff[$staff->id] ?? collect())->map(function (array $item): array {
                $t = $item['task'];

                return [
                    'task' => $t,
                    'assignment' => $item['assignment'],
                    'start_min' => $this->hhmmToMinutes(substr((string) $t->start_time, 0, 5)),
                    'end_min' => $this->hhmmToMinutes(substr((string) $t->end_time, 0, 5)),
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

                    $laneRel = $task->laneRelation;
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
     * HH:MM 形式文字列を 00:00 からの経過分数へ変換
     */
    private function hhmmToMinutes(string $hhmm): int
    {
        $parts = explode(':', $hhmm);
        if (count($parts) < 2) {
            return 0;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
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
