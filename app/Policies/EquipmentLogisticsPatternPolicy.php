<?php

namespace App\Policies;

use App\Models\EquipmentLogisticsPattern;
use App\Models\User;

class EquipmentLogisticsPatternPolicy
{
    /**
     * パターン一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * パターン詳細の閲覧権限
     */
    public function view(User $user, EquipmentLogisticsPattern $pattern): bool
    {
        return $user->is_active;
    }

    /**
     * パターン新規作成権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * パターン更新権限
     */
    public function update(User $user, EquipmentLogisticsPattern $pattern): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * パターン削除権限
     */
    public function delete(User $user, EquipmentLogisticsPattern $pattern): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
