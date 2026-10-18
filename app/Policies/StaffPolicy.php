<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    /**
     * スタッフ一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * スタッフ詳細の閲覧権限
     */
    public function view(User $user, Staff $staff): bool
    {
        return $user->is_active;
    }

    /**
     * スタッフ新規登録権限（admin, operator同等）
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * スタッフ情報更新権限（admin, operator同等）
     */
    public function update(User $user, Staff $staff): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * スタッフ削除権限（admin, operator同等）
     */
    public function delete(User $user, Staff $staff): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
