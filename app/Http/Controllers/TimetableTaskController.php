<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\StoreTimetableTaskRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Requests\UpdateTimetableTaskRequest;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableTaskController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService
    ) {}

    /**
     * タスク一覧（親タイムテーブル一覧へリダイレクト）
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('timetables.index');
    }

    /**
     * 作業タスク新規作成画面の表示
     */
    public function create(Request $request): View
    {
        $this->authorize('create', TimetableTask::class);

        $timetableId = $request->integer('timetable_id');
        $timetable = Timetable::findOrFail($timetableId);

        $timetableLanes = $timetable->lanes()->with('leadStaff')->get();
        if ($timetableLanes->isEmpty()) {
            $timetable->createDefaultLanes();
            $timetableLanes = $timetable->lanes()->with('leadStaff')->get();
        }

        $selectedLaneId = $request->integer('lane_id') ?: null;
        $selectedLane = $selectedLaneId ? $timetableLanes->firstWhere('id', $selectedLaneId) : null;

        $suggestedStartTime = '08:00';
        if ($selectedLane) {
            $latestTask = $selectedLane->tasks()->latest('end_time')->first();
            if ($latestTask) {
                $suggestedStartTime = substr($latestTask->end_time, 0, 5);
            }
        }

        return view('timetable_tasks.create', [
            'timetable' => $timetable,
            'timetableLanes' => $timetableLanes,
            'selectedLane' => $selectedLane,
            'suggestedStartTime' => $suggestedStartTime,
            'lanes' => TimetableTask::LANES,
            'statuses' => TimetableTask::STATUSES,
        ]);
    }

    /**
     * 作業タスク新規登録処理
     */
    public function store(StoreTimetableTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', TimetableTask::class);

        $data = $request->validated();
        $autoAssignLead = $request->boolean('auto_assign_lead');

        $task = $this->taskService->createTask($data);

        // 主担当スタッフの自動アサイン（班リーダー）
        if ($autoAssignLead && $task->lane_id) {
            $lane = Lane::find($task->lane_id);
            if ($lane && $lane->lead_staff_id) {
                $task->assignments()->firstOrCreate([
                    'staff_id' => $lane->lead_staff_id,
                ], [
                    'role_description' => '班リーダー',
                    'attendance_status' => 'present',
                ]);
            }
        }

        return redirect()->route('timetables.show', $task->timetable_id)
            ->with('success', "作業タスク「{$task->title}」を登録しました。");
    }

    /**
     * 作業タスク詳細画面（スタッフアサイン・出欠管理）の表示
     */
    public function show(TimetableTask $task): View
    {
        $this->authorize('view', $task);

        $detailedTask = $this->taskService->getTaskDetails($task->id) ?? $task;
        $activeStaff = $this->taskService->getActiveStaff();

        return view('timetable_tasks.show', [
            'task' => $detailedTask,
            'activeStaff' => $activeStaff,
            'attendanceStatuses' => TaskStaffAssignment::ATTENDANCE_STATUSES,
        ]);
    }

    /**
     * 作業タスク編集画面の表示
     */
    public function edit(TimetableTask $task): View
    {
        $this->authorize('update', $task);

        $timetableLanes = $task->timetable->lanes()->with('leadStaff')->get();

        return view('timetable_tasks.edit', [
            'task' => $task,
            'timetableLanes' => $timetableLanes,
            'lanes' => TimetableTask::LANES,
            'statuses' => TimetableTask::STATUSES,
        ]);
    }

    /**
     * 作業タスク更新処理
     */
    public function update(UpdateTimetableTaskRequest $request, TimetableTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->taskService->updateTask($task, $request->validated());

        return redirect()->route('timetables.show', $task->timetable_id)
            ->with('success', "作業タスク「{$task->title}」を更新しました。");
    }

    /**
     * 作業タスク削除処理
     */
    public function destroy(TimetableTask $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $timetableId = $task->timetable_id;
        $title = $task->title;

        $this->taskService->deleteTask($task);

        return redirect()->route('timetables.show', $timetableId)
            ->with('success', "作業タスク「{$title}」を削除しました。");
    }

    /**
     * スタッフのタスク割り当て処理
     */
    public function assignStaff(AssignStaffRequest $request, TimetableTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->taskService->assignStaff(
            $task,
            (int) $request->validated('staff_id'),
            $request->validated('role_description')
        );

        return back()->with('success', 'スタッフをタスクに割り当てました。');
    }

    /**
     * スタッフの割り当て解除処理
     */
    public function removeStaff(TimetableTask $task, Staff $staff): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->taskService->removeStaff($task, $staff->id);

        return back()->with('success', "スタッフ「{$staff->name}」の割り当てを解除しました。");
    }

    /**
     * 出欠ステータスの更新処理
     */
    public function updateAttendance(UpdateAttendanceRequest $request, TimetableTask $task, TaskStaffAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->taskService->updateAttendanceStatus($assignment->id, $request->validated('attendance_status'));

        return back()->with('success', '出欠ステータスを更新しました。');
    }
}
