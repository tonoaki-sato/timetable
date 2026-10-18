<?php

namespace App\Services;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
}
