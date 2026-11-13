<?php

namespace App\Policies;

use App\Models\OrganizationUnit;
use App\Models\User;

class OrganizationUnitPolicy
{
    /**
     * 組織一覧・組織図の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 組織詳細の閲覧権限
     */
    public function view(User $user, OrganizationUnit $organizationUnit): bool
    {
        return $user->is_active;
    }

    /**
     * 組織新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 組織更新権限
     */
    public function update(User $user, OrganizationUnit $organizationUnit): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 組織削除権限
     */
    public function delete(User $user, OrganizationUnit $organizationUnit): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}

