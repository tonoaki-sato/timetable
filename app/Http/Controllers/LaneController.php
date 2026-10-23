<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaneRequest;
use App\Http\Requests\UpdateLaneLeadStaffRequest;
use App\Http\Requests\UpdateLaneRequest;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LaneController extends Controller
{
    /**
     * レーン一覧・管理画面の表示
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('viewAny', Lane::class);

        $lanes = $timetable->lanes()->with(['leadStaff', 'tasks'])->get();
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
        $lane = $timetable->lanes()->create($request->validated());

        if ($request->input('redirect_to') === 'matrix') {
            return redirect()->route('timetable.index', ['timetable_id' => $timetable->id])
                ->with('success', "新しい担当班（レーン）「{$lane->name}」を追加しました。");
        }

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$lane->name}」を追加しました。");

    }

    /**
     * レーン更新処理（主担当の後付け・変更を含む）
     */
    public function update(UpdateLaneRequest $request, Timetable $timetable, Lane $lane): RedirectResponse
    {
        $lane->update($request->validated());

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
     * レーン削除処理（タスクが存在する場合はブロック）
     */
    public function destroy(Timetable $timetable, Lane $lane): RedirectResponse
    {
        $this->authorize('delete', $lane);

        if ($lane->tasks()->exists()) {
            $taskCount = $lane->tasks()->count();

            return back()->with('error', "レーン「{$lane->name}」には {$taskCount} 件の作業タスクが紐づいているため削除できません。先にタスクを移動または削除してください。");
        }

        $name = $lane->name;
        $lane->delete();

        return redirect()->route('timetables.lanes.index', $timetable)
            ->with('success', "担当班（レーン）「{$name}」を削除しました。");
    }
}
