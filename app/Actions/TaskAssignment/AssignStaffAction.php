<?php

namespace App\Actions\TaskAssignment;

use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
use App\Services\StaffAllocationService;
use App\Services\TimetableTaskService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignStaffAction
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService,
        protected StaffAllocationService $allocationService
    ) {}

    /**
     * スタッフのタスク割り当てを実行
     *
     * @return array{
     *     success: bool,
     *     has_conflict: bool,
     *     message: string,
     *     conflicting_task?: ?TimetableTask,
     *     warning_message?: ?string,
     *     transferable_surplus?: Collection<int, TimetableTask>
     * }
     */
    public function execute(TimetableTask $task, int $staffId, ?string $roleDescription = null, bool $isForced = false): array
    {
        $conflictingTask = $this->conflictService->checkConflict($task, $staffId);
        $transferableSurplusTasks = $conflictingTask
            ? $this->allocationService->getTransferableSurplusTasks($task, $staffId)
            : collect();

        if ($conflictingTask && $transferableSurplusTasks->isEmpty() && ! $isForced) {
            $conflictingLane = $conflictingTask->lane ?: '担当班未設定';
            $startTime = substr($conflictingTask->start_time, 0, 5);
            $endTime = substr($conflictingTask->end_time, 0, 5);
            $warningMessage = "該当スタッフは同時刻（{$startTime}〜{$endTime}）の別タスク「{$conflictingTask->title}」（{$conflictingLane}）に既にアサインされています。";

            return [
                'success' => false,
                'has_conflict' => true,
                'message' => $warningMessage,
                'warning_message' => $warningMessage,
                'conflicting_task' => $conflictingTask,
                'transferable_surplus' => $transferableSurplusTasks,
            ];
        }

        DB::transaction(function () use ($task, $staffId, $roleDescription, $transferableSurplusTasks) {
            foreach ($transferableSurplusTasks as $surplusTask) {
                $this->taskService->removeStaff($surplusTask, $staffId);
            }
            $effectiveRole = $roleDescription ?: ($transferableSurplusTasks->isNotEmpty() ? '他班応援' : null);
            $this->taskService->assignStaff($task, $staffId, $effectiveRole);
        });

        if ($transferableSurplusTasks->isNotEmpty()) {
            $message = '余剰のある別班タスクからスタッフをこのタスクへ融通・割り当てました。';
        } elseif ($conflictingTask) {
            $message = 'スタッフをタスクに割り当てました（重複を承認）。';
        } else {
            $message = 'スタッフをタスクに割り当てました。';
        }

        return [
            'success' => true,
            'has_conflict' => (bool) $conflictingTask,
            'message' => $message,
            'conflicting_task' => $conflictingTask,
            'transferable_surplus' => $transferableSurplusTasks,
        ];
    }
}
