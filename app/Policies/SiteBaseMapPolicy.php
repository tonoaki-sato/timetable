<?php

namespace App\Policies;

use App\Models\SiteBaseMap;
use App\Models\User;

class SiteBaseMapPolicy
{
    /**
     * 白地図エディタ・配置一覧・PDFの閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 白地図詳細の閲覧権限
     */
    public function view(User $user, SiteBaseMap $siteBaseMap): bool
    {
        return $user->is_active;
    }

    /**
     * 白地図配置・カスタムアイコンの新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 白地図配置・カスタムアイコンの更新権限
     */
    public function update(User $user, ?SiteBaseMap $siteBaseMap = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 白地図配置・カスタムアイコンの削除権限
     */
    public function delete(User $user, ?SiteBaseMap $siteBaseMap = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
