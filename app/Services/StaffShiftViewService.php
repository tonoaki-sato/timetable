<?php

namespace App\Services;

use App\DTOs\Staff\MyShiftLaneFilterOptionDTO;
use App\DTOs\Staff\MyShiftTimetableOptionDTO;
use App\DTOs\Staff\MyShiftViewDataDTO;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class StaffShiftViewService
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
    ) {}

    /**
     * 現場マイタイムテーブル画面表示用データを構築
     */
    public function getMyShiftViewData(Staff $staff, ?string $timetableFilter = null): MyShiftViewDataDTO
    {
        // マイシフト画面では運行完了（completed）の運行計画を非表示とする
        $allLedLanes = $this->staffRepository->loadShiftRelationsAndGetLedTimetableLanes($staff)
            ->filter(fn (TimetableLane $lane): bool => $lane->timetable !== null && ! $lane->timetable->isCompleted())
            ->values();

        $allAssignments = $staff->assignments
            ->filter(fn (TaskStaffAssignment $a): bool => $a->task?->timetable !== null && ! $a->task->timetable->isCompleted())
            ->sortBy([
                fn (TaskStaffAssignment $a, TaskStaffAssignment $b): int => ($a->task?->timetable?->target_date?->format('Y-m-d') ?? '9999-12-31')
                    <=> ($b->task?->timetable?->target_date?->format('Y-m-d') ?? '9999-12-31'),
                fn (TaskStaffAssignment $a, TaskStaffAssignment $b): int => ($a->task?->start_time ?? '99:99:99')
                    <=> ($b->task?->start_time ?? '99:99:99'),
                fn (TaskStaffAssignment $a, TaskStaffAssignment $b): int => $a->id <=> $b->id,
            ])
            ->values();

        $timetables = $this->collectSortedTimetablesForShift($allAssignments, $allLedLanes);

        $showAllTimetables = $timetableFilter === 'all' && $timetables->isNotEmpty();
        $activeTimetable = $showAllTimetables
            ? null
            : $this->resolveSelectedTimetable($timetables, $timetableFilter);

        $canOperateActiveTimetable = ! $showAllTimetables
            && $activeTimetable !== null
            && $activeTimetable->canOperateOnMyShift();
        $canCheckInActiveTimetable = $canOperateActiveTimetable;
        $isBeforeOperationDayActiveTimetable = ! $showAllTimetables
            && $activeTimetable !== null
            && ! $activeTimetable->canOperateOnMyShift();
        $allTimetablesReadOnly = $timetables->isNotEmpty()
            && $timetables->every(fn (Timetable $t): bool => ! $t->canOperateOnMyShift());
        $activeTimetableCheckIn = $canCheckInActiveTimetable
            ? $staff->checkInForTimetable($activeTimetable->id)
            : null;

        $timetableFilterValue = $showAllTimetables
            ? 'all'
            : ($activeTimetable ? (string) $activeTimetable->id : 'all');

        $timetableOptions = $this->buildTimetableOptions(
            $timetables,
            $allAssignments,
            $allLedLanes,
            $activeTimetable?->id
        );

        $assignments = $this->filterAssignmentsByTimetable($allAssignments, $activeTimetable, $showAllTimetables);
        $ledLanes = $this->filterLedLanesByTimetable($allLedLanes, $activeTimetable, $showAllTimetables);

        $resolvedTasks = $this->resolveNowAndNextTasks($assignments);
        $groupedAssignments = $assignments->groupBy(
            fn (TaskStaffAssignment $a): string => $a->date_header_label
        );
        $laneFilters = $this->buildLaneFilters($assignments);

        $totalAssignmentCount = $assignments->count();
        $completedAssignmentCount = $assignments->filter(
            fn (TaskStaffAssignment $a): bool => $a->is_completed
        )->count();
        $progressPercent = $totalAssignmentCount > 0
            ? (int) round(($completedAssignmentCount / $totalAssignmentCount) * 100)
            : 0;

        $pendingLeaderRequestCount = (int) $ledLanes->sum(
            fn (TimetableLane $lane): int => $lane->laneRequests->where('status', 'pending')->count()
        );

        return new MyShiftViewDataDTO(
            staff: $staff,
            timetableOptions: $timetableOptions,
            activeTimetable: $activeTimetable,
            activeTimetableCheckIn: $activeTimetableCheckIn,
            canCheckInActiveTimetable: $canCheckInActiveTimetable,
            canOperateActiveTimetable: $canOperateActiveTimetable,
            isBeforeOperationDayActiveTimetable: $isBeforeOperationDayActiveTimetable,
            allTimetablesReadOnly: $allTimetablesReadOnly,
            showAllTimetables: $showAllTimetables,
            timetableFilterValue: $timetableFilterValue,
            assignments: $assignments,
            groupedAssignments: $groupedAssignments,
            laneFilters: $laneFilters,
            currentTask: $resolvedTasks['current_task'],
            nextTask: $resolvedTasks['next_task'],
            ledLanes: $ledLanes,
            totalAssignmentCount: $totalAssignmentCount,
            completedAssignmentCount: $completedAssignmentCount,
            progressPercent: $progressPercent,
            pendingLeaderRequestCount: $pendingLeaderRequestCount,
        );
    }

    /**
     * スタッフのアサイン情報一覧から現在時刻に基づく Now & Next タスクを判定
     *
     * @param  iterable<TaskStaffAssignment>  $assignments
     * @return array{
     *     current_task: ?TaskStaffAssignment,
     *     next_task: ?TaskStaffAssignment
     * }
     */
    public function resolveNowAndNextTasks(iterable $assignments, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $todayDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $currentTask = null;
        $nextTask = null;

        foreach ($assignments as $assignment) {
            $task = $assignment->task;
            if (! $task || ! $task->timetable) {
                continue;
            }

            $taskDate = $task->timetable->target_date?->format('Y-m-d');

            // 当日のタスクまたは直近のタスクを対象に判定
            if ($taskDate === $todayDate) {
                if ($task->start_time <= $currentTime && ($task->end_time === null || $task->end_time >= $currentTime)) {
                    if (! $currentTask) {
                        $currentTask = $assignment;
                    }
                } elseif ($task->start_time > $currentTime) {
                    if (! $nextTask) {
                        $nextTask = $assignment;
                    }
                }
            } elseif ($taskDate > $todayDate && ! $currentTask && ! $nextTask) {
                // 当日にタスクがなく未来日のタスクがある場合、最初のタスクを次の予定とする
                $nextTask = $assignment;
            }
        }

        return [
            'current_task' => $currentTask,
            'next_task' => $nextTask,
        ];
    }

    /**
     * スタッフに関係する運行日（個人アサイン・班長担当）を重複排除して日付昇順に整理
     *
     * @param  SupportCollection<int, TaskStaffAssignment>  $assignments
     * @param  SupportCollection<int, TimetableLane>  $ledLanes
     * @return SupportCollection<int, Timetable>
     */
    protected function collectSortedTimetablesForShift(SupportCollection $assignments, SupportCollection $ledLanes): SupportCollection
    {
        /** @var SupportCollection<int, Timetable> $byId */
        $byId = collect();

        foreach ($assignments as $assignment) {
            $timetable = $assignment->task?->timetable;
            if ($timetable && ! $timetable->isCompleted() && ! $byId->has($timetable->id)) {
                $byId->put($timetable->id, $timetable);
            }
        }

        foreach ($ledLanes as $lane) {
            $timetable = $lane->timetable;
            if ($timetable && ! $timetable->isCompleted() && ! $byId->has($timetable->id)) {
                $byId->put($timetable->id, $timetable);
            }
        }

        return $byId->sortBy([
            fn (Timetable $a, Timetable $b): int => ($a->target_date?->format('Y-m-d') ?? '9999-12-31')
                <=> ($b->target_date?->format('Y-m-d') ?? '9999-12-31'),
            fn (Timetable $a, Timetable $b): int => $a->id <=> $b->id,
        ])->values();
    }

    /**
     * 表示対象の運行日を決定（指定ID優先、未指定時は 本日 > 直近未来 > 直近過去）
     *
     * @param  SupportCollection<int, Timetable>  $timetables
     */
    protected function resolveSelectedTimetable(SupportCollection $timetables, ?string $timetableFilter): ?Timetable
    {
        if ($timetables->isEmpty()) {
            return null;
        }

        if ($timetableFilter !== null && ctype_digit($timetableFilter)) {
            $matched = $timetables->firstWhere('id', (int) $timetableFilter);
            if ($matched) {
                return $matched;
            }
        }

        $todayDate = now()->format('Y-m-d');

        $todayMatch = $timetables->first(
            fn (Timetable $t): bool => $t->target_date?->format('Y-m-d') === $todayDate
        );
        if ($todayMatch) {
            return $todayMatch;
        }

        $futureMatch = $timetables->first(
            fn (Timetable $t): bool => $t->target_date !== null && $t->target_date->format('Y-m-d') > $todayDate
        );
        if ($futureMatch) {
            return $futureMatch;
        }

        $pastMatch = $timetables->last(
            fn (Timetable $t): bool => $t->target_date !== null && $t->target_date->format('Y-m-d') < $todayDate
        );

        return $pastMatch ?? $timetables->first();
    }

    /**
     * 運行日スイッチャー用の選択肢DTOリストを構築
     *
     * @param  SupportCollection<int, Timetable>  $timetables
     * @param  SupportCollection<int, TaskStaffAssignment>  $allAssignments
     * @param  SupportCollection<int, TimetableLane>  $allLedLanes
     * @return SupportCollection<int, MyShiftTimetableOptionDTO>
     */
    protected function buildTimetableOptions(
        SupportCollection $timetables,
        SupportCollection $allAssignments,
        SupportCollection $allLedLanes,
        ?int $selectedTimetableId
    ): SupportCollection {
        $todayDate = now()->format('Y-m-d');

        $assignmentCounts = $allAssignments
            ->filter(fn (TaskStaffAssignment $a): bool => $a->task !== null)
            ->groupBy(fn (TaskStaffAssignment $a): int => (int) $a->task->timetable_id)
            ->map->count();

        $ledLaneCounts = $allLedLanes
            ->groupBy(fn (TimetableLane $lane): int => (int) $lane->timetable_id)
            ->map->count();

        return $timetables->map(function (Timetable $timetable) use (
            $todayDate,
            $assignmentCounts,
            $ledLaneCounts,
            $selectedTimetableId
        ): MyShiftTimetableOptionDTO {
            $dateStr = $timetable->target_date?->format('Y-m-d');
            [$temporalStatus, $temporalLabel] = match (true) {
                $dateStr === null => ['unset', '未定'],
                $dateStr === $todayDate => ['today', '本日'],
                $dateStr > $todayDate => ['future', '予定'],
                default => ['past', '過去'],
            };

            $weekday = $timetable->weekday_label;
            $dateLabel = $timetable->target_date
                ? $timetable->target_date->format('Y年n月j日').($weekday !== '' ? "（{$weekday}）" : '')
                : '日付未設定';
            $shortDateLabel = $timetable->target_date
                ? $timetable->target_date->format('n/j').($weekday !== '' ? "({$weekday})" : '')
                : '未定';

            return new MyShiftTimetableOptionDTO(
                id: $timetable->id,
                name: $timetable->name,
                dateLabel: $dateLabel,
                shortDateLabel: $shortDateLabel,
                weekdayLabel: $weekday,
                temporalStatus: $temporalStatus,
                temporalLabel: $temporalLabel,
                assignmentCount: (int) ($assignmentCounts->get($timetable->id) ?? 0),
                ledLaneCount: (int) ($ledLaneCounts->get($timetable->id) ?? 0),
                isSelected: $selectedTimetableId === $timetable->id,
            );
        })->values();
    }

    /**
     * @param  SupportCollection<int, TaskStaffAssignment>  $allAssignments
     * @return SupportCollection<int, TaskStaffAssignment>
     */
    protected function filterAssignmentsByTimetable(
        SupportCollection $allAssignments,
        ?Timetable $activeTimetable,
        bool $showAllTimetables
    ): SupportCollection {
        if ($showAllTimetables || ! $activeTimetable) {
            return $allAssignments;
        }

        return $allAssignments
            ->filter(fn (TaskStaffAssignment $a): bool => (int) $a->task?->timetable_id === $activeTimetable->id)
            ->values();
    }

    /**
     * @param  SupportCollection<int, TimetableLane>  $allLedLanes
     * @return SupportCollection<int, TimetableLane>
     */
    protected function filterLedLanesByTimetable(
        SupportCollection $allLedLanes,
        ?Timetable $activeTimetable,
        bool $showAllTimetables
    ): SupportCollection {
        if ($showAllTimetables || ! $activeTimetable) {
            return $allLedLanes;
        }

        return $allLedLanes
            ->filter(fn (TimetableLane $lane): bool => (int) $lane->timetable_id === $activeTimetable->id)
            ->values();
    }

    /**
     * 表示中アサインから担当班フィルター選択肢一覧を抽出
     *
     * @param  SupportCollection<int, TaskStaffAssignment>  $assignments
     * @return SupportCollection<int, MyShiftLaneFilterOptionDTO>
     */
    protected function buildLaneFilters(SupportCollection $assignments): SupportCollection
    {
        $laneMeta = [];

        foreach ($assignments as $assignment) {
            $lane = $assignment->task?->timetableLane;
            if (! $lane) {
                continue;
            }

            if (! isset($laneMeta[$lane->id])) {
                $laneMeta[$lane->id] = [
                    'lane' => $lane,
                    'count' => 0,
                ];
            }
            $laneMeta[$lane->id]['count']++;
        }

        return collect($laneMeta)
            ->sortBy(fn (array $item): int => (int) $item['lane']->display_order)
            ->map(fn (array $item): MyShiftLaneFilterOptionDTO => new MyShiftLaneFilterOptionDTO(
                id: $item['lane']->id,
                name: $item['lane']->name,
                icon: $item['lane']->icon ?: '📋',
                assignmentCount: $item['count'],
            ))
            ->values();
    }
}
