<?php

namespace App\Services;

use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Presenters\LanePresenter;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Support\TimeSlotHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableGridService
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository,
        protected TimetableLaneRepositoryInterface $timetableLaneRepository,
        protected LaneRepositoryInterface $laneRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected StaffRepositoryInterface $staffRepository,
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
        $timetableLanes = collect();
        $availableMasterLanes = collect();
        $totalRequired = 0;
        $totalAssigned = 0;
        $satisfiedTasksCount = 0;
        $tasksCount = 0;
        $shortageTasks = collect();

        if ($selectedTimetable !== null) {
            $timetableLanes = $this->timetableLaneRepository->getOrderedWithLeadForTimetable($selectedTimetable);
            $availableMasterLanes = $this->laneRepository->getAvailableForTimetable($selectedTimetable);
            $tasks = $this->taskRepository->getWithAssignmentsAndLaneByTimetableId($selectedTimetable->id);

            $tasksCount = $tasks->count();
            $satisfiedTasksCount = $tasks->filter(fn (TimetableTask $t): bool => $t->is_satisfied)->count();
            $shortageTasks = $tasks->filter(fn (TimetableTask $t): bool => ! $t->is_satisfied)->values();
            $totalRequired = $tasks->sum('required_people_count');
            $totalAssigned = $tasks->sum(fn (TimetableTask $t): int => $t->assigned_count);

            foreach ($timetableLanes as $timetableLane) {
                $tasksByLane[$timetableLane->id] = $tasks->filter(function (TimetableTask $t) use ($timetableLane): bool {
                    return $t->timetable_lane_id === $timetableLane->id;
                })->values();
            }
        }

        $timeSlots = TimeSlotHelper::generateForTimetable($selectedTimetable);
        $grid = $this->buildMatrixGrid($timeSlots, $timetableLanes, $tasksByLane);
        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');
        $activeStaff = $this->staffRepository->getActiveOrderedByAffiliation();

        return [
            'timetables' => $timetables,
            'selectedTimetable' => $selectedTimetable,
            'hasRoad' => $selectedTimetable !== null && $selectedTimetable->hasRoadPeriods(),
            'hasFestival' => $selectedTimetable !== null && $selectedTimetable->hasFestivalPeriods(),
            'timetableLanes' => $timetableLanes,
            'availableMasterLanes' => $availableMasterLanes,
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
            'colorOptions' => LanePresenter::COLOR_OPTIONS,
        ];
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
     * @param  Collection<int, TimetableLane>  $lanes
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

                $found = $this->findNextMatchingCluster(
                    $clustersByLane[$lane->id],
                    $renderedClusters[$lane->id],
                    $slotCarbon,
                    $slotEndCarbon
                );

                if ($found !== null) {
                    $matchedIdx = $found['index'];
                    $matchedCluster = $found['cluster'];
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
     * @param  Collection<int, TimetableLane>  $lanes
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
                $found = $this->findNextMatchingCluster(
                    $clustersByLane[$lane->id],
                    $renderedClusters[$lane->id],
                    $slotCarbon,
                    $slotEndCarbon
                );

                if ($found !== null) {
                    $matchedIdx = $found['index'];
                    $matchedCluster = $found['cluster'];
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
     * 指定スロットの時間帯と重なる未描画の最初のクラスタを検索して返却
     *
     * @param  array<int, array{start: Carbon, end: Carbon, tasks: Collection<int, TimetableTask>}>  $clusters
     * @param  array<int, bool>  $renderedFlags
     * @return array{index: int, cluster: array{start: Carbon, end: Carbon, tasks: Collection<int, TimetableTask>}}|null
     */
    protected function findNextMatchingCluster(
        array $clusters,
        array $renderedFlags,
        Carbon $slotCarbon,
        Carbon $slotEndCarbon
    ): ?array {
        foreach ($clusters as $cIdx => $cluster) {
            if (isset($renderedFlags[$cIdx])) {
                continue;
            }
            if ($cluster['start']->lt($slotEndCarbon) && $cluster['end']->gt($slotCarbon)) {
                return [
                    'index' => $cIdx,
                    'cluster' => $cluster,
                ];
            }
        }

        return null;
    }
}
