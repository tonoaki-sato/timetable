<?php

namespace App\Policies;

use App\Models\LaneRequest;
use App\Models\User;

class LaneRequestPolicy
{
    /**
     * 班要請一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 班要請詳細の閲覧権限
     */
    public function view(User $user, LaneRequest $laneRequest): bool
    {
        return $user->is_active;
    }

    /**
     * 班要請ステータスの更新権限
     */
    public function update(User $user, ?LaneRequest $laneRequest = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 班要請の削除権限
     */
    public function delete(User $user, ?LaneRequest $laneRequest = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
