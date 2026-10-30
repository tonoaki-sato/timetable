<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyEquipmentTemplateRequest;
use App\Http\Requests\StoreEquipmentLogisticsPlanRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPlanRequest;
use App\Models\Equipment;
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
    public function updateStatus(Request $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $plan);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:planned,in_progress,completed,cancelled'],
        ]);

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
     * 既存資材へのテンプレート適用
     */
    public function applyTemplate(ApplyEquipmentTemplateRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $validated = $request->validated();
        $count = $this->logisticsService->applyTemplate(
            $equipment,
            $validated['template_type'],
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count}件の計画ステップを生成しました。",
                'equipment' => $equipment->load('plans'),
            ]);
        }

        return back()->with('status', "{$count}件の計画ステップを生成しました。");
    }
}
