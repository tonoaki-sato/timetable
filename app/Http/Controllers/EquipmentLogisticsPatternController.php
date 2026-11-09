<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveEquipmentPatternRequest;
use App\Http\Requests\StoreEquipmentLogisticsPatternRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPatternRequest;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\Location;
use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentLogisticsPatternController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService,
        protected EquipmentLogisticsPatternRepositoryInterface $patternRepository
    ) {}

    /**
     * 搬出入パターン（テンプレート）一覧画面の表示
     */
    public function index(): View
    {
        $this->authorize('viewAny', EquipmentLogisticsPattern::class);

        $patterns = $this->patternRepository->getAllWithSteps();
        $locations = Location::select('id', 'name')
            ->orderBy('display_order')
            ->get()
            ->unique('name');

        return view('equipment_patterns.index', compact('patterns', 'locations'));
    }

    /**
     * 搬出入パターン（テンプレート）の新規作成
     */
    public function store(StoreEquipmentLogisticsPatternRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPattern::class);

        $validated = $request->validated();
        $steps = $validated['steps'];
        unset($validated['steps']);

        $pattern = $this->patternRepository->createWithSteps(
            $validated,
            $steps,
            $request->user()?->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$pattern->name}」を作成しました。",
                'pattern' => $pattern,
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "搬出入パターン「{$pattern->name}」を作成しました。");
    }

    /**
     * 搬出入パターン（テンプレート）の更新
     */
    public function update(UpdateEquipmentLogisticsPatternRequest $request, EquipmentLogisticsPattern $pattern): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $pattern);

        $validated = $request->validated();
        $steps = $validated['steps'];
        unset($validated['steps']);

        $this->patternRepository->updateWithSteps($pattern, $validated, $steps);
        $pattern->load('steps');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$pattern->name}」を更新しました。",
                'pattern' => $pattern,
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "搬出入パターン「{$pattern->name}」を更新しました。");
    }

    /**
     * 搬出入パターンの削除
     */
    public function destroy(Request $request, EquipmentLogisticsPattern $pattern): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $pattern);

        $name = $pattern->name;
        $this->patternRepository->delete($pattern);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "搬出入パターン「{$name}」を削除しました。");
    }

    /**
     * 資材に登録済みのステップ群を新しい搬出入パターンとして保存
     */
    public function saveFromEquipment(SaveEquipmentPatternRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('create', EquipmentLogisticsPattern::class);

        $validated = $request->validated();

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
