<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentLogisticsPatternStepRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPatternStepRequest;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EquipmentLogisticsPatternStepController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * パターンにステップを新規追加
     */
    public function store(StoreEquipmentLogisticsPatternStepRequest $request, EquipmentLogisticsPattern $pattern): RedirectResponse|JsonResponse
    {
        $step = $this->logisticsService->addPatternStep($pattern, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$step->title}」を追加しました。",
                'step' => $step,
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "ステップ「{$step->title}」を追加しました。");
    }

    /**
     * パターンのステップを更新
     */
    public function update(UpdateEquipmentLogisticsPatternStepRequest $request, EquipmentLogisticsPattern $pattern, EquipmentLogisticsPatternStep $step): RedirectResponse|JsonResponse
    {
        $this->logisticsService->updatePatternStep($step, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$step->title}」を更新しました。",
                'step' => $step->fresh(),
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "ステップ「{$step->title}」を更新しました。");
    }

    /**
     * パターンのステップを削除
     */
    public function destroy(Request $request, EquipmentLogisticsPattern $pattern, EquipmentLogisticsPatternStep $step): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $pattern);

        $title = $step->title;
        $this->logisticsService->deletePatternStep($step);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$title}」を削除しました。",
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "ステップ「{$title}」を削除しました。");
    }
}

