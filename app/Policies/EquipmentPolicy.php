<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    /**
     * 資材一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 資材詳細の閲覧権限
     */
    public function view(User $user, Equipment $equipment): bool
    {
        return $user->is_active;
    }

    /**
     * 資材新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 資材更新権限
     */
    public function update(User $user, Equipment $equipment): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 資材削除権限
     */
    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}

