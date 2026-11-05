<?php

namespace App\Actions\TaskAssignment;

use App\Models\Staff;
use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
use App\Services\TimetableTaskService;

class BulkAssignStaffAction
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * スタッフのタスク複数一括割り当てを実行
     *
     * @param  array<int, int>  $staffIds
     * @return array{
     *     success: bool,
     *     has_conflict: bool,
     *     message: string,
     *     assigned_count: int,
     *     conflicts: array<int, array{staff_id: int, staff_name: string, task_title: string, lane: string, time: string}>,
     *     warning_message?: ?string
     * }
     */
    public function execute(TimetableTask $task, array $staffIds, ?string $roleDescription = null, bool $isForced = false): array
    {
        // 同一時間帯の重複アサインを検知
        $conflictsMap = $this->conflictService->checkConflictsForStaffIds($task, $staffIds);

        if (! empty($conflictsMap) && ! $isForced) {
            $conflictStaffList = [];
            foreach ($conflictsMap as $staffId => $conflictingTask) {
                $staff = Staff::find($staffId);
                $conflictingLane = $conflictingTask->lane ?: '担当班未設定';
                $startTime = substr($conflictingTask->start_time, 0, 5);
                $endTime = substr($conflictingTask->end_time, 0, 5);
                $conflictStaffList[] = [
                    'staff_id' => $staffId,
                    'staff_name' => $staff?->name ?? "ID:{$staffId}",
                    'task_title' => $conflictingTask->title,
                    'lane' => $conflictingLane,
                    'time' => "{$startTime}〜{$endTime}",
                ];
            }

            $names = implode('、', array_column($conflictStaffList, 'staff_name'));
            $warningMessage = "選択されたスタッフのうち、{$names} は同時刻の別タスクに既にアサインされています。";

            return [
                'success' => false,
                'has_conflict' => true,
                'message' => $warningMessage,
                'warning_message' => $warningMessage,
                'assigned_count' => 0,
                'conflicts' => $conflictStaffList,
            ];
        }

        $this->taskService->bulkAssignStaff($task, $staffIds, $roleDescription);

        $count = count($staffIds);
        $message = ! empty($conflictsMap)
            ? "{$count}名のスタッフをタスクに割り当てました（重複を承認）。"
            : "{$count}名のスタッフをタスクに割り当てました。";

        return [
            'success' => true,
            'has_conflict' => ! empty($conflictsMap),
            'message' => $message,
            'assigned_count' => $count,
            'conflicts' => [],
        ];
    }
}
