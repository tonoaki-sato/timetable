<?php

namespace App\Actions\TaskAssignment;

use App\Models\Staff;
use App\Models\TimetableTask;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Services\ConflictDetectionService;
use App\Services\TimetableTaskService;

class AutoAssignLanePoolAction
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService,
        protected StaffRepositoryInterface $staffRepository,
        protected TaskStaffAssignmentRepositoryInterface $assignmentRepository
    ) {}

    /**
     * 班（レーン）専属メンバーの自動一括割り当てを実行
     *
     * @return array{
     *     success: bool,
     *     status_code: int,
     *     message: string,
     *     assigned_count: int,
     *     warning_type?: ?string
     * }
     */
    public function execute(TimetableTask $task): array
    {
        $laneName = $task->lane ?: ($task->laneRelation?->name ?? '');
        if (empty($laneName)) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => '担当班が設定されていないため、班メンバー自動配置は行えません。',
                'assigned_count' => 0,
                'warning_type' => 'error',
            ];
        }

        $shortage = $task->shortage_count;
        if ($shortage <= 0) {
            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'このタスクはすでに人員が充足しています。',
                'assigned_count' => 0,
                'warning_type' => 'info',
            ];
        }

        // 当該レーンに所属するアクティブスタッフ（固定スタッフおよび所属一致スタッフ）を取得
        $task->loadMissing('laneRelation.fixedStaff');
        $fixedStaffIds = $task->laneRelation
            ? $task->laneRelation->fixedStaff->pluck('id')->map(fn ($id): int => (int) $id)->all()
            : [];

        $activeStaff = $this->staffRepository->getActiveStaffList();
        $laneMembers = $activeStaff->filter(function (Staff $s) use ($laneName, $fixedStaffIds): bool {
            if (in_array((int) $s->id, $fixedStaffIds, true)) {
                return true;
            }

            return ! empty($s->affiliation) && (
                mb_strpos($s->affiliation, $laneName) !== false ||
                mb_strpos($laneName, $s->affiliation) !== false
            );
        });

        if ($laneMembers->isEmpty()) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => "「{$laneName}」に該当する所属スタッフが見つかりませんでした。",
                'assigned_count' => 0,
                'warning_type' => 'error',
            ];
        }

        // 既にアサイン済みの人を除外
        $alreadyAssignedIds = $this->assignmentRepository->getStaffIdsForTask($task->id);
        $unassignedLaneMembers = $laneMembers->reject(fn (Staff $s): bool => in_array($s->id, $alreadyAssignedIds));

        // 重複チェック
        $conflictsMap = $this->conflictService->checkConflictsForStaffIds($task, $unassignedLaneMembers->pluck('id')->all());
        // 重複のない空きメンバーを優先
        $availableMembers = $unassignedLaneMembers->reject(fn (Staff $s): bool => isset($conflictsMap[$s->id]))->values();

        if ($availableMembers->isEmpty()) {
            return [
                'success' => false,
                'status_code' => 409,
                'message' => "「{$laneName}」の所属スタッフは全員同時刻の他タスクに配置中、または既にアサイン済みです。",
                'assigned_count' => 0,
                'warning_type' => 'conflict_warning',
            ];
        }

        // 不足人数分だけピックアップ
        $toAssign = $availableMembers->take($shortage);
        $assignIds = $toAssign->pluck('id')->all();

        $this->taskService->bulkAssignStaff($task, $assignIds, '班担当');

        $count = count($assignIds);
        $names = $toAssign->pluck('name')->implode('、');

        return [
            'success' => true,
            'status_code' => 200,
            'message' => "「{$laneName}」から {$count}名（{$names}）を自動配置しました。",
            'assigned_count' => $count,
            'warning_type' => 'success',
        ];
    }
}
