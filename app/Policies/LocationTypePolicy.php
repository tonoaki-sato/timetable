<?php

namespace App\Policies;

use App\Models\LocationType;
use App\Models\User;

class LocationTypePolicy
{
    /**
     * 拠点タイプ一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 拠点タイプ詳細の閲覧権限
     */
    public function view(User $user, LocationType $locationType): bool
    {
        return $user->is_active;
    }

    /**
     * 拠点タイプ新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 拠点タイプ更新権限
     */
    public function update(User $user, LocationType $locationType): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 拠点タイプ削除権限
     */
    public function delete(User $user, LocationType $locationType): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
