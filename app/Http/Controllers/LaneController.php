<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaneRequest;
use App\Http\Requests\UpdateLaneLeadStaffRequest;
use App\Http\Requests\UpdateLaneRequest;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Services\TimetableTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaneController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService
    ) {}

    /**
     * 担当班管理の対象タイムテーブル選択画面の表示
     */
    public function select(): View|RedirectResponse
    {
        $this->authorize('viewAny', Lane::class);

        $timetables = Timetable::with(['lanes.leadStaff'])
            ->orderBy('target_date', 'asc')
            ->get();

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

        $lanes = $timetable->lanes()->with(['leadStaff', 'fixedStaff', 'tasks', 'laneRequests.task', 'laneRequests.requestedByStaff'])->ordered()->get();
        $activeStaff = Staff::active()->orderBy('affiliation')->orderBy('name')->get();

        return view('lanes.index', [
            'timetable' => $timetable,
            'lanes' => $lanes,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }

    /**
     * 新規レーン登録処理
     */
    public function store(StoreLaneRequest $request, Timetable $timetable): RedirectResponse
    {
        $data = $request->validated();
        $fixedStaffIds = $data['fixed_staff_ids'] ?? [];
        unset($data['fixed_staff_ids']);

        if (! isset($data['display_order']) || $data['display_order'] === null) {
            $maxOrder = $timetable->lanes()->max('display_order') ?? 0;
            $data['display_order'] = $maxOrder + 10;
        }

        $lane = $timetable->lanes()->create($data);

        if (! empty($fixedStaffIds)) {
            $this->taskService->syncLaneFixedStaffToTasks($lane, $fixedStaffIds);
        }

        if ($request->input('redirect_to') === 'matrix') {
            return redirect()->route('timetable.index', ['timetable_id' => $timetable->id])
                ->with('success', "新しい担当班（レーン）「{$lane->name}」を追加しました。");
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$lane->name}」を追加しました。");
    }

    /**
     * レーン更新処理（班長・固定スタッフの設定・変更、主要班・表示状態切り替えを含む）
     */
    public function update(UpdateLaneRequest $request, Timetable $timetable, Lane $lane): JsonResponse|RedirectResponse
    {
        $oldLeadStaffId = $lane->lead_staff_id ? (int) $lane->lead_staff_id : null;
        $validated = $request->validated();
        $hasFixedStaffInput = array_key_exists('fixed_staff_ids', $validated) || $request->boolean('sync_fixed_staff');
        $fixedStaffIds = $validated['fixed_staff_ids'] ?? [];
        unset($validated['fixed_staff_ids']);

        $lane->update($validated);

        if ($lane->lead_staff_id) {
            $this->taskService->syncLaneLeadToTasks($lane, $oldLeadStaffId);
        }

        if ($hasFixedStaffInput) {
            $this->taskService->syncLaneFixedStaffToTasks($lane, $fixedStaffIds);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班（レーン）「{$lane->name}」を更新しました。",
                'lane' => $lane->fresh(['leadStaff', 'fixedStaff']),
            ]);
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$lane->name}」を更新しました。");
    }

    /**
     * 班長（主担当スタッフ）のクイック更新・解除（既存タスクへの自動アサイン同期対応）
     */
    public function updateLeadStaff(UpdateLaneLeadStaffRequest $request, Timetable $timetable, Lane $lane): RedirectResponse
    {
        $oldLeadStaffId = $lane->lead_staff_id ? (int) $lane->lead_staff_id : null;

        $lane->update([
            'lead_staff_id' => $request->validated('lead_staff_id'),
        ]);

        $syncedCount = 0;
        if ($lane->lead_staff_id) {
            $syncedCount = $this->taskService->syncLaneLeadToTasks($lane, $oldLeadStaffId);
        }

        $leadName = $lane->leadStaff ? $lane->leadStaff->name : '未設定';
        $message = $syncedCount > 0
            ? "レーン「{$lane->name}」の班長を「{$leadName}」に更新し、{$syncedCount}件のタスクに反映しました。"
            : "レーン「{$lane->name}」の班長を「{$leadName}」に更新しました。";

        return back()->with('success', $message);
    }

    /**
     * レーンの表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(Request $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', Lane::class);

        $validated = $request->validate([
            'lane_ids' => ['required', 'array'],
            'lane_ids.*' => ['integer', 'exists:lanes,id'],
        ]);

        DB::transaction(function () use ($validated, $timetable) {
            $order = 10;
            foreach ($validated['lane_ids'] as $laneId) {
                $timetable->lanes()->where('id', $laneId)->update([
                    'display_order' => $order,
                ]);
                $order += 10;
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'レーンの表示順を更新しました。',
        ]);
    }

    /**
     * レーン削除処理（タスクが存在する場合は別レーン移行または確認後削除）
     */
    public function destroy(Request $request, Timetable $timetable, Lane $lane): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $lane);

        $taskCount = $lane->tasks()->count();

        // タスク移行先が指定されている場合
        if ($request->filled('migrate_to_lane_id')) {
            $migrateToLaneId = (int) $request->input('migrate_to_lane_id');
            $targetLane = $timetable->lanes()->where('id', $migrateToLaneId)->first();

            if (! $targetLane || $targetLane->id === $lane->id) {
                return back()->with('error', '有効な移行先レーンを選択してください。');
            }

            DB::transaction(function () use ($lane, $targetLane) {
                $lane->tasks()->update(['lane_id' => $targetLane->id]);
                $lane->delete();
            });

            return redirect()->route('timetables.lanes.index', $timetable)
                ->with('success', "担当班「{$lane->name}」の作業タスク {$taskCount} 件を「{$targetLane->name}」に引き継ぎ、レーンを削除しました。");
        }

        // タスクごと削除が明示的に指定されている場合
        if ($request->boolean('cascade_delete_tasks')) {
            DB::transaction(function () use ($lane) {
                $lane->tasks()->delete();
                $lane->delete();
            });

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
        $lane->delete();

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$name}」を削除しました。");
    }
}
