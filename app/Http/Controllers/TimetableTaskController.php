<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimetableTaskRequest;
use App\Http\Requests\UpdateTimetableTaskRequest;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
use App\Services\StaffAllocationService;
use App\Services\TimetableService;
use App\Services\TimetableTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableTaskController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService,
        protected StaffAllocationService $allocationService,
        protected TimetableService $timetableService
    ) {}

    /**
     * タスク一覧
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('timetable_id')) {
            $timetable = $this->timetableService->findOrFail($request->integer('timetable_id'));

            return $this->indexByTimetable($timetable);
        }

        $currentTimetable = $this->timetableService->findFirstActiveOrEarliest();

        if ($currentTimetable) {
            return redirect()->route('timetables.tasks.index', $currentTimetable);
        }

        return redirect()->route('timetables.index');
    }

    /**
     * 運行日別 作業タスク一覧画面の表示（タスク管理専用画面）
     */
    public function indexByTimetable(Timetable $timetable): View
    {
        $this->authorize('viewAny', TimetableTask::class);

        return view('timetable_tasks.index', $this->taskService->getTasksIndexViewData($timetable));
    }

    /**
     * 作業タスク新規登録処理（モーダル・一括登録双方対応）
     */
    public function store(StoreTimetableTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', TimetableTask::class);

        $validated = $request->validated();
        $timetableId = (int) $validated['timetable_id'];
        $laneId = (int) $validated['lane_id'];
        $tasksData = $validated['tasks'];
        $autoAssignLead = $request->has('auto_assign_lead')
            ? $request->boolean('auto_assign_lead')
            : true;

        $createdTasks = $this->taskService->createBulkTasks(
            $timetableId,
            $laneId,
            $tasksData,
            $autoAssignLead
        );

        $count = $createdTasks->count();
        if ($count === 1) {
            $firstTask = $createdTasks->first();
            $message = "作業タスク「{$firstTask->title}」を登録しました。";
        } else {
            $lane = $this->taskService->findLaneById($laneId);
            $laneName = $lane?->name ?? '選択した担当班';
            $message = "「{$laneName}」に {$count} 件の作業タスクを登録しました。";
        }

        return redirect()->route('timetables.tasks.index', $timetableId)
            ->with('success', $message);
    }

    /**
     * 作業タスク個別更新処理（モーダル編集対応）
     */
    public function update(UpdateTimetableTaskRequest $request, TimetableTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validated();
        $this->taskService->updateTask($task, $validated);

        return redirect()->route('timetables.tasks.index', $task->timetable_id)
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

        return redirect()->route('timetables.tasks.index', $timetableId)
            ->with('success', "作業タスク「{$title}」を削除しました。");
    }
}
