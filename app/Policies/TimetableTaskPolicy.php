<?php

namespace App\Policies;

use App\Models\TimetableTask;
use App\Models\User;

class TimetableTaskPolicy
{
    /**
     * 作業タスク一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 作業タスク詳細の閲覧権限
     */
    public function view(User $user, TimetableTask $task): bool
    {
        return $user->is_active;
    }

    /**
     * 作業タスク新規登録権限（admin, operator同等）
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 作業タスク更新権限（admin, operator同等）
     */
    public function update(User $user, TimetableTask $task): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 作業タスク削除権限（admin, operator同等）
     */
    public function delete(User $user, TimetableTask $task): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
