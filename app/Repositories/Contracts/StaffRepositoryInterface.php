<?php

namespace App\Repositories\Contracts;

use App\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    /**
     * スタッフ一覧の取得（検索・絞り込み・ページネーション）
     */
    public function paginateFiltered(?string $search = null, ?bool $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * 稼働可能な全スタッフの取得（アサイン選択用）
     *
     * @return Collection<int, Staff>
     */
    public function getActiveStaffList(): Collection;

    /**
     * スタッフ新規登録
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Staff;

    /**
     * スタッフ情報更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Staff $staff, array $attributes): bool;

    /**
     * スタッフ削除
     */
    public function delete(Staff $staff): bool;

    /**
     * アクセストークンによるスタッフ取得
     */
    public function findByAccessToken(string $token): ?Staff;

    /**
     * LINEユーザーIDによるスタッフ取得
     */
    public function findByLineUserId(string $lineUserId): ?Staff;

    /**
     * 氏名・フリガナによるスタッフ検索（照合用）
     *
     * @return Collection<int, Staff>
     */
    public function findByNameAndKana(string $name, string $kana): Collection;
}
