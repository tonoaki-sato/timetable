<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TimetableTaskService
{
    public function __construct(
        protected TimetableTaskRepositoryInterface $taskRepository
    ) {}

    /**
     * アサイン情報付きタスク詳細の取得
     */
    public function getTaskDetails(int $id): ?TimetableTask
    {
        return $this->taskRepository->findWithAssignments($id);
    }

    /**
     * 新規タスクの登録
     *
     * @param  array<string, mixed>  $data
     */
    public function createTask(array $data): TimetableTask
    {
        return $this->taskRepository->create($data);
    }

    /**
     * 選択したレーンに対する複数タスクの一括登録（主担当自動アサイン対応）
     *
     * @param  array<int, array<string, mixed>>  $tasksData
     * @return Collection<int, TimetableTask>
     */
    public function createBulkTasks(
        int $timetableId,
        int $laneId,
        array $tasksData,
        bool $autoAssignLead = false
    ): Collection {
        return DB::transaction(function () use ($timetableId, $laneId, $tasksData, $autoAssignLead) {
            $attributesList = [];
            foreach ($tasksData as $taskItem) {
                $attributesList[] = array_merge($taskItem, [
                    'timetable_id' => $timetableId,
                    'lane_id' => $laneId,
                ]);
            }

            $createdTasks = $this->taskRepository->createMany($attributesList);

            if ($autoAssignLead) {
                $lane = Lane::find($laneId);
                if ($lane && $lane->lead_staff_id) {
                    foreach ($createdTasks as $task) {
                        $task->assignments()->firstOrCreate(
                            ['staff_id' => $lane->lead_staff_id],
                            [
                                'role_description' => '班リーダー',
                                'attendance_status' => 'present',
                            ]
                        );
                    }
                }
            }

            return $createdTasks;
        });
    }

    /**
     * タスク情報の更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTask(TimetableTask $task, array $data): bool
    {
        return $this->taskRepository->update($task, $data);
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
        return Staff::active()->orderBy('kana')->get();
    }
}
