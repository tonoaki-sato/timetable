<?php

namespace App\Policies;

use App\Models\FeatureSetting;
use App\Models\User;

class FeatureSettingPolicy
{
    /**
     * 機能設定一覧の閲覧権限
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * 機能設定詳細の閲覧権限
     */
    public function view(User $user, FeatureSetting $featureSetting): bool
    {
        return $user->is_active;
    }

    /**
     * 機能設定の更新・切り替え権限
     */
    public function update(User $user, ?FeatureSetting $featureSetting = null): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOperator());
    }
}
