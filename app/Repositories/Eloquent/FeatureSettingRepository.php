<?php

namespace App\Repositories\Eloquent;

use App\Models\FeatureSetting;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FeatureSettingRepository implements FeatureSettingRepositoryInterface
{
    /**
     * 機能キーによる設定取得
     */
    public function findByKey(string $featureKey): ?FeatureSetting
    {
        return FeatureSetting::where('feature_key', $featureKey)->first();
    }

    /**
     * 機能キーによる設定取得（存在しない場合は初期作成）
     *
     * @param  array<string, mixed>  $defaultAttributes
     */
    public function getOrCreate(string $featureKey, array $defaultAttributes = []): FeatureSetting
    {
        return FeatureSetting::firstOrCreate(
            ['feature_key' => $featureKey],
            array_merge([
                'name' => $featureKey,
                'is_enabled' => true,
            ], $defaultAttributes)
        );
    }

    /**
     * 機能キーによる設定更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateByKey(string $featureKey, array $attributes): FeatureSetting
    {
        $setting = $this->getOrCreate($featureKey);
        $setting->update($attributes);

        return $setting->fresh();
    }

    /**
     * 全機能設定一覧の取得
     *
     * @return Collection<int, FeatureSetting>
     */
    public function getAll(): Collection
    {
        return FeatureSetting::orderBy('name', 'asc')->get();
    }
}
