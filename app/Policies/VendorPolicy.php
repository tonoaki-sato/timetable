<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    /**
     * 出展・出店者一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 出展・出店者詳細の閲覧権限
     */
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->is_active;
    }

    /**
     * 出展・出店者の新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 出展・出店者の更新権限
     */
    public function update(User $user, ?Vendor $vendor = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 出展・出店者の削除権限
     */
    public function delete(User $user, ?Vendor $vendor = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
