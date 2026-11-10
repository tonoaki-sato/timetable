<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyLaneRequest;
use App\Http\Requests\ReorderLanesRequest;
use App\Http\Requests\StoreLaneRequest;
use App\Http\Requests\UpdateLaneRequest;
use App\Models\Lane;
use App\Models\Timetable;
use App\Services\LaneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LaneController extends Controller
{
    public function __construct(
        protected LaneService $laneService
    ) {}

    /**
     * 担当班管理の対象タイムテーブル選択画面の表示
     */
    public function select(): View|RedirectResponse
    {
        $this->authorize('viewAny', Lane::class);

        $timetables = $this->laneService->getTimetablesForSelect();

        if ($timetables->isEmpty()) {
            return redirect()->route('timetables.index')
                ->with('info', '担当班を編成する前に、運行日を登録してください。');
        }

        if ($timetables->count() === 1) {
            return redirect()->route('timetables.lanes.index', $timetables->first());
        }

        return view('lanes.select', [
            'timetables' => $timetables,
        ]);
    }

    /**
     * レーン一覧・管理画面の表示
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('viewAny', Lane::class);

        $lanes = $this->laneService->getLanesForTimetable($timetable);

        return view('lanes.index', [
            'timetable' => $timetable,
            'lanes' => $lanes,
        ]);
    }

    /**
     * 新規レーン作成画面の表示
     */
    public function create(Timetable $timetable): View
    {
        $this->authorize('create', Lane::class);

        $activeStaff = $this->laneService->getActiveStaffOrderedByAffiliation();

        return view('lanes.create', [
            'timetable' => $timetable,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }

    /**
     * 新規レーン登録処理
     */
    public function store(StoreLaneRequest $request, Timetable $timetable): RedirectResponse
    {
        $lane = $this->laneService->createLane($timetable, $request->validated());

        if ($request->input('redirect_to') === 'matrix') {
            return redirect()->route('timetable.index', ['timetable_id' => $timetable->id])
                ->with('success', "新しい担当班「{$lane->name}」を追加しました。");
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班「{$lane->name}」を追加しました。");
    }

    /**
     * レーン詳細画面の表示
     */
    public function show(Timetable $timetable, Lane $lane): View
    {
        $this->authorize('view', $lane);

        $laneWithDetails = $this->laneService->findWithDetails($timetable, $lane->id) ?? $lane;

        return view('lanes.show', [
            'timetable' => $timetable,
            'lane' => $laneWithDetails,
        ]);
    }

    /**
     * レーン編集画面の表示
     */
    public function edit(Timetable $timetable, Lane $lane): View
    {
        $this->authorize('update', $lane);

        $this->laneService->loadEditRelations($lane);
        $activeStaff = $this->laneService->getActiveStaffOrderedByAffiliation();

        return view('lanes.edit', [
            'timetable' => $timetable,
            'lane' => $lane,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }

    /**
     * レーン更新処理（班長・固定スタッフの設定・変更、主要班・表示状態切り替えを含む）
     */
    public function update(UpdateLaneRequest $request, Timetable $timetable, Lane $lane): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $hasFixedStaffInput = array_key_exists('fixed_staff_ids', $validated) || $request->boolean('sync_fixed_staff');

        $updatedLane = $this->laneService->updateLane($lane, $validated, $hasFixedStaffInput);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班「{$lane->name}」を更新しました。",
                'lane' => $updatedLane,
            ]);
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班「{$lane->name}」を更新しました。");
    }

    /**
     * レーンの表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderLanesRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', Lane::class);

        $this->laneService->reorderLanes($timetable, $request->validated('lane_ids'));

        return response()->json([
            'success' => true,
            'message' => 'レーンの表示順を更新しました。',
        ]);
    }

    /**
     * レーン削除処理（タスクが存在する場合は別レーン移行または確認後削除）
     */
    public function destroy(DestroyLaneRequest $request, Timetable $timetable, Lane $lane): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $lane);

        $taskCount = $this->laneService->countLaneTasks($lane);

        // タスク移行先が指定されている場合
        if ($request->filled('migrate_to_lane_id')) {
            $migrateToLaneId = (int) $request->validated('migrate_to_lane_id');
            $targetLane = $this->laneService->findTargetLaneForMigration($timetable, $migrateToLaneId);

            if (! $targetLane || $targetLane->id === $lane->id) {
                return back()->with('error', '有効な移行先レーンを選択してください。');
            }

            $this->laneService->migrateTasksAndDeleteLane($lane, $targetLane);

            return redirect()->route('timetables.lanes.index', $timetable)
                ->with('success', "担当班「{$lane->name}」の作業タスク {$taskCount} 件を「{$targetLane->name}」に引き継ぎ、レーンを削除しました。");
        }

        // タスクごと削除が明示的に指定されている場合
        if ($request->boolean('cascade_delete_tasks')) {
            $this->laneService->cascadeDeleteTasksAndLane($lane);

            return redirect()->route('timetables.lanes.index', $timetable)
                ->with('success', "担当班「{$lane->name}」および紐づく作業タスク {$taskCount} 件をすべて削除しました。");
        }

        // タスクが存在し、移行先もカスケードも指定されていない場合はブロック
        if ($taskCount > 0) {
            $msg = "レーン「{$lane->name}」には {$taskCount} 件の作業タスクが紐づいているため削除できません。引き継ぎ先レーンを選択するか、タスクを含めて削除してください。";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'task_count' => $taskCount], 422);
            }

            return back()->with('error', $msg);
        }

        $name = $lane->name;
        $this->laneService->deleteLane($lane);

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班「{$name}」を削除しました。");
    }
}
