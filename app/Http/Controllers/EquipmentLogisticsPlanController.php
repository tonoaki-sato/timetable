<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyEquipmentTemplateRequest;
use App\Http\Requests\StoreEquipmentLogisticsPlanRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPlanRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPlanStatusRequest;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;
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
    public function store(StoreEquipmentLogisticsPlanRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPlan::class);

        $validated = $request->validated();
        $validated['timetable_id'] = $timetable->id;

        $plan = EquipmentLogisticsPlan::create($validated);

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
    public function update(UpdateEquipmentLogisticsPlanRequest $request, Timetable $timetable, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        abort_if($plan->timetable_id !== $timetable->id, 404);
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
    public function destroy(Request $request, Timetable $timetable, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        abort_if($plan->timetable_id !== $timetable->id, 404);
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
    public function updateStatus(UpdateEquipmentLogisticsPlanStatusRequest $request, Timetable $timetable, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        abort_if($plan->timetable_id !== $timetable->id, 404);
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
    public function applyTemplate(ApplyEquipmentTemplateRequest $request, Timetable $timetable, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $validated = $request->validated();
        $pattern = EquipmentLogisticsPattern::with('steps')->findOrFail($validated['pattern_id']);

        $count = $this->logisticsService->applyPattern(
            $equipment,
            $pattern,
            $validated,
            $timetable
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "パターン「{$pattern->name}」から{$count}件の計画ステップを生成しました。",
                'equipment' => $equipment->load(['plans' => fn ($q) => $q->where('timetable_id', $timetable->id)]),
            ]);
        }

        return back()->with('status', "パターン「{$pattern->name}」から{$count}件の計画ステップを生成しました。");
    }

    /**
     * 資材の搬出入ステップを一括削除（全クリア）
     */
    public function clear(Request $request, Timetable $timetable, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $query = $equipment->plans()->where('timetable_id', $timetable->id);
        $count = $query->count();
        $query->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資材「{$equipment->name}」の搬出入計画ステップ（全{$count}件）を削除しました。",
            ]);
        }

        return back()->with('status', "資材「{$equipment->name}」の搬出入計画ステップ（全{$count}件）を削除しました。");
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

    public function updateStatusLegacy(UpdateEquipmentLogisticsPlanStatusRequest $request, EquipmentLogisticsPlan $plan): RedirectResponse|JsonResponse
    {
        $timetable = $plan->timetable ?? $this->resolveFallbackTimetable();

        return $this->updateStatus($request, $timetable, $plan);
    }

    public function applyTemplateLegacy(ApplyEquipmentTemplateRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->applyTemplate($request, $timetable, $equipment);
    }

    public function clearLegacy(Request $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->clear($request, $timetable, $equipment);
    }

    protected function resolveFallbackTimetable(?Request $request = null): Timetable
    {
        if ($request && $request->filled('timetable_id')) {
            $found = Timetable::find($request->integer('timetable_id'));
            if ($found) {
                return $found;
            }
        }

        return Timetable::where('status', 'active')->orderBy('target_date', 'asc')->first()
            ?? Timetable::orderBy('target_date', 'asc')->first()
            ?? Timetable::factory()->create(['name' => '既定運行計画', 'target_date' => now()->toDateString()]);
    }
}
