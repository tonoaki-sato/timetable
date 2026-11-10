<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleFeatureSettingRequest;
use App\Http\Requests\UpdateFeatureSettingRequest;
use App\Services\FeatureSettingService;
use Illuminate\Http\RedirectResponse;

class FeatureSettingController extends Controller
{
    public function __construct(
        protected FeatureSettingService $featureSettingService
    ) {}

    /**
     * 機能設定の更新
     */
    public function update(UpdateFeatureSettingRequest $request, string $featureKey): RedirectResponse
    {
        $validated = $request->validated();

        $feature = $this->featureSettingService->updateFeature($featureKey, $validated);

        return back()->with('success', "「{$feature->name}」の設定を更新しました。");
    }

    /**
     * 機能の即時有効/無効切り替え
     */
    public function toggle(ToggleFeatureSettingRequest $request, string $featureKey): RedirectResponse
    {
        $feature = $this->featureSettingService->toggleFeature($featureKey);
        $status = $this->featureSettingService->getFeatureStatus($featureKey);
        $stateText = $status['label'];

        return back()->with('success', "「{$feature->name}」のステータスを「{$stateText}」に変更しました。");
    }
}
