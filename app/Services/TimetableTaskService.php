<?php

namespace App\Services;

use App\DTOs\TimetableTask\CreateCommonTimetableTaskDTO;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TimetableTaskService
{
    protected LaneRepositoryInterface $laneRepository;

    protected StaffRepositoryInterface $staffRepository;

    protected TaskStaffAssignmentRepositoryInterface $assignmentRepository;

    protected TimetableRepositoryInterface $timetableRepository;

    public function __construct(
        protected TimetableTaskRepositoryInterface $taskRepository,
        ?LaneRepositoryInterface $laneRepository = null,
        ?StaffRepositoryInterface $staffRepository = null,
        ?TaskStaffAssignmentRepositoryInterface $assignmentRepository = null,
        ?TimetableRepositoryInterface $timetableRepository = null,
    ) {
        $this->laneRepository = $laneRepository ?? app(LaneRepositoryInterface::class);
        $this->staffRepository = $staffRepository ?? app(StaffRepositoryInterface::class);
        $this->assignmentRepository = $assignmentRepository ?? app(TaskStaffAssignmentRepositoryInterface::class);
        $this->timetableRepository = $timetableRepository ?? app(TimetableRepositoryInterface::class);
    }

    /**
     * アサイン情報付きタスク詳細の取得
     */
    public function getTaskDetails(int $id): ?TimetableTask
    {
        return $this->taskRepository->findWithAssignments($id);
    }

    /**
     * IDによる担当班の取得
     */
    public function findLaneById(int $laneId): ?Lane
    {
        return $this->laneRepository->findById($laneId);
    }

    /**
     * 運行日別作業タスク一覧画面用データの取得
     *
     * @return array<string, mixed>
     */
    public function getTasksIndexViewData(Timetable $timetable): array
    {
        $this->timetableRepository->loadForTasksIndex($timetable);

        return [
            'timetable' => $timetable,
            'lanes' => $timetable->lanes,
            'tasks' => $timetable->lanes->flatMap->tasks,
            'statuses' => TimetableTask::STATUSES,
        ];
    }

    /**
     * 人員アサイン専用ワークスペース画面用データの取得
     *
     * @return array<string, mixed>
     */
    public function getAssignmentWorkspaceViewData(Timetable $timetable, ?int $requestedTaskId = null): array
    {
        $this->timetableRepository->loadForAssignmentWorkspace($timetable);

        $tasks = $timetable->tasks;
        $lanes = $timetable->lanes;

        $totalTasks = $tasks->count();
        $satisfiedTasks = $tasks->filter(fn (TimetableTask $t): bool => $t->is_satisfied)->count();
        $shortageTasks = $totalTasks - $satisfiedTasks;
        $totalRequired = $tasks->sum('required_people_count');
        $totalAssigned = $tasks->sum(fn (TimetableTask $t): int => $t->assigned_count);

        $activeStaff = $this->staffRepository->getActiveOrderedByAffiliation();
        $affiliations = $activeStaff->pluck('affiliation')->filter()->unique()->values()->all();

        $initialTaskId = null;
        if ($requestedTaskId && $tasks->contains('id', $requestedTaskId)) {
            $initialTaskId = $requestedTaskId;
        } else {
            $initialTaskId = $tasks->firstWhere('is_satisfied', false)?->id ?? $tasks->first()?->id;
        }

        return [
            'timetable' => $timetable,
            'lanes' => $lanes,
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'satisfiedTasks' => $satisfiedTasks,
            'shortageTasks' => $shortageTasks,
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'activeStaff' => $activeStaff,
            'affiliations' => $affiliations,
            'initialTaskId' => $initialTaskId,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ];
    }

    /**
     * 単一タスクの更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTask(TimetableTask $task, array $data): TimetableTask
    {
        return DB::transaction(function () use ($task, $data): TimetableTask {
            $this->taskRepository->update($task, $data);

            return $task->fresh(['lane', 'assignments']);
        });
    }

    /**
     * 選択したレーンに対する複数タスクの一括登録（班長・固定スタッフのデフォルト自動アサイン対応）
     *
     * @param  array<int, array<string, mixed>>  $tasksData
     * @return Collection<int, TimetableTask>
     */
    public function createBulkTasks(
        int $timetableId,
        int $laneId,
        array $tasksData,
        bool $autoAssignLead = true
    ): Collection {
        return DB::transaction(function () use ($timetableId, $laneId, $tasksData, $autoAssignLead): Collection {
            $attributesList = [];
            foreach ($tasksData as $taskItem) {
                $attributesList[] = array_merge($taskItem, [
                    'timetable_id' => $timetableId,
                    'lane_id' => $laneId,
                ]);
            }

            $createdTasks = $this->taskRepository->createMany($attributesList);

            if ($autoAssignLead) {
                $lane = $this->laneRepository->findWithFixedStaff($laneId);
                if ($lane) {
                    foreach ($createdTasks as $task) {
                        $this->autoAssignLaneMembersToTask($task, $lane);
                    }
                }
            }

            return $createdTasks;
        });
    }

    /**
     * 指定された複数の担当班へ共通タスクを一括作成する
     *
     * @return Collection<int, TimetableTask>
     */
    public function createCommonTasksForLanes(CreateCommonTimetableTaskDTO $dto): Collection
    {
        return DB::transaction(function () use ($dto): Collection {
            $lanes = $this->laneRepository->getByIdsWithFixedStaff($dto->timetableId, $dto->laneIds);
            if ($lanes->isEmpty()) {
                return new Collection;
            }

            $baseAttributes = $dto->toTaskAttributes();
            $attributesList = [];
            foreach ($lanes as $lane) {
                $attributesList[] = array_merge($baseAttributes, [
                    'lane_id' => $lane->id,
                ]);
            }

            $createdTasks = $this->taskRepository->createMany($attributesList);

            if ($dto->autoAssignLead) {
                $lanesById = $lanes->keyBy('id');
                foreach ($createdTasks as $task) {
                    $lane = $lanesById->get($task->lane_id);
                    if ($lane instanceof Lane) {
                        $this->autoAssignLaneMembersToTask($task, $lane);
                    }
                }
            }

            return $createdTasks;
        });
    }

    /**
     * 単一タスクに対して班長および固定スタッフを基本アサインする
     */
    protected function autoAssignLaneMembersToTask(TimetableTask $task, Lane $lane): void
    {
        if ($lane->lead_staff_id) {
            $this->assignmentRepository->firstOrCreate(
                [
                    'timetable_task_id' => $task->id,
                    'staff_id' => $lane->lead_staff_id,
                ],
                [
                    'role_description' => '班長',
                    'attendance_status' => 'assigned',
                ]
            );
        }

        foreach ($lane->fixedStaff as $fixedMember) {
            if ($lane->lead_staff_id && (int) $fixedMember->id === (int) $lane->lead_staff_id) {
                continue;
            }

            $this->assignmentRepository->firstOrCreate(
                [
                    'timetable_task_id' => $task->id,
                    'staff_id' => $fixedMember->id,
                ],
                [
                    'role_description' => '固定スタッフ',
                    'attendance_status' => 'assigned',
                ]
            );
        }
    }

    /**
     * レーンの班長（主担当）が設定・変更された際、そのレーンの既存タスクへ班長を自動アサイン（同期）する
     */
    public function syncLaneLeadToTasks(Lane $lane, ?int $oldLeadStaffId = null): int
    {
        $newLeadStaffId = $lane->lead_staff_id ? (int) $lane->lead_staff_id : null;
        if ($newLeadStaffId === null) {
            return 0;
        }

        return DB::transaction(function () use ($lane, $oldLeadStaffId, $newLeadStaffId): int {
            $tasks = $this->taskRepository->getActiveForLaneWithAssignments($lane->id);

            $syncedCount = 0;

            foreach ($tasks as $task) {
                $existingNewLeadAssignment = $task->assignments
                    ->firstWhere('staff_id', $newLeadStaffId);

                // 旧班長が「班長」または「班リーダー」としてアサインされている場合の引き継ぎ処理
                if ($oldLeadStaffId !== null && $oldLeadStaffId !== $newLeadStaffId) {
                    $oldLeadAssignment = $task->assignments
                        ->where('staff_id', $oldLeadStaffId)
                        ->whereIn('role_description', ['班長', '班リーダー'])
                        ->first();

                    if ($oldLeadAssignment) {
                        if ($existingNewLeadAssignment) {
                            $this->assignmentRepository->delete($oldLeadAssignment);
                            if (empty($existingNewLeadAssignment->role_description) || in_array($existingNewLeadAssignment->role_description, ['一般担当', '固定スタッフ', '班リーダー'], true)) {
                                $this->assignmentRepository->update($existingNewLeadAssignment, ['role_description' => '班長']);
                            }
                        } else {
                            $this->assignmentRepository->update($oldLeadAssignment, [
                                'staff_id' => $newLeadStaffId,
                                'role_description' => '班長',
                            ]);
                            $syncedCount++;

                            continue;
                        }
                    }
                }

                if (! $existingNewLeadAssignment) {
                    $this->assignmentRepository->create([
                        'timetable_task_id' => $task->id,
                        'staff_id' => $newLeadStaffId,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ]);
                    $syncedCount++;
                } elseif (in_array($existingNewLeadAssignment->role_description, [null, '', '一般担当', '固定スタッフ', '班リーダー'], true)) {
                    $this->assignmentRepository->update($existingNewLeadAssignment, ['role_description' => '班長']);
                }
            }

            return $syncedCount;
        });
    }

    /**
     * レーンの固定スタッフ一覧を同期し、そのレーンの全有効タスクへ固定スタッフとして基本アサインする
     *
     * @param  array<int, int|string>  $fixedStaffIds
     */
    public function syncLaneFixedStaffToTasks(Lane $lane, array $fixedStaffIds): int
    {
        $cleanIds = array_values(array_unique(array_map('intval', array_filter($fixedStaffIds))));

        return DB::transaction(function () use ($lane, $cleanIds): int {
            $previousFixedIds = $this->laneRepository->getFixedStaffIds($lane);

            $this->laneRepository->syncFixedStaff($lane, $cleanIds);

            if (! empty($cleanIds)) {
                $this->staffRepository->updateStaffTypeByIds($cleanIds, 'fixed');
            }

            $removedIds = array_diff($previousFixedIds, $cleanIds);
            $tasks = $this->taskRepository->getActiveForLaneWithAssignments($lane->id);

            // 固定スタッフから外れたメンバーの未着手タスク自動アサインを解除
            if (! empty($removedIds)) {
                foreach ($tasks as $task) {
                    if ($task->status !== 'not_started') {
                        continue;
                    }
                    foreach ($removedIds as $removedId) {
                        if ($lane->lead_staff_id && (int) $lane->lead_staff_id === (int) $removedId) {
                            continue;
                        }
                        $this->assignmentRepository->deleteByTaskAndStaffAndRole(
                            $task->id,
                            (int) $removedId,
                            '固定スタッフ'
                        );
                    }
                }
            }

            // 固定スタッフを全タスクに基本アサイン
            $syncedCount = 0;
            foreach ($tasks as $task) {
                foreach ($cleanIds as $staffId) {
                    if ($lane->lead_staff_id && (int) $lane->lead_staff_id === (int) $staffId) {
                        continue;
                    }

                    $exists = $task->assignments->contains('staff_id', $staffId);
                    if (! $exists) {
                        $this->assignmentRepository->create([
                            'timetable_task_id' => $task->id,
                            'staff_id' => $staffId,
                            'role_description' => '固定スタッフ',
                            'attendance_status' => 'assigned',
                        ]);
                        $syncedCount++;
                    }
                }
            }

            return $syncedCount;
        });
    }

    /**
     * タスクの削除
     */
    public function deleteTask(TimetableTask $task): bool
    {
        return $this->taskRepository->delete($task);
    }

    /**
     * スタッフをタスクに割り当て
     */
    public function assignStaff(TimetableTask $task, int $staffId, ?string $roleDescription = null): TaskStaffAssignment
    {
        return $this->taskRepository->assignStaff($task, $staffId, $roleDescription);
    }

    /**
     * 複数スタッフをタスクに一括割り当て
     *
     * @param  array<int, int>  $staffIds
     * @return Collection<int, TaskStaffAssignment>
     */
    public function bulkAssignStaff(TimetableTask $task, array $staffIds, ?string $roleDescription = null): Collection
    {
        return $this->taskRepository->bulkAssignStaff($task, $staffIds, $roleDescription);
    }

    /**
     * スタッフのタスク割り当て解除
     */
    public function removeStaff(TimetableTask $task, int $staffId): bool
    {
        $task->loadMissing('laneRelation');
        if ($task->laneRelation && (int) $task->laneRelation->lead_staff_id === $staffId) {
            throw new DomainException('班長はタスクの配置から解除できません。');
        }

        return $this->taskRepository->removeStaff($task, $staffId);
    }

    /**
     * 出欠ステータスの更新
     */
    public function updateAttendanceStatus(int $assignmentId, string $status): bool
    {
        return $this->taskRepository->updateAttendanceStatus($assignmentId, $status);
    }

    /**
     * タスクへの割り当て候補となるアクティブスタッフ一覧を取得
     *
     * @return Collection<int, Staff>
     */
    public function getActiveStaff(): Collection
    {
        return $this->staffRepository->getActiveStaffList();
    }

    /**
     * 送信されたタスクリスト内での時間帯重複チェック
     *
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, string> 重複エラーメッセージの配列
     */
    public function checkInternalOverlaps(array $tasks): array
    {
        $errors = [];
        $count = count($tasks);

        for ($i = 0; $i < $count; $i++) {
            $taskA = $tasks[$i];
            $statusA = $taskA['status'] ?? 'not_started';
            if ($statusA === 'cancelled') {
                continue;
            }

            $startA = ! empty($taskA['start_time']) ? substr($taskA['start_time'], 0, 5) : null;
            $endA = ! empty($taskA['end_time']) ? substr($taskA['end_time'], 0, 5) : null;
            if (! $startA || ! $endA) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $taskB = $tasks[$j];
                $statusB = $taskB['status'] ?? 'not_started';
                if ($statusB === 'cancelled') {
                    continue;
                }

                $startB = ! empty($taskB['start_time']) ? substr($taskB['start_time'], 0, 5) : null;
                $endB = ! empty($taskB['end_time']) ? substr($taskB['end_time'], 0, 5) : null;
                if (! $startB || ! $endB) {
                    continue;
                }

                // 重複判定: startA < endB && endA > startB
                if ($this->hasTimeRangeOverlap($startA, $endA, $startB, $endB)) {
                    $titleA = ! empty($taskA['title']) ? "「{$taskA['title']}」" : 'タスク #'.($i + 1);
                    $titleB = ! empty($taskB['title']) ? "「{$taskB['title']}」" : 'タスク #'.($j + 1);
                    $errors[] = 'タスク #'.($i + 1)." {$titleA}（{$startA}〜{$endA}）と タスク #".($j + 1)." {$titleB}（{$startB}〜{$endB}）の時間帯が重複しています。";
                }
            }
        }

        return $errors;
    }

    /**
     * 指定レーンの既存タスクとの時間帯重複チェック
     *
     * @param  array<int, array<string, mixed>>  $tasks
     * @param  array<int, int>  $ignoredTaskIds  更新対象など除外する既存タスクID一覧
     * @return array<int, string> 重複エラーメッセージの配列
     */
    public function checkDatabaseOverlaps(int $laneId, array $tasks, array $ignoredTaskIds = []): array
    {
        $existingTasks = $this->taskRepository->getActiveForLaneExcludingIds($laneId, $ignoredTaskIds);

        if ($existingTasks->isEmpty()) {
            return [];
        }

        $errors = [];

        foreach ($tasks as $index => $taskItem) {
            $status = $taskItem['status'] ?? 'not_started';
            if ($status === 'cancelled') {
                continue;
            }

            $start = ! empty($taskItem['start_time']) ? substr($taskItem['start_time'], 0, 5) : null;
            $end = ! empty($taskItem['end_time']) ? substr($taskItem['end_time'], 0, 5) : null;
            if (! $start || ! $end) {
                continue;
            }

            foreach ($existingTasks as $existing) {
                $exStart = substr($existing->start_time, 0, 5);
                $exEnd = substr($existing->end_time, 0, 5);

                if ($this->hasTimeRangeOverlap($start, $end, $exStart, $exEnd)) {
                    $itemTitle = ! empty($taskItem['title']) ? "「{$taskItem['title']}」" : 'タスク #'.($index + 1);
                    $errors[] = "{$itemTitle}（{$start}〜{$end}）は、既に登録されているタスク「{$existing->title}」（{$exStart}〜{$exEnd}）と時間帯が重複しています。";
                }
            }
        }

        return $errors;
    }

    /**
     * 複数の担当班に対する既存タスクとの時間帯重複を一括チェック
     *
     * @param  array<int, int>  $laneIds
     * @return array<int, string> 重複エラーメッセージの配列
     */
    public function checkDatabaseOverlapsForLanes(
        int $timetableId,
        array $laneIds,
        string $startTime,
        string $endTime,
        string $status = 'not_started'
    ): array {
        if ($status === 'cancelled' || empty($laneIds)) {
            return [];
        }

        $start = substr($startTime, 0, 5);
        $end = substr($endTime, 0, 5);
        if ($start === '' || $end === '' || $start >= $end) {
            return [];
        }

        $lanes = $this->laneRepository->getByIdsWithFixedStaff($timetableId, $laneIds);
        if ($lanes->isEmpty()) {
            return [];
        }

        $existingTasksByLane = $this->taskRepository
            ->getActiveForLanes($laneIds)
            ->groupBy('lane_id');

        $errors = [];

        foreach ($lanes as $lane) {
            $laneTasks = $existingTasksByLane->get($lane->id);
            if (! $laneTasks || $laneTasks->isEmpty()) {
                continue;
            }

            foreach ($laneTasks as $existing) {
                $exStart = substr($existing->start_time, 0, 5);
                $exEnd = substr($existing->end_time, 0, 5);

                if ($this->hasTimeRangeOverlap($start, $end, $exStart, $exEnd)) {
                    $errors[] = "「{$lane->name}」の既存タスク「{$existing->title}」（{$exStart}〜{$exEnd}）と時間帯が重複しています。";
                }
            }
        }

        return $errors;
    }

    /**
     * 2つの時間帯 (HH:MM) が重複しているかを判定する
     */
    private function hasTimeRangeOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }
}
