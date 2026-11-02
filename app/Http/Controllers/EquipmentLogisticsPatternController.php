<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentLogisticsPatternRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPatternRequest;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipmentLogisticsPatternController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 搬出入パターン（テンプレート）の新規作成
     */
    public function store(StoreEquipmentLogisticsPatternRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPattern::class);

        $validated = $request->validated();

        $pattern = DB::transaction(function () use ($validated, $request) {
            $pattern = EquipmentLogisticsPattern::create([
                'name' => $validated['name'],
                'target_type' => $validated['target_type'],
                'description' => $validated['description'] ?? null,
                'display_order' => $validated['display_order'] ?? ((int) EquipmentLogisticsPattern::max('display_order') + 10),
                'created_by' => $request->user()?->id,
            ]);

            foreach ($validated['steps'] as $index => $stepData) {
                $pattern->steps()->create([
                    'day_phase' => $stepData['day_phase'],
                    'action_type' => $stepData['action_type'],
                    'title' => $stepData['title'],
                    'from_location_id' => $stepData['from_location_id'] ?? null,
                    'to_location_id' => $stepData['to_location_id'] ?? null,
                    'operator_type' => $stepData['operator_type'],
                    'witness_required' => (bool) ($stepData['witness_required'] ?? false),
                    'notes' => $stepData['notes'] ?? null,
                    'display_order' => $stepData['display_order'] ?? (($index + 1) * 10),
                ]);
            }

            return $pattern->load('steps');
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$pattern->name}」を作成しました。",
                'pattern' => $pattern,
            ]);
        }

        return redirect()->route('logistics.index', ['tab' => 'patterns'])
            ->with('status', "搬出入パターン「{$pattern->name}」を作成しました。");
    }

    /**
     * 搬出入パターン（テンプレート）の更新
     */
    public function update(UpdateEquipmentLogisticsPatternRequest $request, EquipmentLogisticsPattern $pattern): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $pattern);

        $validated = $request->validated();

        DB::transaction(function () use ($pattern, $validated) {
            $pattern->update([
                'name' => $validated['name'],
                'target_type' => $validated['target_type'],
                'description' => $validated['description'] ?? null,
                'display_order' => $validated['display_order'] ?? $pattern->display_order,
            ]);

            $pattern->steps()->delete();

            foreach ($validated['steps'] as $index => $stepData) {
                $pattern->steps()->create([
                    'day_phase' => $stepData['day_phase'],
                    'action_type' => $stepData['action_type'],
                    'title' => $stepData['title'],
                    'from_location_id' => $stepData['from_location_id'] ?? null,
                    'to_location_id' => $stepData['to_location_id'] ?? null,
                    'operator_type' => $stepData['operator_type'],
                    'witness_required' => (bool) ($stepData['witness_required'] ?? false),
                    'notes' => $stepData['notes'] ?? null,
                    'display_order' => $stepData['display_order'] ?? (($index + 1) * 10),
                ]);
            }
        });

        $pattern->load('steps');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$pattern->name}」を更新しました。",
                'pattern' => $pattern,
            ]);
        }

        return redirect()->route('logistics.index', ['tab' => 'patterns'])
            ->with('status', "搬出入パターン「{$pattern->name}」を更新しました。");
    }

    /**
     * 搬出入パターンの削除
     */
    public function destroy(Request $request, EquipmentLogisticsPattern $pattern): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $pattern);

        $name = $pattern->name;
        $pattern->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('logistics.index', ['tab' => 'patterns'])
            ->with('status', "搬出入パターン「{$name}」を削除しました。");
    }

    /**
     * 資材に登録済みのステップ群を新しい搬出入パターンとして保存
     */
    public function saveFromEquipment(Request $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPattern::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'target_type' => ['nullable', 'string', Rule::in(array_keys(EquipmentLogisticsPattern::TARGET_TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($equipment->plans()->count() === 0) {
            return back()->withErrors(['name' => '保存する搬出入ステップが登録されていません。']);
        }

        $pattern = $this->logisticsService->saveEquipmentPlansAsPattern(
            $equipment,
            $validated,
            $request->user()?->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "現在のステップ構成を新しいパターン「{$pattern->name}」として保存しました。",
                'pattern' => $pattern,
            ]);
        }

        return back()->with('status', "現在のステップ構成を新しいパターン「{$pattern->name}」として保存しました。");
    }
}
