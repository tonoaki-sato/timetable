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
     * 担当班の作業タスクを一括更新（新規追加枠の自動作成および班長・固定スタッフアサイン対応）
     *
     * @param  array<int, array<string, mixed>>  $tasksData
     * @return array{updated_count: int, created_count: int}
     */
    public function updateBulkTasks(Lane $lane, array $tasksData): array
    {
        return DB::transaction(function () use ($lane, $tasksData) {
            $lane->loadMissing('fixedStaff');
            $existingTasks = $lane->tasks()->get()->keyBy('id');

            $updatedCount = 0;
            $createdCount = 0;

            foreach ($tasksData as $taskItem) {
                $taskId = ! empty($taskItem['id']) ? (int) $taskItem['id'] : null;

                if ($taskId && isset($existingTasks[$taskId])) {
                    $task = $existingTasks[$taskId];
                    $attributes = $taskItem;
                    unset($attributes['id']);
                    $attributes['timetable_id'] = $lane->timetable_id;
                    $attributes['lane_id'] = $lane->id;
                    $attributes['lane'] = $lane->name;

                    $this->taskRepository->update($task, $attributes);
                    $updatedCount++;
                } else {
                    $attributes = $taskItem;
                    unset($attributes['id']);
                    $attributes['timetable_id'] = $lane->timetable_id;
                    $attributes['lane_id'] = $lane->id;
                    $attributes['lane'] = $lane->name;

                    $newTask = $this->taskRepository->create($attributes);
                    $this->autoAssignLaneMembersToTask($newTask, $lane);
                    $createdCount++;
                }
            }

            return [
                'updated_count' => $updatedCount,
                'created_count' => $createdCount,
            ];
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

    /**
     * レーン別および選択レーンに基づく候補開始・終了時刻を算出
     *
     * @param  \Illuminate\Support\Collection<int, Lane>  $timetableLanes
     * @return array{
     *     suggested_start_time: string,
     *     suggested_end_time: string,
     *     lane_suggested_start_times: array<int, string>
     * }
     */
    public function calculateSuggestedTimeRange(
        $timetableLanes,
        ?Lane $selectedLane = null,
        ?string $requestedStartTime = null
    ): array {
        $laneSuggestedStartTimes = [];
        foreach ($timetableLanes as $laneItem) {
            $latest = $laneItem->tasks->sortByDesc('end_time')->first();
            $laneSuggestedStartTimes[$laneItem->id] = $latest ? substr($latest->end_time, 0, 5) : '08:00';
        }

        if (is_string($requestedStartTime) && preg_match('/^\d{2}:\d{2}$/', $requestedStartTime)) {
            $suggestedStartTime = $requestedStartTime;
        } elseif ($selectedLane && isset($laneSuggestedStartTimes[$selectedLane->id])) {
            $suggestedStartTime = $laneSuggestedStartTimes[$selectedLane->id];
        } else {
            $suggestedStartTime = '08:00';
        }

        // 初期終了時刻の算出（開始時刻の60分後、または08:00なら09:30）
        $suggestedEndTime = '09:30';
        if (preg_match('/^(\d{2}):(\d{2})$/', $suggestedStartTime, $matches)) {
            $startMinutes = ((int) $matches[1]) * 60 + ((int) $matches[2]);
            $endMinutes = min(23 * 60 + 59, $startMinutes + ($suggestedStartTime === '08:00' && ! $requestedStartTime ? 90 : 60));
            if ($endMinutes <= $startMinutes) {
                $endMinutes = min(23 * 60 + 59, $startMinutes + 30);
            }
            $suggestedEndTime = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);
        }

        return [
            'suggested_start_time' => $suggestedStartTime,
            'suggested_end_time' => $suggestedEndTime,
            'lane_suggested_start_times' => $laneSuggestedStartTimes,
        ];
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
                if ($startA < $endB && $endA > $startB) {
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
        $existingTasks = TimetableTask::where('lane_id', $laneId)
            ->where('status', '!=', 'cancelled')
            ->when(! empty($ignoredTaskIds), function ($query) use ($ignoredTaskIds) {
                $query->whereNotIn('id', $ignoredTaskIds);
            })
            ->get();

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

                if ($start < $exEnd && $end > $exStart) {
                    $itemTitle = ! empty($taskItem['title']) ? "「{$taskItem['title']}」" : 'タスク #'.($index + 1);
                    $errors[] = "{$itemTitle}（{$start}〜{$end}）は、既に登録されているタスク「{$existing->title}」（{$exStart}〜{$exEnd}）と時間帯が重複しています。";
                }
            }
        }

        return $errors;
    }
}
