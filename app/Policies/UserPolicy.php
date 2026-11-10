<?php

namespace App\Policies;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserPolicy
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * ユーザー一覧の閲覧権限
     * 有効な admin または operator のみ
     */
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * ユーザー詳細の閲覧権限
     * admin, operator, または ログインユーザー本人
     */
    public function view(User $user, User $model): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || $user->isOperator() || $user->id === $model->id;
    }

    /**
     * ユーザー新規登録権限
     * admin のみ
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    /**
     * ユーザー情報更新権限
     * admin または ログインユーザー本人
     */
    public function update(User $user, User $model): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * ユーザー削除権限
     * admin のみ（かつ自身の削除禁止・最後の1人の管理者削除禁止）
     */
    public function delete(User $user, User $model): bool
    {
        if (! $user->is_active || ! $user->isAdmin()) {
            return false;
        }

        // 自分自身の削除は禁止
        if ($user->id === $model->id) {
            return false;
        }

        // 最後の有効な管理者の削除は禁止
        if ($model->isAdmin()) {
            $activeAdminCount = $this->userRepository->countActiveAdmins();
            if ($activeAdminCount <= 1) {
                return false;
            }
        }

        return true;
    }
}
