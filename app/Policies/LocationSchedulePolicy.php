<?php

namespace App\Policies;

use App\Models\LocationSchedule;
use App\Models\User;

class LocationSchedulePolicy
{
    /**
     * 拠点スケジュール一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 拠点スケジュール詳細の閲覧権限
     */
    public function view(User $user, LocationSchedule $schedule): bool
    {
        return $user->is_active;
    }

    /**
     * 拠点スケジュール新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 拠点スケジュール更新権限
     */
    public function update(User $user, LocationSchedule $schedule): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 拠点スケジュール削除権限
     */
    public function delete(User $user, LocationSchedule $schedule): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
