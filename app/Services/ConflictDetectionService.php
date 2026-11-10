<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Support\Collection;

class ConflictDetectionService
{
    protected TimetableTaskRepositoryInterface $taskRepository;

    public function __construct(
        ?TimetableTaskRepositoryInterface $taskRepository = null
    ) {
        $this->taskRepository = $taskRepository ?? app(TimetableTaskRepositoryInterface::class);
    }

    /**
     * 指定したタスクと時間帯が重複し、同一スタッフが割り当てられているタスク一覧を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getConflictingTasks(TimetableTask $task, int $staffId): Collection
    {
        if (! $task->start_time || ! $task->end_time) {
            return new Collection;
        }

        return $this->taskRepository->getConflictingTasksForStaff($task, $staffId);
    }

    /**
     * 重複が存在するかチェックし、最初の重複タスクを返却（重複なしはnull）
     */
    public function checkConflict(TimetableTask $task, int $staffId): ?TimetableTask
    {
        return $this->getConflictingTasks($task, $staffId)->first();
    }

    /**
     * スタッフリスト全体について、該当タスクに対する重複タスクをスタッフIDをキーとしたマップとして取得
     *
     * @param  Collection<int, Staff>  $activeStaff
     * @return array<int, TimetableTask>
     */
    public function getConflictsMapForStaffList(TimetableTask $task, Collection $activeStaff): array
    {
        if (! $task->start_time || ! $task->end_time || $activeStaff->isEmpty()) {
            return [];
        }

        $staffIds = $activeStaff->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return $this->checkConflictsForStaffIds($task, $staffIds);
    }

    /**
     * 指定したスタッフID一覧について、該当タスクに対する重複タスクをスタッフIDをキーとしたマップとして取得
     *
     * @param  array<int, int>  $staffIds
     * @return array<int, TimetableTask>
     */
    public function checkConflictsForStaffIds(TimetableTask $task, array $staffIds): array
    {
        if (! $task->start_time || ! $task->end_time || empty($staffIds)) {
            return [];
        }

        $intStaffIds = array_map('intval', $staffIds);
        $overlappingTasks = $this->taskRepository->getOverlappingTasksForStaffIds($task, $intStaffIds);

        $conflictsMap = [];
        foreach ($overlappingTasks as $ovTask) {
            foreach ($ovTask->assignments as $assignment) {
                if (! isset($conflictsMap[$assignment->staff_id])) {
                    $conflictsMap[$assignment->staff_id] = $ovTask;
                }
            }
        }

        return $conflictsMap;
    }
}
