<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderEquipmentLogisticsPatternStepsRequest;
use App\Http\Requests\StoreEquipmentLogisticsPatternStepRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPatternStepRequest;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;
use App\Services\EquipmentLogisticsPatternService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EquipmentLogisticsPatternStepController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsPatternService $patternService
    ) {}

    /**
     * パターンにステップを新規追加
     */
    public function store(StoreEquipmentLogisticsPatternStepRequest $request, EquipmentLogisticsPattern $pattern): RedirectResponse|JsonResponse
    {
        $step = $this->patternService->addPatternStep($pattern, $request->validated());

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
        $updatedStep = $this->patternService->updatePatternStep($step, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$updatedStep->title}」を更新しました。",
                'step' => $updatedStep,
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "ステップ「{$updatedStep->title}」を更新しました。");
    }

    /**
     * パターン内ステップの表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderEquipmentLogisticsPatternStepsRequest $request, EquipmentLogisticsPattern $pattern): JsonResponse
    {
        $this->patternService->reorderPatternSteps($pattern, $request->validated('step_ids'));

        return response()->json([
            'success' => true,
            'message' => 'ステップの表示順を更新しました。',
        ]);
    }

    /**
     * パターンのステップを削除
     */
    public function destroy(Request $request, EquipmentLogisticsPattern $pattern, EquipmentLogisticsPatternStep $step): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $pattern);

        $title = $step->title;
        $this->patternService->deletePatternStep($step);

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
