<?php

namespace App\Actions\TaskAssignment;

use App\Models\Staff;
use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
use App\Services\TimetableTaskService;
use Illuminate\Support\Facades\DB;

class SwapStaffAssignmentAction
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * スタッフの交代（ワンタップ代替補充）を実行
     *
     * @return array{
     *     success: bool,
     *     status_code: int,
     *     message: string,
     *     has_conflict?: bool,
     *     conflict_task?: ?array{title: string, lane: string, time: string},
     *     old_staff?: Staff,
     *     new_staff?: Staff,
     *     role_description?: string
     * }
     */
    public function execute(
        TimetableTask $task,
        int $oldStaffId,
        int $newStaffId,
        ?string $roleDescription = null,
        bool $isForced = false
    ): array {
        $oldAssignment = $task->assignments()->where('staff_id', $oldStaffId)->first();
        if (! $oldAssignment) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => '交代対象のスタッフはこのタスクにアサインされていません。',
            ];
        }

        $oldStaff = Staff::findOrFail($oldStaffId);
        $newStaff = Staff::findOrFail($newStaffId);

        // 既に新スタッフがこのタスクにアサインされていないか確認
        if ($task->assignments()->where('staff_id', $newStaff->id)->exists()) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => "スタッフ「{$newStaff->name}」は既にこのタスクにアサインされています。",
            ];
        }

        // 新スタッフの重複チェック（forceが未指定の場合）
        $conflictsMap = $this->conflictService->checkConflictsForStaffIds($task, [$newStaff->id]);
        if (isset($conflictsMap[$newStaff->id]) && ! $isForced) {
            $conflictTask = $conflictsMap[$newStaff->id];
            $warnMsg = "交代候補「{$newStaff->name}」は、同一時間帯（{$conflictTask->time_range}）のタスク「{$conflictTask->title}」に既に配置されています。";

            return [
                'success' => false,
                'has_conflict' => true,
                'status_code' => 409,
                'message' => $warnMsg,
                'conflict_task' => [
                    'title' => $conflictTask->title,
                    'lane' => $conflictTask->lane,
                    'time' => $conflictTask->time_range,
                ],
                'old_staff' => $oldStaff,
                'new_staff' => $newStaff,
                'role_description' => $roleDescription ?: ($oldAssignment->role_description ?: '担当'),
            ];
        }

        $role = ! empty($roleDescription)
            ? $roleDescription
            : ($oldAssignment->role_description ?: '担当');

        DB::transaction(function () use ($task, $oldStaff, $newStaff, $role) {
            $this->taskService->removeStaff($task, $oldStaff->id);
            $this->taskService->bulkAssignStaff($task, [$newStaff->id], $role);
        });

        return [
            'success' => true,
            'status_code' => 200,
            'message' => "スタッフ「{$oldStaff->name}」から「{$newStaff->name}」への交代配置を完了しました。",
            'old_staff' => $oldStaff,
            'new_staff' => $newStaff,
        ];
    }
}

