<?php

namespace App\Policies;

use App\Models\LayoutMap;
use App\Models\User;

class LayoutMapPolicy
{
    /**
     * 配置計画一覧・詳細の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 配置計画詳細の閲覧権限
     */
    public function view(User $user, LayoutMap $layoutMap): bool
    {
        return $user->is_active;
    }

    /**
     * 配置アイテムの新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 配置アイテムの更新権限
     */
    public function update(User $user, ?LayoutMap $layoutMap = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 配置アイテムの削除権限
     */
    public function delete(User $user, ?LayoutMap $layoutMap = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
