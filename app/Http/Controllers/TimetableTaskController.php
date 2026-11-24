<?php

namespace App\Http\Controllers;

use App\Actions\TaskAssignment\AssignStaffAction;
use App\Actions\Timetable\ResolveActiveTimetableRedirectAction;
use App\Actions\TimetableTask\CreateCommonTimetableTasksAction;
use App\Http\Requests\StoreCommonTimetableTaskRequest;
use App\Http\Requests\StoreTimetableTaskRequest;
use App\Http\Requests\UpdateTimetableTaskRequest;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\TimetableTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TimetableTaskController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService,
    ) {}

    /**
     * タスク一覧
     */
    public function index(Request $request, ResolveActiveTimetableRedirectAction $redirectAction): View|RedirectResponse
    {
        if ($request->filled('timetable_id')) {
            $timetable = $redirectAction->resolveRequestedOrActive($request->integer('timetable_id'));
            if ($timetable !== null) {
                return $this->indexByTimetable($timetable);
            }
        }

        return $redirectAction->execute($request, 'timetables.tasks.index');
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
     * 作業タスク新規登録処理（JSON/タイムライン即時アサイン対応）
     */
    public function store(StoreTimetableTaskRequest $request, AssignStaffAction $assignStaffAction): RedirectResponse|JsonResponse
    {
        $this->authorize('create', TimetableTask::class);

        $validated = $request->validated();
        $assignStaffId = isset($validated['assign_staff_id']) ? (int) $validated['assign_staff_id'] : null;
        $forceAssign = (bool) ($validated['force_assign'] ?? false);

        DB::beginTransaction();
        try {
            $task = $this->taskService->createTask($validated);
            $task->load(['timetableLane.lane', 'timetableLane.leadStaff', 'assignments']);

            if ($assignStaffId !== null && $assignStaffId > 0) {
                $alreadyAssigned = $task->assignments->contains('staff_id', $assignStaffId);
                if (! $alreadyAssigned) {
                    $assignResult = $assignStaffAction->execute($task, $assignStaffId, null, $forceAssign);
                    if (! $assignResult['success'] && ($assignResult['has_conflict'] ?? false)) {
                        DB::rollBack();

                        if ($request->wantsJson()) {
                            $conflictingTask = $assignResult['conflicting_task'] ?? null;

                            return response()->json([
                                'success' => false,
                                'has_conflict' => true,
                                'message' => $assignResult['message'],
                                'conflicting_task' => $conflictingTask ? [
                                    'id' => $conflictingTask->id,
                                    'title' => $conflictingTask->title,
                                    'lane' => $conflictingTask->presenter()->laneNameLabel(),
                                    'start_time' => substr((string) $conflictingTask->start_time, 0, 5),
                                    'end_time' => substr((string) $conflictingTask->end_time, 0, 5),
                                ] : null,
                            ], 409);
                        }

                        return back()->with('conflict_warning', $assignResult['message'])->withInput();
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($request->wantsJson()) {
            $task->load(['timetableLane.lane', 'timetableLane.leadStaff', 'assignments.staff']);

            return response()->json([
                'success' => true,
                'message' => "作業タスク「{$task->title}」を登録しました。",
                'task' => $task->presenter()->toModalPayload(),
            ], 201);
        }

        return redirect()->route('timetables.tasks.index', $task->timetable_id)
            ->with('success', "作業タスク「{$task->title}」を登録しました。");
    }

    /**
     * 複数班・全担当班への共通タスク一括登録処理
     */
    public function storeCommon(
        StoreCommonTimetableTaskRequest $request,
        CreateCommonTimetableTasksAction $action
    ): RedirectResponse {
        $this->authorize('create', TimetableTask::class);

        $dto = $request->toDto();
        $createdTasks = $action->execute($dto);
        $count = $createdTasks->count();

        return redirect()->route('timetables.tasks.index', $dto->timetableId)
            ->with('success', "共通タスク「{$dto->title}」を {$count} 班に一括登録しました。");
    }

    /**
     * 作業タスク個別更新処理（モーダル編集・ガントドラッグ更新対応）
     */
    public function update(UpdateTimetableTaskRequest $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validated();
        $updatedTask = $this->taskService->updateTask($task, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "作業タスク「{$updatedTask->title}」を更新しました。",
                'task' => $updatedTask->presenter()->toModalPayload(),
            ]);
        }

        return redirect()->route('timetables.tasks.index', $task->timetable_id)
            ->with('success', "作業タスク「{$task->title}」を更新しました。");
    }

    /**
     * 作業タスク削除処理
     */
    public function destroy(Request $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $task);

        $timetableId = $task->timetable_id;
        $title = $task->title;

        $this->taskService->deleteTask($task);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "作業タスク「{$title}」を削除しました。",
            ]);
        }

        return redirect()->route('timetables.tasks.index', $timetableId)
            ->with('success', "作業タスク「{$title}」を削除しました。");
    }
}
