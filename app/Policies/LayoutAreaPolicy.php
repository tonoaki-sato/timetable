<?php

namespace App\Policies;

use App\Models\LayoutArea;
use App\Models\User;

/**
 * 会場エリアマスター管理ポリシー
 */
class LayoutAreaPolicy
{
    /**
     * 会場エリア一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 会場エリア詳細の閲覧権限
     */
    public function view(User $user, LayoutArea $layoutArea): bool
    {
        return $user->is_active;
    }

    /**
     * 会場エリアの新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 会場エリアの更新権限
     */
    public function update(User $user, ?LayoutArea $layoutArea = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 会場エリアの削除権限
     */
    public function delete(User $user, ?LayoutArea $layoutArea = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
