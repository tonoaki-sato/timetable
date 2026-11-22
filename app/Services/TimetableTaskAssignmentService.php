<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Presenters\LanePresenter;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class TimetableTaskAssignmentService
{
    public function __construct(
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected TimetableLaneRepositoryInterface $timetableLaneRepository,
        protected StaffRepositoryInterface $staffRepository,
        protected TaskStaffAssignmentRepositoryInterface $assignmentRepository,
        protected TimetableRepositoryInterface $timetableRepository,
    ) {}

    /**
     * アサイン情報付きタスク詳細の取得
     */
    public function getTaskDetails(int $id): ?TimetableTask
    {
        return $this->taskRepository->findWithAssignments($id);
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
        $timetableLanes = $timetable->timetableLanes;

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
            'timetableLanes' => $timetableLanes,
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'satisfiedTasks' => $satisfiedTasks,
            'shortageTasks' => $shortageTasks,
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'activeStaff' => $activeStaff,
            'affiliations' => $affiliations,
            'initialTaskId' => $initialTaskId,
            'colorOptions' => LanePresenter::COLOR_OPTIONS,
        ];
    }

    /**
     * 選択した日別担当班編成で新規作成された複数タスクへ、班長・固定スタッフを自動アサインする
     *
     * @param  Collection<int, TimetableTask>  $createdTasks
     */
    public function autoAssignDefaultMembersForLaneTasks(Collection $createdTasks, int $timetableLaneId): void
    {
        $timetableLane = $this->timetableLaneRepository->findWithFixedStaff($timetableLaneId);
        if (! $timetableLane) {
            return;
        }

        $preloadedLaneTasks = $this->taskRepository->getActiveForLanes([$timetableLaneId]);
        $rows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($createdTasks as $task) {
            array_push($rows, ...$this->collectDefaultAssignmentRowsForTask($task, $timetableLane, $preloadedLaneTasks, $now));
        }

        $this->assignmentRepository->insertOrIgnoreMany($rows);
    }

    /**
     * 複数班へ一括作成された共通タスク群へ、各班の班長・固定スタッフを自動アサインする
     *
     * @param  Collection<int, TimetableTask>  $createdTasks
     * @param  Collection<int, TimetableLane>  $timetableLanes
     */
    public function autoAssignDefaultMembersForCommonTasks(Collection $createdTasks, Collection $timetableLanes): void
    {
        $lanesById = $timetableLanes->keyBy('id');
        $tasksByLane = $this->taskRepository
            ->getActiveForLanes($timetableLanes->pluck('id')->all())
            ->groupBy('timetable_lane_id');
        $rows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($createdTasks as $task) {
            $timetableLane = $lanesById->get($task->timetable_lane_id);
            if ($timetableLane instanceof TimetableLane) {
                array_push(
                    $rows,
                    ...$this->collectDefaultAssignmentRowsForTask(
                        $task,
                        $timetableLane,
                        $tasksByLane->get($timetableLane->id, collect()),
                        $now
                    )
                );
            }
        }

        $this->assignmentRepository->insertOrIgnoreMany($rows);
    }

    /**
     * 班の班長（主担当）が設定・変更された際、その班の既存タスクへ班長を自動アサイン（同期）する
     */
    public function syncTimetableLaneLeadToTasks(TimetableLane $timetableLane, ?int $oldLeadStaffId = null): int
    {
        $newLeadStaffId = $timetableLane->lead_staff_id ? (int) $timetableLane->lead_staff_id : null;
        if ($newLeadStaffId === null) {
            return 0;
        }

        return DB::transaction(function () use ($timetableLane, $oldLeadStaffId, $newLeadStaffId): int {
            $tasks = $this->taskRepository->getActiveForLaneWithAssignments($timetableLane->id);

            $syncedCount = 0;
            $deleteOldAssignmentIds = [];
            $upgradeToLeadAssignmentIds = [];
            $newLeadRows = [];
            $now = now()->format('Y-m-d H:i:s');

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
                            $deleteOldAssignmentIds[] = $oldLeadAssignment->id;
                            if (empty($existingNewLeadAssignment->role_description) || in_array($existingNewLeadAssignment->role_description, ['一般担当', '固定スタッフ', '班リーダー'], true)) {
                                $upgradeToLeadAssignmentIds[] = $existingNewLeadAssignment->id;
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
                    $newLeadRows[] = [
                        'timetable_task_id' => $task->id,
                        'staff_id' => $newLeadStaffId,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $syncedCount++;
                } elseif (in_array($existingNewLeadAssignment->role_description, [null, '', '一般担当', '固定スタッフ', '班リーダー'], true)) {
                    $upgradeToLeadAssignmentIds[] = $existingNewLeadAssignment->id;
                }
            }

            $this->assignmentRepository->deleteByIds($deleteOldAssignmentIds);
            $this->assignmentRepository->updateRoleByIds(array_values(array_unique($upgradeToLeadAssignmentIds)), '班長');
            $this->assignmentRepository->insertOrIgnoreMany($newLeadRows);

            return $syncedCount;
        });
    }

    /**
     * 班の固定スタッフ一覧を同期し、その班の全有効タスクへ固定スタッフとして基本アサインする
     *
     * @param  array<int, int|string>  $fixedStaffIds
     */
    public function syncTimetableLaneFixedStaffToTasks(TimetableLane $timetableLane, array $fixedStaffIds): int
    {
        $cleanIds = array_values(array_unique(array_map('intval', array_filter($fixedStaffIds))));

        return DB::transaction(function () use ($timetableLane, $cleanIds): int {
            $previousFixedIds = $this->timetableLaneRepository->getFixedStaffIds($timetableLane);

            $this->timetableLaneRepository->syncFixedStaff($timetableLane, $cleanIds);

            $leadStaffId = $timetableLane->lead_staff_id ? (int) $timetableLane->lead_staff_id : null;
            $removedIds = array_values(array_filter(
                array_diff($previousFixedIds, $cleanIds),
                fn (int $id): bool => $leadStaffId === null || $id !== $leadStaffId
            ));

            $tasks = $this->taskRepository->getActiveForLaneWithAssignments($timetableLane->id);

            // 固定スタッフから外れたメンバーの未着手タスク自動アサインを解除
            if ($removedIds !== []) {
                $notStartedTaskIds = $tasks
                    ->where('status', 'not_started')
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all();

                $this->assignmentRepository->deleteByTaskIdsAndStaffIdsAndRole(
                    $notStartedTaskIds,
                    $removedIds,
                    '固定スタッフ'
                );
            }

            // 固定スタッフを全タスクに基本アサイン
            $rowsToInsert = [];
            $now = now()->format('Y-m-d H:i:s');

            foreach ($tasks as $task) {
                $existingStaffIdSet = $task->assignments
                    ->pluck('staff_id')
                    ->map(fn ($id): int => (int) $id)
                    ->flip();

                foreach ($cleanIds as $staffId) {
                    if ($leadStaffId !== null && $leadStaffId === $staffId) {
                        continue;
                    }

                    if (! isset($existingStaffIdSet[$staffId])) {
                        $rowsToInsert[] = [
                            'timetable_task_id' => $task->id,
                            'staff_id' => $staffId,
                            'role_description' => '固定スタッフ',
                            'attendance_status' => 'assigned',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            $this->assignmentRepository->insertOrIgnoreMany($rowsToInsert);

            return count($rowsToInsert);
        });
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
        $task->loadMissing('timetableLane');
        if ($task->timetableLane && (int) $task->timetableLane->lead_staff_id === $staffId) {
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
     * 単一タスクに対する班長・固定スタッフの初期アサイン行配列を組み立てる
     *
     * @param  SupportCollection<int, TimetableTask>|null  $preloadedLaneTasks
     * @return array<int, array<string, mixed>>
     */
    protected function collectDefaultAssignmentRowsForTask(
        TimetableTask $task,
        TimetableLane $timetableLane,
        ?SupportCollection $preloadedLaneTasks,
        string $now
    ): array {
        $rows = [];

        if ($timetableLane->lead_staff_id) {
            $rows[] = [
                'timetable_task_id' => $task->id,
                'staff_id' => $timetableLane->lead_staff_id,
                'role_description' => '班長',
                'attendance_status' => 'assigned',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($timetableLane->fixedStaff->isEmpty()) {
            return $rows;
        }

        if ($this->hasOverlappingActiveTaskInLane($task, $timetableLane->id, $preloadedLaneTasks)) {
            return $rows;
        }

        foreach ($timetableLane->fixedStaff as $fixedMember) {
            if ($timetableLane->lead_staff_id && (int) $fixedMember->id === (int) $timetableLane->lead_staff_id) {
                continue;
            }

            $rows[] = [
                'timetable_task_id' => $task->id,
                'staff_id' => $fixedMember->id,
                'role_description' => '固定スタッフ',
                'attendance_status' => 'assigned',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    /**
     * 同一班内に当該タスクと時間帯の全部または一部が重なる有効タスクが存在するか判定する
     *
     * @param  SupportCollection<int, TimetableTask>|null  $preloadedLaneTasks
     */
    protected function hasOverlappingActiveTaskInLane(
        TimetableTask $task,
        int $timetableLaneId,
        ?SupportCollection $preloadedLaneTasks = null
    ): bool {
        if ($task->status === 'cancelled') {
            return false;
        }

        $start = ! empty($task->start_time) ? substr((string) $task->start_time, 0, 5) : '';
        $end = ! empty($task->end_time) ? substr((string) $task->end_time, 0, 5) : '';
        if ($start === '' || $end === '' || $start >= $end) {
            return false;
        }

        $existingTasks = $preloadedLaneTasks !== null
            ? $preloadedLaneTasks->filter(fn (TimetableTask $existing): bool => $existing->id !== $task->id)
            : $this->taskRepository->getActiveForLaneExcludingIds($timetableLaneId, [$task->id]);
        foreach ($existingTasks as $existing) {
            $exStart = substr((string) $existing->start_time, 0, 5);
            $exEnd = substr((string) $existing->end_time, 0, 5);

            if ($this->hasTimeRangeOverlap($start, $end, $exStart, $exEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 2つの時間帯 (HH:MM) が重複しているかを判定する
     */
    private function hasTimeRangeOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }
}
