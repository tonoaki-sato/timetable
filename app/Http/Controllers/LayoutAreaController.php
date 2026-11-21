<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLayoutAreasRequest;
use App\Http\Requests\StoreLayoutAreaRequest;
use App\Http\Requests\UpdateLayoutAreaRequest;
use App\Models\LayoutArea;
use App\Services\LayoutAreaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LayoutAreaController extends Controller
{
    public function __construct(
        protected LayoutAreaService $layoutAreaService,
    ) {}

    /**
     * 会場エリアマスター一覧画面の表示
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', LayoutArea::class);

        $viewData = $this->layoutAreaService->getIndexViewData();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'layout_areas' => $viewData['layoutAreas'],
            ]);
        }

        return view('layout_areas.index', $viewData);
    }

    /**
     * 会場エリアの新規登録処理
     */
    public function store(StoreLayoutAreaRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', LayoutArea::class);

        $layoutArea = $this->layoutAreaService->createLayoutArea($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "会場エリア「{$layoutArea->name}」を登録しました。",
                'layout_area' => $layoutArea,
            ]);
        }

        return redirect()->route('layout-areas.index')
            ->with('status', "会場エリア「{$layoutArea->name}」を登録しました。");
    }

    /**
     * 会場エリアの更新処理
     */
    public function update(UpdateLayoutAreaRequest $request, LayoutArea $layoutArea): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $layoutArea);

        $updated = $this->layoutAreaService->updateLayoutArea($layoutArea, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "会場エリア「{$updated->name}」を更新しました。",
                'layout_area' => $updated,
            ]);
        }

        return redirect()->route('layout-areas.index')
            ->with('status', "会場エリア「{$updated->name}」を更新しました。");
    }

    /**
     * 会場エリアの削除処理
     */
    public function destroy(Request $request, LayoutArea $layoutArea): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $layoutArea);

        $name = $layoutArea->name;

        try {
            $this->layoutAreaService->deleteLayoutArea($layoutArea);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "会場エリア「{$name}」を削除しました。",
                ]);
            }

            return redirect()->route('layout-areas.index')
                ->with('status', "会場エリア「{$name}」を削除しました。");
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('layout-areas.index')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 会場エリアの表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderLayoutAreasRequest $request): JsonResponse
    {
        $this->authorize('update', LayoutArea::class);

        $this->layoutAreaService->reorderLayoutAreas($request->validated('layout_area_ids'));

        return response()->json([
            'success' => true,
            'message' => '会場エリアの表示順を更新しました。',
        ]);
    }
}
