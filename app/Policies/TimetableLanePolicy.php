<?php

namespace App\Policies;

use App\Models\TimetableLane;
use App\Models\User;

class TimetableLanePolicy
{
    /**
     * 運行日別担当班編成一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 運行日別担当班編成詳細の閲覧権限
     */
    public function view(User $user, TimetableLane $timetableLane): bool
    {
        return $user->is_active;
    }

    /**
     * 運行日別担当班編成の登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 運行日別担当班編成の更新権限（班長・固定スタッフ・並び順変更含む）
     */
    public function update(User $user, ?TimetableLane $timetableLane = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 運行日別担当班編成の解除・削除権限
     */
    public function delete(User $user, TimetableLane $timetableLane): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
