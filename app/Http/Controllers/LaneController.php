<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLanesRequest;
use App\Http\Requests\StoreLaneRequest;
use App\Http\Requests\UpdateLaneRequest;
use App\Models\Lane;
use App\Services\LaneService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaneController extends Controller
{
    public function __construct(
        protected LaneService $laneService,
    ) {}

    /**
     * 担当班マスター一覧画面の表示
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Lane::class);

        $viewData = $this->laneService->getIndexViewData();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'lanes' => $viewData['lanes'],
            ]);
        }

        return view('lanes.index', $viewData);
    }

    /**
     * 担当班マスターの新規登録処理
     */
    public function store(StoreLaneRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Lane::class);

        $lane = $this->laneService->createLane($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班マスター「{$lane->name}」を登録しました。",
                'lane' => $lane->presenter()->toModalPayload(),
            ]);
        }

        return redirect()->route('lanes.index')
            ->with('status', "担当班マスター「{$lane->name}」を登録しました。");
    }

    /**
     * 担当班マスターの更新処理
     */
    public function update(UpdateLaneRequest $request, Lane $lane): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $lane);

        $updated = $this->laneService->updateLane($lane, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班マスター「{$updated->name}」を更新しました。",
                'lane' => $updated->presenter()->toModalPayload(),
            ]);
        }

        return redirect()->route('lanes.index')
            ->with('status', "担当班マスター「{$updated->name}」を更新しました。");
    }

    /**
     * 担当班マスターの削除処理
     */
    public function destroy(Request $request, Lane $lane): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $lane);

        $name = $lane->name;

        try {
            $this->laneService->deleteLane($lane);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "担当班マスター「{$name}」を削除しました。",
                ]);
            }

            return redirect()->route('lanes.index')
                ->with('status', "担当班マスター「{$name}」を削除しました。");
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('lanes.index')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 担当班マスターの表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderLanesRequest $request): JsonResponse
    {
        $this->authorize('update', Lane::class);

        $this->laneService->reorderLanes($request->validated('lane_ids'));

        return response()->json([
            'success' => true,
            'message' => '担当班マスターの表示順を更新しました。',
        ]);
    }
}
