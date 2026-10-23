<?php

namespace App\Policies;

use App\Models\Lane;
use App\Models\User;

class LanePolicy
{
    /**
     * レーン一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * レーン詳細の閲覧権限
     */
    public function view(User $user, Lane $lane): bool
    {
        return $user->is_active;
    }

    /**
     * レーン新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * レーン更新権限（主担当設定含む）
     */
    public function update(User $user, ?Lane $lane = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * レーン削除権限
     */
    public function delete(User $user, Lane $lane): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
