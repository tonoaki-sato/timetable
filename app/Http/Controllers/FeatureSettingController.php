<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFeatureSettingRequest;
use App\Services\FeatureSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
    public function toggle(Request $request, string $featureKey): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->is_active || (! $user->isAdmin() && ! $user->isOperator())) {
            abort(403, 'この操作を行う権限がありません。');
        }

        $feature = $this->featureSettingService->toggleFeature($featureKey);

        $stateText = $feature->is_enabled ? '「受付中（有効）」' : '「停止中（無効）」';

        return back()->with('success', "「{$feature->name}」のステータスを {$stateText} に変更しました。");
    }
}
