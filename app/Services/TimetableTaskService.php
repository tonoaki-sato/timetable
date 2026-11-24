<?php

namespace App\Services;

use App\DTOs\TimetableTask\CreateCommonTimetableTaskDTO;
use App\Enums\TimetableTaskStatus;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TimetableTaskService
{
    public function __construct(
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected TimetableLaneRepositoryInterface $timetableLaneRepository,
        protected TimetableRepositoryInterface $timetableRepository,
        protected TimetableTaskAssignmentService $taskAssignmentService,
    ) {}

    /**
     * アサイン情報付きタスク詳細の取得
     */
    public function getTaskDetails(int $id): ?TimetableTask
    {
        return $this->taskRepository->findWithAssignments($id);
    }

    /**
     * IDによる運行日別担当班編成の取得
     */
    public function findTimetableLaneById(int $timetableLaneId): ?TimetableLane
    {
        return $this->timetableLaneRepository->findById($timetableLaneId);
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
            'timetableLanes' => $timetable->timetableLanes,
            'tasks' => $timetable->timetableLanes->flatMap->tasks,
            'statuses' => TimetableTaskStatus::options(),
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

            return $task->fresh(['timetableLane.lane', 'assignments']);
        });
    }

    /**
     * 単一作業タスクの新規登録（班長・固定スタッフのデフォルト自動アサイン対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function createTask(array $data): TimetableTask
    {
        return DB::transaction(function () use ($data): TimetableTask {
            $autoAssignLead = (bool) ($data['auto_assign_lead'] ?? true);
            $attributes = $data;
            unset($attributes['auto_assign_lead'], $attributes['assign_staff_id'], $attributes['force_assign']);

            $task = $this->taskRepository->create($attributes);

            if ($autoAssignLead) {
                $this->taskAssignmentService->autoAssignDefaultMembersForLaneTasks(
                    new Collection([$task]),
                    (int) $task->timetable_lane_id
                );
            }

            return $task;
        });
    }

    /**
     * 指定された複数の日別担当班編成へ共通タスクを一括作成する
     *
     * @return Collection<int, TimetableTask>
     */
    public function createCommonTasksForLanes(CreateCommonTimetableTaskDTO $dto): Collection
    {
        return DB::transaction(function () use ($dto): Collection {
            $timetableLanes = $this->timetableLaneRepository->getByIdsWithFixedStaff($dto->timetableId, $dto->timetableLaneIds);
            if ($timetableLanes->isEmpty()) {
                return new Collection;
            }

            $baseAttributes = $dto->toTaskAttributes();
            $attributesList = [];
            foreach ($timetableLanes as $timetableLane) {
                $attributesList[] = array_merge($baseAttributes, [
                    'timetable_lane_id' => $timetableLane->id,
                ]);
            }

            $createdTasks = $this->taskRepository->createMany($attributesList);

            if ($dto->autoAssignLead) {
                $this->taskAssignmentService->autoAssignDefaultMembersForCommonTasks($createdTasks, $timetableLanes);
            }

            return $createdTasks;
        });
    }

    /**
     * タスクの削除
     */
    public function deleteTask(TimetableTask $task): bool
    {
        return $this->taskRepository->delete($task);
    }
}
