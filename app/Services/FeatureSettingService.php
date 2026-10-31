<?php

namespace App\Services;

use App\Models\FeatureSetting;
use App\Repositories\Contracts\FeatureSettingRepositoryInterface;
use Carbon\CarbonInterface;

class FeatureSettingService
{
    public function __construct(
        protected FeatureSettingRepositoryInterface $featureSettingRepository
    ) {}

    /**
     * 機能設定の取得（存在しない場合は作成）
     *
     * @param  array<string, mixed>  $defaultAttributes
     */
    public function getFeature(string $featureKey, array $defaultAttributes = []): FeatureSetting
    {
        return $this->featureSettingRepository->getOrCreate($featureKey, $defaultAttributes);
    }

    /**
     * 指定した機能が現在利用可能（オープン）かどうかを判定
     */
    public function isFeatureOpen(string $featureKey, ?CarbonInterface $now = null): bool
    {
        $feature = $this->getFeature($featureKey);

        return $feature->isOpen($now);
    }

    /**
     * 機能の利用可能状態・ステータスラベル・案内メッセージのセットを取得
     *
     * @return array{
     *     isOpen: bool,
     *     status: string,
     *     label: string,
     *     message: string,
     *     feature: FeatureSetting
     * }
     */
    public function getFeatureStatus(string $featureKey, ?CarbonInterface $now = null): array
    {
        $feature = $this->getFeature($featureKey);

        return [
            'isOpen' => $feature->isOpen($now),
            'status' => $feature->getStatus($now),
            'label' => $feature->getStatusLabel($now),
            'message' => $feature->getClosedMessage($now),
            'feature' => $feature,
        ];
    }

    /**
     * 機能設定の更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateFeature(string $featureKey, array $data): FeatureSetting
    {
        return $this->featureSettingRepository->updateByKey($featureKey, $data);
    }

    /**
     * 機能の即時有効/無効切り替え（トグル）
     */
    public function toggleFeature(string $featureKey): FeatureSetting
    {
        $feature = $this->getFeature($featureKey);

        return $this->featureSettingRepository->updateByKey($featureKey, [
            'is_enabled' => ! $feature->is_enabled,
        ]);
    }
}
