<?php

namespace App\Http\Controllers;

use App\Actions\Timetable\ResolveActiveTimetableRedirectAction;
use App\Http\Requests\BulkStoreTimetableLanesRequest;
use App\Http\Requests\DestroyTimetableLaneRequest;
use App\Http\Requests\ReorderTimetableLanesRequest;
use App\Http\Requests\StoreTimetableLaneRequest;
use App\Http\Requests\UpdateTimetableLaneRequest;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Services\TimetableLaneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableLaneController extends Controller
{
    public function __construct(
        protected TimetableLaneService $timetableLaneService,
    ) {}

    /**
     * 運行日別担当班編成一覧へのリダイレクト（アクティブまたは直近の運行日へ）
     */
    public function redirect(Request $request, ResolveActiveTimetableRedirectAction $redirectAction): RedirectResponse
    {
        return $redirectAction->execute($request, 'timetables.lanes.index');
    }

    /**
     * 運行日別担当班編成一覧・管理画面の表示
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('viewAny', TimetableLane::class);
        $this->authorize('view', $timetable);

        $timetableLanes = $this->timetableLaneService->getTimetableLanesForTimetable($timetable);
        $availableMasterLanes = $this->timetableLaneService->getAvailableMasterLanes($timetable);
        $activeStaff = $this->timetableLaneService->getActiveStaffOrderedByAffiliation();

        return view('timetable_lanes.index', [
            'timetable' => $timetable,
            'timetableLanes' => $timetableLanes,
            'availableMasterLanes' => $availableMasterLanes,
            'activeStaff' => $activeStaff,
        ]);
    }

    /**
     * 単一の担当班を当該運行日の編成へ追加
     */
    public function store(StoreTimetableLaneRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        $timetableLane = $this->timetableLaneService->createTimetableLane($timetable, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班「{$timetableLane->name}」を編成に追加しました。",
                'timetable_lane' => $timetableLane->presenter()->toModalPayload(),
            ]);
        }

        if ($request->input('redirect_to') === 'matrix') {
            return redirect()->route('timetable.index', ['timetable_id' => $timetable->id])
                ->with('success', "担当班「{$timetableLane->name}」を編成に追加しました。");
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班「{$timetableLane->name}」を編成に追加しました。");
    }

    /**
     * 担当班マスターから複数の担当班を当該運行日へ一括追加
     */
    public function bulkStore(BulkStoreTimetableLanesRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        $created = $this->timetableLaneService->bulkCreateFromMasters($timetable, $request->validated('lane_ids'));
        $count = $created->count();
        $message = $count > 0
            ? "担当班マスターから {$count} 件の担当班を編成に追加しました。"
            : '選択された担当班はすでにこの運行日に編成されています。';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'added_count' => $count,
            ]);
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', $message);
    }

    /**
     * 運行日別担当班編成の更新（班長・固定スタッフ・当日メモ）
     */
    public function update(
        UpdateTimetableLaneRequest $request,
        Timetable $timetable,
        TimetableLane $timetableLane
    ): JsonResponse|RedirectResponse {
        $validated = $request->validated();
        $hasFixedStaffInput = array_key_exists('fixed_staff_ids', $validated) || $request->boolean('sync_fixed_staff');

        $updated = $this->timetableLaneService->updateTimetableLane($timetableLane, $validated, $hasFixedStaffInput);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班「{$updated->name}」の編成を更新しました。",
                'timetable_lane' => $updated->presenter()->toModalPayload(),
            ]);
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班「{$updated->name}」の編成を更新しました。");
    }

    /**
     * 運行日別担当班編成の表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderTimetableLanesRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', TimetableLane::class);

        $this->timetableLaneService->reorderTimetableLanes($timetable, $request->validated('timetable_lane_ids'));

        return response()->json([
            'success' => true,
            'message' => '担当班の表示順を更新しました。',
        ]);
    }

    /**
     * 運行日別担当班編成の解除・削除（タスク移行・カスケード削除対応）
     */
    public function destroy(
        DestroyTimetableLaneRequest $request,
        Timetable $timetable,
        TimetableLane $timetableLane
    ): JsonResponse|RedirectResponse {
        $this->authorize('delete', $timetableLane);

        $migrateToId = $request->filled('migrate_to_timetable_lane_id')
            ? (int) $request->validated('migrate_to_timetable_lane_id')
            : null;
        $result = $this->timetableLaneService->deleteTimetableLaneWithStrategy(
            timetable: $timetable,
            timetableLane: $timetableLane,
            migrateToTimetableLaneId: $migrateToId,
            cascadeDeleteTasks: $request->boolean('cascade_delete_tasks'),
        );

        if ($result['status'] === 'invalid_target') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'task_count' => $result['task_count'],
                ], 422);
            }

            return back()->with('error', $result['message']);
        }

        if ($result['status'] === 'blocked') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'task_count' => $result['task_count'],
                ], 422);
            }

            return back()->with('error', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', $result['message']);
    }
}
