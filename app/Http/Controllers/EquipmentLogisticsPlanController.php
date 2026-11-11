<?php

namespace App\Http\Controllers;

use App\DTOs\ApplyEquipmentPatternDTO;
use App\Http\Requests\ApplyEquipmentTemplateRequest;
use App\Http\Requests\StoreEquipmentLogisticsPlanRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPlanRequest;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;
use App\Services\EquipmentLogisticsService;
use App\Services\TimetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EquipmentLogisticsPlanController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService,
        protected TimetableService $timetableService
    ) {}

    /**
     * 搬出入ステップの新規登録
     */
    public function store(StoreEquipmentLogisticsPlanRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPlan::class);

        $plan = $this->logisticsService->createPlan($timetable, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$plan->title}」を登録しました。",
                'plan' => $plan,
            ]);
        }

        return back()->with('status', "ステップ「{$plan->title}」を登録しました。");
    }

    /**
     * 搬出入ステップの更新
     */
    public function update(UpdateEquipmentLogisticsPlanRequest $request, Timetable $timetable, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        abort_if($plan->timetable_id !== $timetable->id, 404);
        $this->authorize('update', $plan);

        $plan = $this->logisticsService->updatePlan($plan, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$plan->title}」を更新しました。",
                'plan' => $plan,
            ]);
        }

        return back()->with('status', "ステップ「{$plan->title}」を更新しました。");
    }

    /**
     * 搬出入ステップの削除
     */
    public function destroy(Request $request, Timetable $timetable, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        abort_if($plan->timetable_id !== $timetable->id, 404);
        $this->authorize('delete', $plan);

        $title = $plan->title;
        $this->logisticsService->deletePlan($plan);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "ステップ「{$title}」を削除しました。",
            ]);
        }

        return back()->with('status', "ステップ「{$title}」を削除しました。");
    }

    /**
     * 資材へのカスタムパターン一括適用
     */
    public function applyTemplate(ApplyEquipmentTemplateRequest $request, Timetable $timetable, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $dto = ApplyEquipmentPatternDTO::fromArray($request->validated());
        $result = $this->logisticsService->applyPatternById($equipment, $dto, $timetable);
        $pattern = $result['pattern'];
        $count = $result['count'];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "パターン「{$pattern->name}」から{$count}件の計画ステップを生成しました。",
                'equipment' => $result['equipment'],
            ]);
        }

        return back()->with('status', "パターン「{$pattern->name}」から{$count}件の計画ステップを生成しました。");
    }

    // ==========================================
    // 既存互換フォールバックアクション群
    // ==========================================

    public function storeLegacy(StoreEquipmentLogisticsPlanRequest $request): RedirectResponse|JsonResponse
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->store($request, $timetable);
    }

    public function updateLegacy(UpdateEquipmentLogisticsPlanRequest $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $timetable = $plan->timetable ?? $this->resolveFallbackTimetable();

        return $this->update($request, $timetable, $plan);
    }

    public function destroyLegacy(Request $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $timetable = $plan->timetable ?? $this->resolveFallbackTimetable();

        return $this->destroy($request, $timetable, $plan);
    }

    public function applyTemplateLegacy(ApplyEquipmentTemplateRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->applyTemplate($request, $timetable, $equipment);
    }

    protected function resolveFallbackTimetable(?Request $request = null): Timetable
    {
        $timetableId = ($request && $request->filled('timetable_id'))
            ? $request->integer('timetable_id')
            : null;

        return $this->timetableService->resolveFallbackTimetable($timetableId);
    }
}
