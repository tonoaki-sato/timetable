<?php

namespace App\Policies;

use App\Models\Lane;
use App\Models\User;

class LanePolicy
{
    /**
     * 担当班マスター一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 担当班マスター詳細の閲覧権限
     */
    public function view(User $user, Lane $lane): bool
    {
        return $user->is_active;
    }

    /**
     * 担当班マスター新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 担当班マスター更新権限
     */
    public function update(User $user, ?Lane $lane = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 担当班マスター削除権限
     */
    public function delete(User $user, Lane $lane): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
