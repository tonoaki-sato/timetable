<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffTimelineController extends Controller
{
    public function __construct(
        protected TimetableGridService $gridService
    ) {}

    /**
     * スタッフ別リソースタイムライン（ガントチャート）の表示
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        $activeStaff = Staff::active()->orderBy('affiliation')->orderBy('name')->get();
        $staffIds = $activeStaff->pluck('id')->all();

        // 当該運行日の全タスクとアサイン情報を取得
        $tasks = TimetableTask::with(['laneRelation', 'assignments.staff'])
            ->where('timetable_id', $timetable->id)
            ->orderBy('start_time')
            ->get();

        // スタッフIDをキーとするタスクアサイン一覧マップ
        $assignmentsByStaff = [];
        foreach ($staffIds as $sId) {
            $assignmentsByStaff[$sId] = collect();
        }

        foreach ($tasks as $task) {
            foreach ($task->assignments as $assignment) {
                if (isset($assignmentsByStaff[$assignment->staff_id])) {
                    $assignmentsByStaff[$assignment->staff_id]->push([
                        'task' => $task,
                        'assignment' => $assignment,
                    ]);
                }
            }
        }

        // 30分刻みの時間スロット一覧を生成
        $timeSlots = $this->gridService->generateTimeSlots($timetable);

        // スタッフ × 時間スロットのグリッドデータを算出
        $timelineGrid = $this->gridService->buildTimelineGrid($timeSlots, $activeStaff, $assignmentsByStaff);

        // サマリー集計
        $totalStaffCount = $activeStaff->count();
        $assignedStaffCount = collect($assignmentsByStaff)->filter(fn ($items) => $items->isNotEmpty())->count();
        $unassignedStaffCount = $totalStaffCount - $assignedStaffCount;
        $checkedInCount = $activeStaff->filter(fn (Staff $s) => $s->is_checked_in)->count();

        // 不足タスク一覧（空き枠からのクイック配置用）
        $tasksWithShortage = $tasks->filter(fn (TimetableTask $t) => $t->shortage_count > 0)->map(function (TimetableTask $t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'lane' => $t->lane ?: ($t->laneRelation?->name ?? '担当班未設定'),
                'start_time' => substr($t->start_time, 0, 5),
                'end_time' => substr($t->end_time, 0, 5),
                'shortage_count' => $t->shortage_count,
                'assigned_staff_ids' => $t->assignments->pluck('staff_id')->all(),
            ];
        })->values();

        // 当該タイムテーブルで班長を務めるスタッフID一覧および担当班名
        $leadLanes = $timetable->lanes()->whereNotNull('lead_staff_id')->get(['name', 'lead_staff_id']);
        $leadStaffIds = $leadLanes->pluck('lead_staff_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $laneNamesByLeadStaff = $leadLanes->groupBy('lead_staff_id')
            ->map(fn ($lanes) => $lanes->pluck('name')->join('・'))
            ->all();

        return view('timetables.staff_timeline', [
            'timetable' => $timetable,
            'activeStaff' => $activeStaff,
            'timeSlots' => $timeSlots,
            'timelineGrid' => $timelineGrid,
            'assignmentsByStaff' => $assignmentsByStaff,
            'tasksWithShortage' => $tasksWithShortage,
            'totalStaffCount' => $totalStaffCount,
            'assignedStaffCount' => $assignedStaffCount,
            'unassignedStaffCount' => $unassignedStaffCount,
            'checkedInCount' => $checkedInCount,
            'leadStaffIds' => $leadStaffIds,
            'laneNamesByLeadStaff' => $laneNamesByLeadStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }
}
