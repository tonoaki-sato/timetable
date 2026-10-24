<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaneRequest;
use App\Http\Requests\UpdateLaneLeadStaffRequest;
use App\Http\Requests\UpdateLaneRequest;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaneController extends Controller
{
    /**
     * 担当班管理の対象運行計画選択画面の表示
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

        $lanes = $timetable->lanes()->with(['leadStaff', 'tasks'])->ordered()->get();
        $activeStaff = Staff::active()->orderBy('affiliation')->orderBy('name')->get();
        $allTimetables = Timetable::orderBy('target_date', 'asc')->get();
        $otherTimetables = $allTimetables->where('id', '!=', $timetable->id)->values();

        return view('lanes.index', [
            'timetable' => $timetable,
            'lanes' => $lanes,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
            'allTimetables' => $allTimetables,
            'otherTimetables' => $otherTimetables,
        ]);
    }

    /**
     * 新規レーン登録処理
     */
    public function store(StoreLaneRequest $request, Timetable $timetable): RedirectResponse
    {
        $data = $request->validated();
        if (! isset($data['display_order']) || $data['display_order'] === null) {
            $maxOrder = $timetable->lanes()->max('display_order') ?? 0;
            $data['display_order'] = $maxOrder + 10;
        }

        $lane = $timetable->lanes()->create($data);

        if ($request->input('redirect_to') === 'matrix') {
            return redirect()->route('timetable.index', ['timetable_id' => $timetable->id])
                ->with('success', "新しい担当班（レーン）「{$lane->name}」を追加しました。");
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$lane->name}」を追加しました。");
    }

    /**
     * レーン更新処理（主担当の後付け・変更、主要班・表示状態切り替えを含む）
     */
    public function update(UpdateLaneRequest $request, Timetable $timetable, Lane $lane): JsonResponse|RedirectResponse
    {
        $lane->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "担当班（レーン）「{$lane->name}」を更新しました。",
                'lane' => $lane->fresh(['leadStaff']),
            ]);
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$lane->name}」を更新しました。");
    }

    /**
     * 主担当スタッフのクイック更新・解除
     */
    public function updateLeadStaff(UpdateLaneLeadStaffRequest $request, Timetable $timetable, Lane $lane): RedirectResponse
    {
        $lane->update([
            'lead_staff_id' => $request->validated('lead_staff_id'),
        ]);

        $leadName = $lane->leadStaff ? $lane->leadStaff->name : '未設定';

        return back()->with('success', "レーン「{$lane->name}」の主担当を「{$leadName}」に更新しました。");
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
     * 他の日程からレーン構成を一括コピー
     */
    public function copyFrom(Request $request, Timetable $timetable): RedirectResponse
    {
        $this->authorize('create', Lane::class);

        $validated = $request->validate([
            'source_timetable_id' => ['required', 'integer', 'exists:timetables,id', 'different:timetable'],
        ]);

        $sourceTimetable = Timetable::findOrFail($validated['source_timetable_id']);
        $sourceLanes = $sourceTimetable->lanes()->ordered()->get();

        if ($sourceLanes->isEmpty()) {
            return back()->with('error', "コピー元「{$sourceTimetable->name}」にレーンが設定されていません。");
        }

        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($timetable, $sourceLanes, &$createdCount, &$updatedCount) {
            foreach ($sourceLanes as $src) {
                $existing = $timetable->lanes()->where('name', $src->name)->first();
                if ($existing) {
                    $existing->update([
                        'icon' => $src->icon,
                        'color' => $src->color,
                        'code' => $src->code,
                        'is_core' => $src->is_core,
                        'is_active' => $src->is_active,
                        'description' => $src->description,
                        'display_order' => $src->display_order,
                    ]);
                    $updatedCount++;
                } else {
                    $timetable->lanes()->create([
                        'name' => $src->name,
                        'icon' => $src->icon,
                        'color' => $src->color,
                        'code' => $src->code,
                        'is_core' => $src->is_core,
                        'is_active' => $src->is_active,
                        'lead_staff_id' => null, // 他日程の担当者は引き継がず未設定
                        'description' => $src->description,
                        'display_order' => $src->display_order,
                    ]);
                    $createdCount++;
                }
            }
        });

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "「{$sourceTimetable->name}」からレーン構成をコピーしました。（新規追加: {$createdCount}件、更新: {$updatedCount}件）");
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
