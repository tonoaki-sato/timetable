<?php

namespace App\Actions\TaskAssignment;

use App\Models\Staff;
use App\Models\TimetableTask;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Services\ConflictDetectionService;
use App\Services\TimetableTaskService;
use Illuminate\Support\Facades\DB;

class SwapStaffAssignmentAction
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService,
        protected StaffRepositoryInterface $staffRepository,
        protected TaskStaffAssignmentRepositoryInterface $assignmentRepository
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
        $oldAssignment = $this->assignmentRepository->findByTaskAndStaff($task->id, $oldStaffId);
        if (! $oldAssignment) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => '交代対象のスタッフはこのタスクにアサインされていません。',
            ];
        }

        $oldStaff = $this->staffRepository->findOrFail($oldStaffId);
        $newStaff = $this->staffRepository->findOrFail($newStaffId);

        // 既に新スタッフがこのタスクにアサインされていないか確認
        if ($this->assignmentRepository->existsByTaskAndStaff($task->id, $newStaff->id)) {
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

        DB::transaction(function () use ($task, $oldStaff, $newStaff, $role): void {
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
