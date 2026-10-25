<?php

namespace App\Services;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class StaffService
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository
    ) {}

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
     * スタッフ本人によるセルフ登録（既存台帳との自動照合・バインド対応）
     *
     * @param  array<string, mixed>  $data
     */
    public function registerSelfStaff(array $data): Staff
    {
        $lineDisplayName = $data['line_display_name'] ?? null;
        if (empty($lineDisplayName) && ! empty($data['manual_line_display_name'])) {
            $lineDisplayName = $data['manual_line_display_name'];
        }

        // 1. LINE User IDによる既存照合
        $existingStaff = null;
        if (! empty($data['line_user_id'])) {
            $existingStaff = $this->staffRepository->findByLineUserId($data['line_user_id']);
        }

        // 2. 氏名・フリガナによる既存台帳照合（本部先行登録スタッフとの結合）
        if (! $existingStaff && ! empty($data['name']) && ! empty($data['kana'])) {
            $matched = $this->staffRepository->findByNameAndKana($data['name'], $data['kana']);
            if ($matched->count() === 1) {
                $existingStaff = $matched->first();
            }
        }

        // 3. 既存スタッフが存在する場合はLINE情報を統合バインド
        if ($existingStaff) {
            $updateData = [
                'last_accessed_at' => now(),
            ];

            if (! empty($data['line_user_id']) && empty($existingStaff->line_user_id)) {
                $updateData['line_user_id'] = $data['line_user_id'];
            }

            if (! empty($lineDisplayName)) {
                $updateData['line_display_name'] = $lineDisplayName;
            }

            if (! empty($data['line_picture_url'])) {
                $updateData['line_picture_url'] = $data['line_picture_url'];
            }

            $this->staffRepository->update($existingStaff, $updateData);

            return $existingStaff->fresh();
        }

        // 4. 既存に該当しない場合は新規作成
        $attributes = [
            'name' => $data['name'],
            'kana' => $data['kana'],
            'line_user_id' => $data['line_user_id'] ?? null,
            'line_display_name' => $lineDisplayName,
            'line_picture_url' => $data['line_picture_url'] ?? null,
            'registered_via' => 'self',
            'is_active' => true,
        ];

        return $this->staffRepository->create($attributes);
    }

    /**
     * マイシフト画面等からのLINEアカウント直接バインド
     *
     * @param  array{line_user_id: string, line_display_name?: ?string, line_picture_url?: ?string}  $data
     */
    public function bindLineProfile(Staff $staff, array $data): bool
    {
        // 既に他のスタッフに同一のLINE User IDが割り当てられていないか確認
        $other = $this->staffRepository->findByLineUserId($data['line_user_id']);
        if ($other && $other->id !== $staff->id) {
            return false;
        }

        $updateData = [
            'line_user_id' => $data['line_user_id'],
            'last_accessed_at' => now(),
        ];

        if (! empty($data['line_display_name'])) {
            $updateData['line_display_name'] = $data['line_display_name'];
        }

        if (! empty($data['line_picture_url'])) {
            $updateData['line_picture_url'] = $data['line_picture_url'];
        }

        return $this->staffRepository->update($staff, $updateData);
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
     * LINEユーザーIDによるスタッフ特定
     */
    public function getStaffByLineUserId(string $lineUserId): ?Staff
    {
        return $this->staffRepository->findByLineUserId($lineUserId);
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
        $firstAssignment = $staff->assignments()
            ->whereHas('task.timetable', function ($q) use ($todayDate) {
                $q->whereDate('target_date', $todayDate);
            })
            ->join('timetable_tasks', 'task_staff_assignments.timetable_task_id', '=', 'timetable_tasks.id')
            ->orderBy('timetable_tasks.start_time', 'asc')
            ->select('task_staff_assignments.*')
            ->first();

        if ($firstAssignment && $firstAssignment->attendance_status === 'assigned') {
            $firstAssignment->update(['attendance_status' => 'checked_in']);
        }

        return true;
    }

    /**
     * 現場スタッフによる自身のタスク進捗更新（着手・完了報告）
     */
    public function updateStaffAssignmentStatus(Staff $staff, int $assignmentId, string $status): bool
    {
        $assignment = $staff->assignments()->find($assignmentId);
        if (! $assignment) {
            return false;
        }

        return $assignment->update(['attendance_status' => $status]);
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
    public function findByNameAndKana(string $name, string $kana)
    {
        return $this->staffRepository->findByNameAndKana($name, $kana);
    }
}
