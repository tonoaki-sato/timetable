<?php

namespace App\Policies;

use App\Models\Timetable;
use App\Models\User;

class TimetablePolicy
{
    /**
     * タイムテーブル一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * タイムテーブル詳細の閲覧権限
     */
    public function view(User $user, Timetable $timetable): bool
    {
        return $user->is_active;
    }

    /**
     * タイムテーブル新規登録権限（admin, operator同等）
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * タイムテーブル更新権限（admin, operator同等）
     */
    public function update(User $user, Timetable $timetable): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * タイムテーブル削除権限（admin, operator同等）
     */
    public function delete(User $user, Timetable $timetable): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
