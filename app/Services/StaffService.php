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
     * スタッフ本人によるセルフ登録
     *
     * @param  array<string, mixed>  $data
     */
    public function registerSelfStaff(array $data): Staff
    {
        $lineDisplayName = $data['line_display_name'] ?? null;
        if (empty($lineDisplayName) && ! empty($data['manual_line_display_name'])) {
            $lineDisplayName = $data['manual_line_display_name'];
        }

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
     * 当日現場到着チェックイン
     */
    public function checkInStaff(Staff $staff): bool
    {
        if ($staff->checked_in_at === null) {
            return $this->staffRepository->update($staff, [
                'checked_in_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * アクセストークンの再発行（古いトークンを無効化）
     */
    public function regenerateAccessToken(Staff $staff): string
    {
        $newToken = Str::random(32);

        $this->staffRepository->update($staff, [
            'access_token' => $newToken,
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
