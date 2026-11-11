<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentLogisticsPatternRequest;
use App\Http\Requests\UpdateEquipmentLogisticsPatternRequest;
use App\Models\EquipmentLogisticsPattern;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentLogisticsPatternController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 搬出入パターン（テンプレート）一覧画面の表示
     */
    public function index(): View
    {
        $this->authorize('viewAny', EquipmentLogisticsPattern::class);

        return view('equipment_patterns.index', $this->logisticsService->getPatternIndexViewData());
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

        $pattern = $this->logisticsService->createPatternWithSteps(
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

        $pattern = $this->logisticsService->updatePatternWithSteps($pattern, $validated, $steps);

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
        $this->logisticsService->deletePattern($pattern);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "搬出入パターン「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('equipment-patterns.index')
            ->with('status', "搬出入パターン「{$name}」を削除しました。");
    }
}
