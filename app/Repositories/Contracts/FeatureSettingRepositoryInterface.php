<?php

namespace App\Repositories\Contracts;

use App\Models\FeatureSetting;
use Illuminate\Database\Eloquent\Collection;

interface FeatureSettingRepositoryInterface
{
    /**
     * 機能キーによる設定取得
     */
    public function findByKey(string $featureKey): ?FeatureSetting;

    /**
     * 機能キーによる設定取得（存在しない場合は初期作成）
     *
     * @param  array<string, mixed>  $defaultAttributes
     */
    public function getOrCreate(string $featureKey, array $defaultAttributes = []): FeatureSetting;

    /**
     * 機能キーによる設定更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateByKey(string $featureKey, array $attributes): FeatureSetting;

    /**
     * 全機能設定一覧の取得
     *
     * @return Collection<int, FeatureSetting>
     */
    public function getAll(): Collection;
}
