<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateTimetableTaskRequest;
use App\Http\Requests\StoreTimetableTaskRequest;
use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
use App\Services\StaffAllocationService;
use App\Services\TimetableTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableTaskController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected ConflictDetectionService $conflictService,
        protected StaffAllocationService $allocationService
    ) {}

    /**
     * タスク一覧
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('timetable_id')) {
            $timetable = Timetable::findOrFail($request->integer('timetable_id'));

            return $this->indexByTimetable($timetable);
        }

        $currentTimetable = Timetable::where('status', 'active')->orderBy('target_date')->first()
            ?? Timetable::orderBy('target_date')->first();

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

        $timetable->load([
            'lanes' => function ($q) {
                $q->orderBy('display_order')->with([
                    'leadStaff',
                    'tasks' => function ($tQuery) {
                        $tQuery->orderBy('start_time', 'asc')
                            ->orderBy('end_time', 'asc');
                    },
                ]);
            },
        ]);

        return view('timetable_tasks.index', [
            'timetable' => $timetable,
            'lanes' => $timetable->lanes,
            'tasks' => $timetable->lanes->flatMap->tasks,
        ]);
    }

    /**
     * 作業タスク新規作成画面の表示
     */
    public function create(Request $request): View
    {
        $this->authorize('create', TimetableTask::class);

        $timetableId = $request->integer('timetable_id');
        $timetable = Timetable::findOrFail($timetableId);

        $timetableLanes = $timetable->lanes()->with(['leadStaff', 'fixedStaff', 'tasks'])->get();
        if ($timetableLanes->isEmpty()) {
            $timetable->createDefaultLanes();
            $timetableLanes = $timetable->lanes()->with(['leadStaff', 'fixedStaff', 'tasks'])->get();
        }

        $selectedLaneId = $request->integer('lane_id') ?: null;
        $selectedLane = $selectedLaneId
            ? $timetableLanes->firstWhere('id', $selectedLaneId)
            : $timetableLanes->first();

        $suggestedRange = $this->taskService->calculateSuggestedTimeRange(
            $timetableLanes,
            $selectedLane,
            $request->query('start_time')
        );

        return view('timetable_tasks.create', [
            'timetable' => $timetable,
            'timetableLanes' => $timetableLanes,
            'selectedLane' => $selectedLane,
            'suggestedStartTime' => $suggestedRange['suggested_start_time'],
            'suggestedEndTime' => $suggestedRange['suggested_end_time'],
            'laneSuggestedStartTimes' => $suggestedRange['lane_suggested_start_times'],
            'lanes' => $timetableLanes->pluck('name', 'name')->all(),
            'statuses' => TimetableTask::STATUSES,
        ]);
    }

    /**
     * 作業タスク新規登録処理（単一・複数タスク一括登録対応）
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
            $lane = Lane::find($laneId);
            $laneName = $lane?->name ?? '選択した担当班';
            $message = "「{$laneName}」に {$count} 件の作業タスクを一括登録しました。";
        }

        if ($request->input('redirect_to') === 'show') {
            return redirect()->route('timetables.show', $timetableId)
                ->with('success', $message);
        }

        return redirect()->route('timetable.index', ['timetable_id' => $timetableId])
            ->with('success', $message);
    }

    /**
     * 作業タスク詳細画面の表示
     */
    public function show(TimetableTask $task): View
    {
        $this->authorize('view', $task);

        $detailedTask = $this->taskService->getTaskDetails($task->id) ?? $task;

        return view('timetable_tasks.show', [
            'task' => $detailedTask,
        ]);
    }

    /**
     * 班別作業タスク一括編集画面の表示
     */
    public function bulkEdit(Timetable $timetable, Lane $lane): View
    {
        $this->authorize('update', TimetableTask::class);

        if ((int) $lane->timetable_id !== (int) $timetable->id) {
            abort(404);
        }

        $lane->loadMissing([
            'leadStaff',
            'fixedStaff',
            'tasks' => function ($query) {
                $query->orderBy('start_time', 'asc')
                    ->orderBy('end_time', 'asc');
            },
        ]);

        return view('timetable_tasks.bulk_edit', [
            'timetable' => $timetable,
            'lane' => $lane,
            'tasks' => $lane->tasks,
            'statuses' => TimetableTask::STATUSES,
        ]);
    }

    /**
     * 班別作業タスク一括更新処理
     */
    public function bulkUpdate(BulkUpdateTimetableTaskRequest $request, Timetable $timetable, Lane $lane): RedirectResponse
    {
        $this->authorize('update', TimetableTask::class);

        if ((int) $lane->timetable_id !== (int) $timetable->id) {
            abort(404);
        }

        $validated = $request->validated();
        $tasksData = $validated['tasks'];

        $result = $this->taskService->updateBulkTasks($lane, $tasksData);
        $totalCount = $result['updated_count'] + $result['created_count'];

        $message = "「{$lane->name}」の作業タスク（{$totalCount}件）を一括更新しました。";

        return redirect()->route('timetables.tasks.index', $timetable)
            ->with('success', $message);
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

        if (request()->input('redirect_to') === 'show') {
            return redirect()->route('timetables.show', $timetableId)
                ->with('success', "作業タスク「{$title}」を削除しました。");
        }

        return redirect()->route('timetable.index', ['timetable_id' => $timetableId])
            ->with('success', "作業タスク「{$title}」を削除しました。");
    }
}
