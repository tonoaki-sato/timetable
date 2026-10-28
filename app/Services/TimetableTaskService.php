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
     * 新規タスクの登録（班長・固定スタッフのデフォルト自動アサイン対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function createTask(array $data, bool $autoAssignLead = true): TimetableTask
    {
        return DB::transaction(function () use ($data, $autoAssignLead) {
            $task = $this->taskRepository->create($data);

            if ($autoAssignLead && ! empty($task->lane_id)) {
                $lane = Lane::with('fixedStaff')->find($task->lane_id);
                if ($lane) {
                    $this->autoAssignLaneMembersToTask($task, $lane);
                }
            }

            return $task;
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
                $lane = Lane::with('fixedStaff')->find($laneId);
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
     * 単一タスクに対して班長および固定スタッフを基本アサインする
     */
    protected function autoAssignLaneMembersToTask(TimetableTask $task, Lane $lane): void
    {
        if ($lane->lead_staff_id) {
            $task->assignments()->firstOrCreate(
                ['staff_id' => $lane->lead_staff_id],
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

            $task->assignments()->firstOrCreate(
                ['staff_id' => $fixedMember->id],
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

        return DB::transaction(function () use ($lane, $oldLeadStaffId, $newLeadStaffId) {
            $tasks = $lane->tasks()
                ->where('status', '!=', 'cancelled')
                ->with('assignments')
                ->get();

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
                            $oldLeadAssignment->delete();
                            if (empty($existingNewLeadAssignment->role_description) || in_array($existingNewLeadAssignment->role_description, ['一般担当', '固定スタッフ', '班リーダー'], true)) {
                                $existingNewLeadAssignment->update(['role_description' => '班長']);
                            }
                        } else {
                            $oldLeadAssignment->update([
                                'staff_id' => $newLeadStaffId,
                                'role_description' => '班長',
                            ]);
                            $syncedCount++;

                            continue;
                        }
                    }
                }

                if (! $existingNewLeadAssignment) {
                    $task->assignments()->create([
                        'staff_id' => $newLeadStaffId,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ]);
                    $syncedCount++;
                } elseif (in_array($existingNewLeadAssignment->role_description, [null, '', '一般担当', '固定スタッフ', '班リーダー'], true)) {
                    $existingNewLeadAssignment->update(['role_description' => '班長']);
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

        return DB::transaction(function () use ($lane, $cleanIds) {
            $previousFixedIds = $lane->fixedStaff()
                ->pluck('staff.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $lane->fixedStaff()->sync($cleanIds);

            if (! empty($cleanIds)) {
                Staff::whereIn('id', $cleanIds)->update(['staff_type' => 'fixed']);
            }

            $removedIds = array_diff($previousFixedIds, $cleanIds);
            $tasks = $lane->tasks()
                ->where('status', '!=', 'cancelled')
                ->with('assignments')
                ->get();

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
                        $task->assignments()
                            ->where('staff_id', $removedId)
                            ->where('role_description', '固定スタッフ')
                            ->delete();
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
                        $task->assignments()->create([
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
