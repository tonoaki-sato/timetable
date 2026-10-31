<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * 拠点一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 拠点詳細の閲覧権限
     */
    public function view(User $user, Location $location): bool
    {
        return $user->is_active;
    }

    /**
     * 拠点新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 拠点更新権限
     */
    public function update(User $user, Location $location): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 拠点削除権限
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
