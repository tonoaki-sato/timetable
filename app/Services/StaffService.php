<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class StaffService
{
    protected TaskStaffAssignmentRepositoryInterface $assignmentRepository;

    protected TimetableTaskRepositoryInterface $taskRepository;

    protected LaneRequestRepositoryInterface $laneRequestRepository;

    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
        ?TaskStaffAssignmentRepositoryInterface $assignmentRepository = null,
        ?TimetableTaskRepositoryInterface $taskRepository = null,
        ?LaneRequestRepositoryInterface $laneRequestRepository = null,
    ) {
        $this->assignmentRepository = $assignmentRepository ?? app(TaskStaffAssignmentRepositoryInterface::class);
        $this->taskRepository = $taskRepository ?? app(TimetableTaskRepositoryInterface::class);
        $this->laneRequestRepository = $laneRequestRepository ?? app(LaneRequestRepositoryInterface::class);
    }

    /**
     * IDによるスタッフ取得
     */
    public function findById(int $id): ?Staff
    {
        return $this->staffRepository->findById($id);
    }

    /**
     * スタッフ詳細画面用にリレーションをロードして取得
     */
    public function getStaffWithShowDetails(Staff $staff): Staff
    {
        return $this->staffRepository->loadShowRelations($staff);
    }

    /**
     * スタッフ一覧画面データの取得
     *
     * @return array{
     *     staffMembers: LengthAwarePaginator,
     *     search: ?string,
     *     status: ?string
     * }
     */
    public function getIndexViewData(?string $search, ?string $status): array
    {
        $statusBool = null;
        if ($status === '1') {
            $statusBool = true;
        } elseif ($status === '0') {
            $statusBool = false;
        }

        $staffMembers = $this->staffRepository->paginateFiltered($search, $statusBool);

        return [
            'staffMembers' => $staffMembers,
            'search' => $search,
            'status' => $status,
        ];
    }

    /**
     * スタッフ新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function createStaff(array $data): Staff
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        return $this->staffRepository->create($data);
    }

    /**
     * スタッフ情報更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStaff(Staff $staff, array $data): bool
    {
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : false;

        return $this->staffRepository->update($staff, $data);
    }

    /**
     * スタッフ削除
     */
    public function deleteStaff(Staff $staff): bool
    {
        return $this->staffRepository->delete($staff);
    }

    /**
     * スタッフ本人によるセルフ登録（既存台帳との自動照合対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function registerSelfStaff(array $data): Staff
    {
        // 氏名・フリガナによる既存台帳照合（本部先行登録スタッフとの結合）
        $existingStaff = null;
        if (! empty($data['name']) && ! empty($data['kana'])) {
            $matched = $this->staffRepository->findByNameAndKana($data['name'], $data['kana']);
            if ($matched->count() === 1) {
                $existingStaff = $matched->first();
            }
        }

        // 既存スタッフが存在する場合は最終確認日時を更新して返却
        if ($existingStaff) {
            $this->staffRepository->update($existingStaff, [
                'last_accessed_at' => now(),
            ]);

            return $existingStaff->fresh();
        }

        // 既存に該当しない場合は新規作成
        $attributes = [
            'name' => $data['name'],
            'kana' => $data['kana'],
            'registered_via' => 'self',
            'is_active' => true,
        ];

        return $this->staffRepository->create($attributes);
    }

    /**
     * トークンによるスタッフ特定および最終確認日時の更新
     */
    public function getStaffByToken(string $token): ?Staff
    {
        $staff = $this->staffRepository->findByAccessToken($token);

        if ($staff) {
            $this->staffRepository->update($staff, [
                'last_accessed_at' => now(),
            ]);
        }

        return $staff;
    }

    /**
     * 現場マイタイムテーブル画面表示用データを構築
     *
     * @return array<string, mixed>
     */
    public function getMyShiftViewData(Staff $staff): array
    {
        $ledLanes = $this->staffRepository->loadShiftRelationsAndGetLedLanes($staff);

        $assignments = $staff->assignments->sortBy([
            fn (TaskStaffAssignment $a, TaskStaffAssignment $b): int => ($a->task?->timetable?->target_date <=> $b->task?->timetable?->target_date),
            fn (TaskStaffAssignment $a, TaskStaffAssignment $b): int => ($a->task?->start_time <=> $b->task?->start_time),
        ]);

        $resolvedTasks = $this->resolveNowAndNextTasks($assignments);
        $groupedAssignments = $assignments->groupBy(
            fn (TaskStaffAssignment $a): string => $a->date_header_label
        );

        return [
            'staff' => $staff,
            'assignments' => $assignments,
            'groupedAssignments' => $groupedAssignments,
            'currentTask' => $resolvedTasks['current_task'],
            'nextTask' => $resolvedTasks['next_task'],
            'ledLanes' => $ledLanes,
        ];
    }

    /**
     * 当日現場到着チェックイン（打刻および当日第1タスク出欠連動）
     */
    public function checkInStaff(Staff $staff): bool
    {
        if ($staff->checked_in_at === null) {
            $this->staffRepository->update($staff, [
                'checked_in_at' => now(),
            ]);
        }

        // 当日運行日のタスクの中で最も早いタスクの出欠を checked_in に連動更新
        $todayDate = now()->format('Y-m-d');
        $firstAssignment = $this->assignmentRepository->findFirstTodayAssignmentForStaff($staff->id, $todayDate);

        if ($firstAssignment && $firstAssignment->attendance_status === 'assigned') {
            $this->assignmentRepository->update($firstAssignment, ['attendance_status' => 'checked_in']);
        }

        return true;
    }

    /**
     * 現場スタッフによる自身のタスク進捗更新（着手・完了報告）
     */
    public function updateStaffAssignmentStatus(Staff $staff, int $assignmentId, string $status): bool
    {
        $assignment = $this->assignmentRepository->findForStaffById($staff->id, $assignmentId);
        if (! $assignment) {
            return false;
        }

        return $this->assignmentRepository->update($assignment, ['attendance_status' => $status]);
    }

    /**
     * 班長による担当班タスク全体のステータス更新
     */
    public function updateLaneTaskStatus(TimetableTask $task, string $status): bool
    {
        return $this->taskRepository->update($task, ['status' => $status]);
    }

    /**
     * 班長による管理者（本部）への人員不足要請・必要機材申請の作成
     *
     * @param  array<string, mixed>  $validated
     */
    public function createLaneRequestFromLeader(Lane $lane, Staff $staff, array $validated): LaneRequest
    {
        return $this->laneRequestRepository->create([
            'timetable_id' => $lane->timetable_id,
            'lane_id' => $lane->id,
            'timetable_task_id' => $validated['timetable_task_id'] ?? null,
            'requested_by_staff_id' => $staff->id,
            'request_type' => $validated['request_type'],
            'requested_count' => (int) $validated['requested_count'],
            'equipment_name' => $validated['equipment_name'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * アクセストークンの再発行（古いトークンを無効化）
     */
    public function regenerateAccessToken(Staff $staff): string
    {
        $newToken = Str::random(32);

        $this->staffRepository->update($staff, [
            'access_token' => $newToken,
            'token_reissued_at' => now(),
            'reissue_count' => $staff->reissue_count + 1,
        ]);

        return $newToken;
    }

    /**
     * 氏名・フリガナによるスタッフ照合
     *
     * @return Collection<int, Staff>
     */
    public function findByNameAndKana(string $name, string $kana): Collection
    {
        return $this->staffRepository->findByNameAndKana($name, $kana);
    }

    /**
     * スタッフのアサイン情報一覧から現在時刻に基づく Now & Next タスクを判定
     *
     * @param  iterable<TaskStaffAssignment>  $assignments
     * @return array{
     *     current_task: ?TaskStaffAssignment,
     *     next_task: ?TaskStaffAssignment
     * }
     */
    public function resolveNowAndNextTasks(iterable $assignments, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $todayDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $currentTask = null;
        $nextTask = null;

        foreach ($assignments as $assignment) {
            $task = $assignment->task;
            if (! $task || ! $task->timetable) {
                continue;
            }

            $taskDate = $task->timetable->target_date?->format('Y-m-d');

            // 当日のタスクまたは直近のタスクを対象に判定
            if ($taskDate === $todayDate) {
                if ($task->start_time <= $currentTime && ($task->end_time === null || $task->end_time >= $currentTime)) {
                    if (! $currentTask) {
                        $currentTask = $assignment;
                    }
                } elseif ($task->start_time > $currentTime) {
                    if (! $nextTask) {
                        $nextTask = $assignment;
                    }
                }
            } elseif ($taskDate > $todayDate && ! $currentTask && ! $nextTask) {
                // 当日にタスクがなく未来日のタスクがある場合、最初のタスクを次の予定とする
                $nextTask = $assignment;
            }
        }

        return [
            'current_task' => $currentTask,
            'next_task' => $nextTask,
        ];
    }
}
