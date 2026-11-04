<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyEquipmentTemplateRequest;
use App\Http\Requests\StoreEquipmentLogisticsPlanRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPlanRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPlanStatusRequest;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPlan;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EquipmentLogisticsPlanController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 搬出入ステップの新規登録
     */
    public function store(StoreEquipmentLogisticsPlanRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPlan::class);

        $plan = EquipmentLogisticsPlan::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$plan->title}」を登録しました。",
                'plan' => $plan->load(['fromLocationModel', 'toLocationModel', 'witnessStaff']),
            ]);
        }

        return back()->with('status', "ステップ「{$plan->title}」を登録しました。");
    }

    /**
     * 搬出入ステップの更新
     */
    public function update(UpdateEquipmentLogisticsPlanRequest $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $plan);

        $plan->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$plan->title}」を更新しました。",
                'plan' => $plan->load(['fromLocationModel', 'toLocationModel', 'witnessStaff']),
            ]);
        }

        return back()->with('status', "ステップ「{$plan->title}」を更新しました。");
    }

    /**
     * 搬出入ステップの削除
     */
    public function destroy(Request $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $plan);

        $title = $plan->title;
        $plan->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$title}」を削除しました。",
            ]);
        }

        return back()->with('status', "ステップ「{$title}」を削除しました。");
    }

    /**
     * ステータス更新（計画中 ➔ 進行中 ➔ 完了）
     */
    public function updateStatus(UpdateEquipmentLogisticsPlanStatusRequest $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();

        $plan->update(['status' => $validated['status']]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$plan->title}」のステータスを「{$plan->status_label}」に更新しました。",
                'plan' => $plan,
            ]);
        }

        return back()->with('status', "ステップ「{$plan->title}」のステータスを「{$plan->status_label}」に更新しました。");
    }

    /**
     * 資材へのカスタムパターン一括適用
     */
    public function applyTemplate(ApplyEquipmentTemplateRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $validated = $request->validated();
        $pattern = EquipmentLogisticsPattern::with('steps')->findOrFail($validated['pattern_id']);

        $count = $this->logisticsService->applyPattern(
            $equipment,
            $pattern,
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "パターン「{$pattern->name}」から{$count}件の計画ステップを生成しました。",
                'equipment' => $equipment->load('plans'),
            ]);
        }

        return back()->with('status', "パターン「{$pattern->name}」から{$count}件の計画ステップを生成しました。");
    }

    /**
     * 資材の搬出入ステップを一括削除（全クリア）
     */
    public function clear(Request $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $count = $equipment->plans()->count();
        $equipment->plans()->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資材「{$equipment->name}」の搬出入計画ステップ（全{$count}件）を削除しました。",
            ]);
        }

        return back()->with('status', "資材「{$equipment->name}」の搬出入計画ステップ（全{$count}件）を削除しました。");
    }
}
