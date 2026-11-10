<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * 条件付きユーザー一覧の取得（ページネーション）
     */
    public function paginateFiltered(
        ?string $search = null,
        ?string $role = null,
        ?bool $status = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * 有効な管理者（admin）ユーザーの件数を取得
     */
    public function countActiveAdmins(): int;

    /**
     * ユーザー新規作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User;

    /**
     * ユーザー情報更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): bool;

    /**
     * ユーザー削除
     */
    public function delete(User $user): bool;
}
