<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableAssignmentWorkspaceController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService
    ) {}

    /**
     * 人員アサイン専用ワークスペース画面の表示
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        $timetable->load([
            'lanes' => function ($q) {
                $q->orderBy('display_order')->with('leadStaff');
            },
            'tasks' => function ($q) {
                $q->orderBy('start_time')
                    ->orderBy('end_time')
                    ->with(['laneRelation', 'assignments.staff']);
            },
        ]);

        $tasks = $timetable->tasks;
        $lanes = $timetable->lanes;

        // サマリー集計
        $totalTasks = $tasks->count();
        $satisfiedTasks = $tasks->filter(fn (TimetableTask $t) => $t->is_satisfied)->count();
        $shortageTasks = $totalTasks - $satisfiedTasks;
        $totalRequired = $tasks->sum('required_people_count');
        $totalAssigned = $tasks->sum(fn (TimetableTask $t) => $t->assigned_count);

        // アクティブスタッフ一覧（所属一覧抽出用）
        $activeStaff = Staff::active()->orderBy('affiliation')->orderBy('name')->get();
        $affiliations = $activeStaff->pluck('affiliation')->filter()->unique()->values()->all();

        // 初期選択タスクの決定（リクエスト指定 ＞ 不足タスク先頭 ＞ 全タスク先頭）
        $requestedTaskId = $request->integer('task_id');
        $initialTaskId = null;
        if ($requestedTaskId && $tasks->contains('id', $requestedTaskId)) {
            $initialTaskId = $requestedTaskId;
        } else {
            $initialTaskId = $tasks->firstWhere('is_satisfied', false)?->id ?? $tasks->first()?->id;
        }

        return view('timetables.assignments.index', [
            'timetable' => $timetable,
            'lanes' => $lanes,
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'satisfiedTasks' => $satisfiedTasks,
            'shortageTasks' => $shortageTasks,
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'activeStaff' => $activeStaff,
            'affiliations' => $affiliations,
            'initialTaskId' => $initialTaskId,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }
}

