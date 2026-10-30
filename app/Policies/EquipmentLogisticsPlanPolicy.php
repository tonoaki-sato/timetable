<?php

namespace App\Policies;

use App\Models\EquipmentLogisticsPlan;
use App\Models\User;

class EquipmentLogisticsPlanPolicy
{
    /**
     * 搬出入計画一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 搬出入計画詳細の閲覧権限
     */
    public function view(User $user, EquipmentLogisticsPlan $plan): bool
    {
        return $user->is_active;
    }

    /**
     * 搬出入計画新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 搬出入計画更新権限
     */
    public function update(User $user, EquipmentLogisticsPlan $plan): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 搬出入計画削除権限
     */
    public function delete(User $user, EquipmentLogisticsPlan $plan): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}

