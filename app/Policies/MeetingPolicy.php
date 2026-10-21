<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    /**
     * 会議一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 会議詳細の閲覧権限
     */
    public function view(User $user, Meeting $meeting): bool
    {
        return $user->is_active;
    }

    /**
     * 会議の新規作成権限（admin, operator同等）
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 会議の編集・更新権限（admin, operator同等）
     */
    public function update(User $user, Meeting $meeting): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 会議の削除権限（admin, operator同等）
     */
    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
