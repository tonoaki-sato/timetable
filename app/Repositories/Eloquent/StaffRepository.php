<?php

namespace App\Repositories\Eloquent;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository implements StaffRepositoryInterface
{
    /**
     * スタッフ一覧の取得（検索・絞り込み・ページネーション）
     */
    public function paginateFiltered(?string $search = null, ?bool $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Staff::query()->withCount('assignments');

        if ($search) {
            $query->search($search);
        }

        if ($status !== null) {
            $query->where('is_active', $status);
        }

        return $query->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * 稼働可能な全スタッフの取得（アサイン選択用）
     *
     * @return Collection<int, Staff>
     */
    public function getActiveStaffList(): Collection
    {
        return Staff::active()->orderBy('kana', 'asc')->orderBy('name', 'asc')->get();
    }

    /**
     * スタッフ新規登録
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Staff
    {
        return Staff::create($attributes);
    }

    /**
     * スタッフ情報更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Staff $staff, array $attributes): bool
    {
        return $staff->update($attributes);
    }

    /**
     * スタッフ削除
     */
    public function delete(Staff $staff): bool
    {
        return (bool) $staff->delete();
    }
}
