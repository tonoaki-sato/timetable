<?php

namespace App\Policies;

use App\Models\SiteBaseMap;
use App\Models\User;

/**
 * 白地図マスター管理・白地図配置エディタ・カスタムアイコン定義共通ポリシー
 */
class SiteBaseMapPolicy
{
    /**
     * 白地図マスター一覧・エディタ・配置一覧・PDFの閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 白地図マスター詳細の閲覧権限
     */
    public function view(User $user, SiteBaseMap $siteBaseMap): bool
    {
        return $user->is_active;
    }

    /**
     * 白地図マスター・白地図配置・カスタムアイコンの新規登録権限
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 白地図マスター・白地図配置・カスタムアイコンの更新権限
     */
    public function update(User $user, ?SiteBaseMap $siteBaseMap = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }

    /**
     * 白地図マスター・白地図配置・カスタムアイコンの削除権限
     */
    public function delete(User $user, ?SiteBaseMap $siteBaseMap = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
