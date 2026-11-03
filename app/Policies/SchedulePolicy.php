<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    /**
     * スケジュール一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * スケジュール詳細の閲覧権限
     */
    public function view(User $user, Schedule $schedule): bool
    {
        return $user->is_active;
    }

    /**
     * スケジュールの新規作成権限（admin, operator同等）
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * スケジュールの編集・更新権限（admin, operator同等）
     */
    public function update(User $user, Schedule $schedule): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * スケジュールの削除権限（admin, operator同等）
     */
    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
