<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableGridService
{
    protected TimetableRepositoryInterface $timetableRepository;

    protected LaneRepositoryInterface $laneRepository;

    protected TimetableTaskRepositoryInterface $taskRepository;

    protected StaffRepositoryInterface $staffRepository;

    public function __construct(
        ?TimetableRepositoryInterface $timetableRepository = null,
        ?LaneRepositoryInterface $laneRepository = null,
        ?TimetableTaskRepositoryInterface $taskRepository = null,
        ?StaffRepositoryInterface $staffRepository = null,
    ) {
        $this->timetableRepository = $timetableRepository ?? app(TimetableRepositoryInterface::class);
        $this->laneRepository = $laneRepository ?? app(LaneRepositoryInterface::class);
        $this->taskRepository = $taskRepository ?? app(TimetableTaskRepositoryInterface::class);
        $this->staffRepository = $staffRepository ?? app(StaffRepositoryInterface::class);
    }

    /**
     * 当日進行表（マトリクス画面）用の全表示データを取得
     *
     * @return array<string, mixed>
     */
    public function getMatrixViewData(?int $selectedTimetableId = null): array
    {
        $timetables = $this->timetableRepository->getAll();

        $selectedTimetable = null;
        if ($selectedTimetableId && $selectedTimetableId > 0) {
            $selectedTimetable = $timetables->firstWhere('id', $selectedTimetableId);
        }

        if ($selectedTimetable === null) {
            $selectedTimetable = $timetables->firstWhere('status', 'active') ?? $timetables->first();
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

        $totalStaffCount = $activeStaff->count();
        $assignedStaffCount = collect($assignmentsByStaff)->filter(fn ($items): bool => $items->isNotEmpty())->count();
        $unassignedStaffCount = $totalStaffCount - $assignedStaffCount;
        $checkedInCount = $activeStaff->filter(fn (Staff $s): bool => (bool) $s->is_checked_in)->count();

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

        $staffMeta = $this->buildStaffRowMeta($activeStaff, $assignmentsByStaff, $leadStaffIds);

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
     * スロット × レーンのセル情報（タスク、空枠、rowspan縦結合）を算出
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Lane>  $lanes
     * @param  array<int, Collection<int, TimetableTask>>  $tasksByLane
     * @return array<int, array<int, array{type: string, rowspan?: int, task?: TimetableTask}>>
     */
    public function buildMatrixGrid(array $timeSlots, Collection $lanes, array $tasksByLane): array
    {
        $grid = [];
        $coveredUntil = [];

        foreach ($lanes as $lane) {
            $coveredUntil[$lane->id] = -1;
        }

        $totalSlots = count($timeSlots);

        foreach ($timeSlots as $sIdx => $slot) {
            $slotTime = $slot['time'];
            $slotCarbon = Carbon::createFromFormat('H:i', $slotTime);
            $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

            $grid[$sIdx] = [];

            foreach ($lanes as $lane) {
                // すでに上位スロットのタスクによって縦結合でカバーされている場合
                if (($coveredUntil[$lane->id] ?? -1) >= $sIdx) {
                    $grid[$sIdx][$lane->id] = [
                        'type' => 'skip',
                    ];

                    continue;
                }

                $laneTasks = $tasksByLane[$lane->id] ?? collect();

                // このスロットの時間帯に開始する、または該当するタスクを探索
                $task = $laneTasks->first(function (TimetableTask $t) use ($slotCarbon, $slotEndCarbon) {
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));

                    return $tStart->gte($slotCarbon) && $tStart->lt($slotEndCarbon);
                });

                if (! $task) {
                    // スロット時間帯をまたいで進行中のタスク（開始時刻が以前でまだ処理されていない場合）
                    $task = $laneTasks->first(function (TimetableTask $t) use ($slotCarbon) {
                        $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));
                        $tEnd = Carbon::createFromFormat('H:i', substr($t->end_time, 0, 5));

                        return $tStart->lte($slotCarbon) && $tEnd->gt($slotCarbon);
                    });
                }

                if ($task) {
                    $tEnd = Carbon::createFromFormat('H:i', substr($task->end_time, 0, 5));
                    $diffMinutes = max(30, $slotCarbon->diffInMinutes($tEnd, false));
                    $span = (int) ceil($diffMinutes / 30);

                    // 最大スロット数を超えないように調整
                    $span = min($span, $totalSlots - $sIdx);
                    $span = max(1, $span);

                    $coveredUntil[$lane->id] = $sIdx + $span - 1;

                    $grid[$sIdx][$lane->id] = [
                        'type' => 'task',
                        'rowspan' => $span,
                        'task' => $task,
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
     * 全行・全列に必ずセルを出力し、継続タスクは task_continue として表現
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Lane>  $lanes
     * @param  array<int, Collection<int, TimetableTask>>  $tasksByLane
     * @return array<int, array<int, array{type: string, task?: TimetableTask, has_next?: bool}>>
     */
    public function buildPdfMatrixGrid(array $timeSlots, Collection $lanes, array $tasksByLane): array
    {
        $grid = [];
        $activeTask = [];
        $taskEndsAt = [];

        foreach ($timeSlots as $sIdx => $slot) {
            $slotCarbon = Carbon::createFromFormat('H:i', $slot['time']);
            $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

            $grid[$sIdx] = [];

            foreach ($lanes as $lane) {
                $laneTasks = $tasksByLane[$lane->id] ?? collect();

                // このスロットの時間帯に新規開始するタスクを探索
                $task = $laneTasks->first(function (TimetableTask $t) use ($slotCarbon, $slotEndCarbon) {
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));

                    return $tStart->gte($slotCarbon) && $tStart->lt($slotEndCarbon);
                });

                if ($task) {
                    $tEnd = Carbon::createFromFormat('H:i', substr($task->end_time, 0, 5));
                    $activeTask[$lane->id] = $task;
                    $taskEndsAt[$lane->id] = $tEnd;
                    $hasNext = $tEnd->gt($slotEndCarbon);

                    $grid[$sIdx][$lane->id] = [
                        'type' => 'task',
                        'task' => $task,
                        'has_next' => $hasNext,
                    ];

                    continue;
                }

                // 継続中のタスクがあるかチェック
                if (isset($activeTask[$lane->id], $taskEndsAt[$lane->id])) {
                    if ($taskEndsAt[$lane->id]->gt($slotCarbon)) {
                        $hasNext = $taskEndsAt[$lane->id]->gt($slotEndCarbon);
                        $grid[$sIdx][$lane->id] = [
                            'type' => 'task_continue',
                            'task' => $activeTask[$lane->id],
                            'has_next' => $hasNext,
                        ];

                        continue;
                    }

                    // タスク終了
                    unset($activeTask[$lane->id], $taskEndsAt[$lane->id]);
                }

                // 過去に開始して現在スロットもカバーするタスクのフォールバック探索
                $spanningTask = $laneTasks->first(function (TimetableTask $t) use ($slotCarbon) {
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));
                    $tEnd = Carbon::createFromFormat('H:i', substr($t->end_time, 0, 5));

                    return $tStart->lte($slotCarbon) && $tEnd->gt($slotCarbon);
                });

                if ($spanningTask) {
                    $tEnd = Carbon::createFromFormat('H:i', substr($spanningTask->end_time, 0, 5));
                    $activeTask[$lane->id] = $spanningTask;
                    $taskEndsAt[$lane->id] = $tEnd;
                    $hasNext = $tEnd->gt($slotEndCarbon);

                    $grid[$sIdx][$lane->id] = [
                        'type' => 'task_continue',
                        'task' => $spanningTask,
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

        foreach ($activeStaff as $staff) {
            $staffGrid = [];
            $coveredUntil = -1;
            $staffAssignments = $assignmentsByStaff[$staff->id] ?? collect();

            foreach ($timeSlots as $sIdx => $slot) {
                if ($coveredUntil >= $sIdx) {
                    $staffGrid[$sIdx] = ['type' => 'skip'];

                    continue;
                }

                $slotCarbon = Carbon::createFromFormat('H:i', $slot['time']);
                $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

                // このスロットの時間帯に開始または進行中のタスクを検索
                $match = $staffAssignments->first(function ($item) use ($slotCarbon, $slotEndCarbon) {
                    $t = $item['task'];
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));

                    return $tStart->gte($slotCarbon) && $tStart->lt($slotEndCarbon);
                });

                if (! $match) {
                    $match = $staffAssignments->first(function ($item) use ($slotCarbon) {
                        $t = $item['task'];
                        $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));
                        $tEnd = Carbon::createFromFormat('H:i', substr($t->end_time, 0, 5));

                        return $tStart->lte($slotCarbon) && $tEnd->gt($slotCarbon);
                    });
                }

                if ($match) {
                    $task = $match['task'];
                    $assignment = $match['assignment'];
                    $tEnd = Carbon::createFromFormat('H:i', substr($task->end_time, 0, 5));
                    $diffMinutes = max(30, $slotCarbon->diffInMinutes($tEnd, false));
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
     * スタッフ別タイムライン左列の表示メタ情報を構築
     *
     * @param  Collection<int, Staff>  $activeStaff
     * @param  array<int, Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}>>  $assignmentsByStaff
     * @param  array<int, int>  $leadStaffIds
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
    ): array {
        $meta = [];

        foreach ($activeStaff as $staff) {
            $staffAssignments = $assignmentsByStaff[$staff->id] ?? collect();
            $assignedCount = $staffAssignments->count();
            $isCheckedIn = (bool) $staff->is_checked_in;
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
