<?php

namespace App\Policies;

use App\Models\AnnualSchedule;
use App\Models\User;

class AnnualSchedulePolicy
{
    /**
     * 年間スケジュール一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 年間スケジュール詳細の閲覧権限
     */
    public function view(User $user, AnnualSchedule $annualSchedule): bool
    {
        return $user->is_active;
    }

    /**
     * 年間スケジュールの新規作成権限（admin, operator同等）
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 年間スケジュールの編集・更新権限（admin, operator同等）
     */
    public function update(User $user, AnnualSchedule $annualSchedule): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 年間スケジュールの削除権限（admin, operator同等）
     */
    public function delete(User $user, AnnualSchedule $annualSchedule): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
