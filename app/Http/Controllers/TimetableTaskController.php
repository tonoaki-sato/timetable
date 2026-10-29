<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\BulkAssignStaffRequest;
use App\Http\Requests\StoreTimetableTaskRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Requests\UpdateTimetableTaskRequest;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\ConflictDetectionService;
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
        protected ConflictDetectionService $conflictService
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

        $timetableLanes = $timetable->lanes()->with(['leadStaff', 'fixedStaff', 'tasks'])->get();
        if ($timetableLanes->isEmpty()) {
            $timetable->createDefaultLanes();
            $timetableLanes = $timetable->lanes()->with(['leadStaff', 'fixedStaff', 'tasks'])->get();
        }

        $selectedLaneId = $request->integer('lane_id') ?: null;
        $selectedLane = $selectedLaneId
            ? $timetableLanes->firstWhere('id', $selectedLaneId)
            : $timetableLanes->first();

        $laneSuggestedStartTimes = [];
        foreach ($timetableLanes as $laneItem) {
            $latest = $laneItem->tasks->sortByDesc('end_time')->first();
            $laneSuggestedStartTimes[$laneItem->id] = $latest ? substr($latest->end_time, 0, 5) : '08:00';
        }

        $requestedStartTime = $request->query('start_time');
        if (is_string($requestedStartTime) && preg_match('/^\d{2}:\d{2}$/', $requestedStartTime)) {
            $suggestedStartTime = $requestedStartTime;
        } elseif ($selectedLane && isset($laneSuggestedStartTimes[$selectedLane->id])) {
            $suggestedStartTime = $laneSuggestedStartTimes[$selectedLane->id];
        } else {
            $suggestedStartTime = '08:00';
        }

        // 初期終了時刻の算出（開始時刻の60分後、または08:00なら09:30）
        $suggestedEndTime = '09:30';
        if (preg_match('/^(\d{2}):(\d{2})$/', $suggestedStartTime, $matches)) {
            $startMinutes = ((int) $matches[1]) * 60 + ((int) $matches[2]);
            $endMinutes = min(23 * 60 + 59, $startMinutes + ($suggestedStartTime === '08:00' && ! $requestedStartTime ? 90 : 60));
            if ($endMinutes <= $startMinutes) {
                $endMinutes = min(23 * 60 + 59, $startMinutes + 30);
            }
            $suggestedEndTime = sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);
        }

        return view('timetable_tasks.create', [
            'timetable' => $timetable,
            'timetableLanes' => $timetableLanes,
            'selectedLane' => $selectedLane,
            'suggestedStartTime' => $suggestedStartTime,
            'suggestedEndTime' => $suggestedEndTime,
            'laneSuggestedStartTimes' => $laneSuggestedStartTimes,
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
     * 作業タスク詳細画面（スタッフアサイン・出欠管理）の表示
     */
    public function show(TimetableTask $task): View
    {
        $this->authorize('view', $task);

        $detailedTask = $this->taskService->getTaskDetails($task->id) ?? $task;
        $activeStaff = $this->taskService->getActiveStaff();
        $conflictsMap = $this->conflictService->getConflictsMapForStaffList($detailedTask, $activeStaff);
        $prioritizedStaff = $this->conflictService->getPrioritizedStaffForTask($detailedTask, $activeStaff);

        return view('timetable_tasks.show', [
            'task' => $detailedTask,
            'activeStaff' => $activeStaff,
            'prioritizedStaff' => $prioritizedStaff,
            'conflictsMap' => $conflictsMap,
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
            'lanes' => $timetableLanes->pluck('name', 'name')->all(),
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

        if ($request->input('redirect_to') === 'show') {
            return redirect()->route('timetables.show', $task->timetable_id)
                ->with('success', "作業タスク「{$task->title}」を更新しました。");
        }

        return redirect()->route('timetable.index', ['timetable_id' => $task->timetable_id])
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

        if (request()->input('redirect_to') === 'show') {
            return redirect()->route('timetables.show', $timetableId)
                ->with('success', "作業タスク「{$title}」を削除しました。");
        }

        return redirect()->route('timetable.index', ['timetable_id' => $timetableId])
            ->with('success', "作業タスク「{$title}」を削除しました。");
    }

    /**
     * スタッフのタスク割り当て処理（重複検知および強制承認対応）
     */
    public function assignStaff(AssignStaffRequest $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $staffId = (int) $request->validated('staff_id');
        $roleDescription = $request->validated('role_description');
        $isForced = $request->boolean('force');

        // 同一時間帯の重複アサインを検知（他班で定員超過により余剰となっている固定スタッフは自動融通）
        $conflictingTask = $this->conflictService->checkConflict($task, $staffId);
        $transferableSurplusTasks = $conflictingTask
            ? $this->conflictService->getTransferableSurplusTasks($task, $staffId)
            : collect();

        if ($conflictingTask && $transferableSurplusTasks->isEmpty() && ! $isForced) {
            $conflictingLane = $conflictingTask->lane ?: '担当班未設定';
            $startTime = substr($conflictingTask->start_time, 0, 5);
            $endTime = substr($conflictingTask->end_time, 0, 5);
            $warningMessage = "該当スタッフは同時刻（{$startTime}〜{$endTime}）の別タスク「{$conflictingTask->title}」（{$conflictingLane}）に既にアサインされています。";

            if ($request->wantsJson()) {
                return response()->json([
                    'has_conflict' => true,
                    'message' => $warningMessage,
                    'conflicting_task' => [
                        'id' => $conflictingTask->id,
                        'title' => $conflictingTask->title,
                        'lane' => $conflictingLane,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                ], 409);
            }

            return back()->with([
                'conflict_warning' => $warningMessage,
                'conflict_staff_id' => $staffId,
                'conflict_role_description' => $roleDescription,
                'conflict_task_title' => $conflictingTask->title,
            ])->withInput();
        }

        DB::transaction(function () use ($task, $staffId, $roleDescription, $transferableSurplusTasks) {
            foreach ($transferableSurplusTasks as $surplusTask) {
                $this->taskService->removeStaff($surplusTask, $staffId);
            }
            $effectiveRole = $roleDescription ?: ($transferableSurplusTasks->isNotEmpty() ? '他班応援' : null);
            $this->taskService->assignStaff($task, $staffId, $effectiveRole);
        });

        if ($transferableSurplusTasks->isNotEmpty()) {
            $message = '余剰のある別班タスクからスタッフをこのタスクへ融通・割り当てました。';
        } elseif ($conflictingTask) {
            $message = 'スタッフをタスクに割り当てました（重複を承認）。';
        } else {
            $message = 'スタッフをタスクに割り当てました。';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * スタッフのタスク複数一括割り当て処理（重複検知および強制承認対応）
     */
    public function bulkAssignStaff(BulkAssignStaffRequest $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $staffIds = $request->validated('staff_ids');
        $roleDescription = $request->validated('role_description');
        $isForced = $request->boolean('force');

        // 同一時間帯の重複アサインを検知
        $conflictsMap = $this->conflictService->checkConflictsForStaffIds($task, $staffIds);

        if (! empty($conflictsMap) && ! $isForced) {
            $conflictStaffList = [];
            foreach ($conflictsMap as $staffId => $conflictingTask) {
                $staff = Staff::find($staffId);
                $conflictingLane = $conflictingTask->lane ?: '担当班未設定';
                $startTime = substr($conflictingTask->start_time, 0, 5);
                $endTime = substr($conflictingTask->end_time, 0, 5);
                $conflictStaffList[] = [
                    'staff_id' => $staffId,
                    'staff_name' => $staff?->name ?? "ID:{$staffId}",
                    'task_title' => $conflictingTask->title,
                    'lane' => $conflictingLane,
                    'time' => "{$startTime}〜{$endTime}",
                ];
            }

            $names = implode('、', array_column($conflictStaffList, 'staff_name'));
            $warningMessage = "選択されたスタッフのうち、{$names} は同時刻の別タスクに既にアサインされています。";

            if ($request->wantsJson()) {
                return response()->json([
                    'has_conflict' => true,
                    'message' => $warningMessage,
                    'conflicts' => $conflictStaffList,
                ], 409);
            }

            return back()->with([
                'bulk_conflict_warning' => $warningMessage,
                'bulk_conflict_staff_ids' => $staffIds,
                'bulk_conflict_role_description' => $roleDescription,
                'bulk_conflict_list' => $conflictStaffList,
            ])->withInput();
        }

        $this->taskService->bulkAssignStaff($task, $staffIds, $roleDescription);

        $count = count($staffIds);
        $message = ! empty($conflictsMap)
            ? "{$count}名のスタッフをタスクに割り当てました（重複を承認）。"
            : "{$count}名のスタッフをタスクに割り当てました。";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'assigned_count' => $count,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * 班（レーン）専属メンバーの自動一括割り当て処理
     */
    public function assignLanePool(Request $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $laneName = $task->lane ?: ($task->laneRelation?->name ?? '');
        if (empty($laneName)) {
            $msg = '担当班が設定されていないため、班メンバー自動配置は行えません。';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $shortage = $task->shortage_count;
        if ($shortage <= 0) {
            $msg = 'このタスクはすでに人員が充足しています。';
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'assigned_count' => 0]);
            }

            return back()->with('info', $msg);
        }

        // 当該レーンに所属するアクティブスタッフ（固定スタッフおよび所属一致スタッフ）を取得
        $task->loadMissing('laneRelation.fixedStaff');
        $fixedStaffIds = $task->laneRelation
            ? $task->laneRelation->fixedStaff->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $activeStaff = Staff::active()->get();
        $laneMembers = $activeStaff->filter(function (Staff $s) use ($laneName, $fixedStaffIds) {
            if (in_array((int) $s->id, $fixedStaffIds, true)) {
                return true;
            }

            return ! empty($s->affiliation) && (
                mb_strpos($s->affiliation, $laneName) !== false ||
                mb_strpos($laneName, $s->affiliation) !== false
            );
        });

        if ($laneMembers->isEmpty()) {
            $msg = "「{$laneName}」に該当する所属スタッフが見つかりませんでした。";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        // 既にアサイン済みの人を除外
        $alreadyAssignedIds = $task->assignments()->pluck('staff_id')->all();
        $unassignedLaneMembers = $laneMembers->reject(fn (Staff $s) => in_array($s->id, $alreadyAssignedIds));

        // 重複チェック
        $conflictsMap = $this->conflictService->checkConflictsForStaffIds($task, $unassignedLaneMembers->pluck('id')->all());
        // 重複のない空きメンバーを優先
        $availableMembers = $unassignedLaneMembers->reject(fn (Staff $s) => isset($conflictsMap[$s->id]))->values();

        if ($availableMembers->isEmpty()) {
            $msg = "「{$laneName}」の所属スタッフは全員同時刻の他タスクに配置中、または既にアサイン済みです。";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 409);
            }

            return back()->with('conflict_warning', $msg);
        }

        // 不足人数分だけピックアップ
        $toAssign = $availableMembers->take($shortage);
        $assignIds = $toAssign->pluck('id')->all();

        $this->taskService->bulkAssignStaff($task, $assignIds, '班担当');

        $count = count($assignIds);
        $names = $toAssign->pluck('name')->implode('、');
        $msg = "「{$laneName}」から {$count}名（{$names}）を自動配置しました。";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'assigned_count' => $count,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * クイックアサインモーダル用のタスク詳細・スタッフ候補データ取得 (JSON)
     */
    public function getAssignModalData(TimetableTask $task): JsonResponse
    {
        $this->authorize('view', $task);

        $detailedTask = $this->taskService->getTaskDetails($task->id) ?? $task;
        $activeStaff = $this->taskService->getActiveStaff();
        $prioritizedStaff = $this->conflictService->getPrioritizedStaffForTask($detailedTask, $activeStaff);

        return response()->json([
            'task' => [
                'id' => $detailedTask->id,
                'title' => $detailedTask->title,
                'lane' => $detailedTask->lane,
                'start_time' => substr($detailedTask->start_time, 0, 5),
                'end_time' => substr($detailedTask->end_time, 0, 5),
                'location' => $detailedTask->location,
                'required_people_count' => $detailedTask->required_people_count,
                'required_people_label' => $detailedTask->required_people_label,
                'assigned_count' => $detailedTask->assigned_count,
                'is_satisfied' => $detailedTask->is_satisfied,
                'shortage_count' => $detailedTask->shortage_count,
            ],
            'assignments' => $detailedTask->assignments->map(function ($a) use ($detailedTask) {
                $leadId = $detailedTask->laneRelation?->lead_staff_id;

                return [
                    'id' => $a->id,
                    'staff_id' => $a->staff_id,
                    'staff_name' => $a->staff?->name ?? '不明',
                    'staff_kana' => $a->staff?->kana ?? '',
                    'affiliation' => $a->staff?->affiliation ?? '',
                    'role_description' => $a->role_description ?: '一般担当',
                    'attendance_status' => $a->attendance_status,
                    'attendance_status_label' => $a->attendance_status_label,
                    'is_lane_lead' => $leadId !== null && (int) $a->staff_id === (int) $leadId,
                ];
            })->values(),
            'candidate_staff' => $prioritizedStaff->map(function ($s) use ($detailedTask) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'kana' => $s->kana ?? '',
                    'affiliation' => $s->affiliation ?? '',
                    'staff_type' => $s->staff_type ?? 'fulfillment',
                    'staff_type_label' => $s->staff_type_label,
                    'priority_tier' => $s->priority_tier ?? 2,
                    'is_conflicted' => $s->conflict_task !== null,
                    'conflicted_task_title' => $s->conflict_task?->title ?? '',
                    'conflicted_label' => $s->conflict_task ? "重複中: {$s->conflict_task->title}" : '',
                    'is_lane_lead' => (bool) ($s->is_lane_lead ?? false),
                    'is_fixed_in_lane' => (bool) ($s->is_fixed_in_lane ?? false),
                    'is_surplus_fixed' => (bool) ($s->is_surplus_fixed ?? false),
                    'surplus_type' => $s->surplus_type ?? null,
                    'is_same_lane' => (bool) ($s->is_same_lane ?? false),
                    'is_checked_in' => (bool) ($s->is_checked_in ?? false),
                    'minutes_today' => $s->today_assigned_minutes ?? 0,
                    'is_already_assigned' => $detailedTask->assignments->contains('staff_id', $s->id),
                ];
            })->values(),
        ]);
    }

    /**
     * スタッフの交代（ワンタップ代替補充）処理
     */
    public function swapStaff(Request $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'old_staff_id' => ['required', 'integer', 'exists:staff,id'],
            'new_staff_id' => ['required', 'integer', 'exists:staff,id', 'different:old_staff_id'],
            'role_description' => ['nullable', 'string', 'max:255'],
            'force' => ['nullable', 'boolean'],
        ]);

        $oldAssignment = $task->assignments()->where('staff_id', $validated['old_staff_id'])->first();
        if (! $oldAssignment) {
            $msg = '交代対象のスタッフはこのタスクにアサインされていません。';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $oldStaff = Staff::findOrFail($validated['old_staff_id']);
        $newStaff = Staff::findOrFail($validated['new_staff_id']);

        // 既に新スタッフがこのタスクにアサインされていないか確認
        if ($task->assignments()->where('staff_id', $newStaff->id)->exists()) {
            $msg = "スタッフ「{$newStaff->name}」は既にこのタスクにアサインされています。";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        // 新スタッフの重複チェック（forceが未指定の場合）
        $conflictsMap = $this->conflictService->checkConflictsForStaffIds($task, [$newStaff->id]);
        if (isset($conflictsMap[$newStaff->id]) && ! $request->boolean('force')) {
            $conflictTask = $conflictsMap[$newStaff->id];
            $warnMsg = "交代候補「{$newStaff->name}」は、同一時間帯（{$conflictTask->time_range}）のタスク「{$conflictTask->title}」に既に配置されています。";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'has_conflict' => true,
                    'conflict_task' => [
                        'title' => $conflictTask->title,
                        'lane' => $conflictTask->lane,
                        'time' => $conflictTask->time_range,
                    ],
                    'message' => $warnMsg,
                ], 409);
            }

            return back()->with('swap_conflict_warning', $warnMsg)
                ->with('swap_old_staff_id', $oldStaff->id)
                ->with('swap_new_staff_id', $newStaff->id)
                ->with('swap_role_description', $request->input('role_description', $oldAssignment->role_description));
        }

        $role = $request->filled('role_description')
            ? $request->input('role_description')
            : ($oldAssignment->role_description ?: '担当');

        DB::transaction(function () use ($task, $oldStaff, $newStaff, $role) {
            $this->taskService->removeStaff($task, $oldStaff->id);
            $this->taskService->bulkAssignStaff($task, [$newStaff->id], $role);
        });

        $msg = "スタッフ「{$oldStaff->name}」から「{$newStaff->name}」への交代配置を完了しました。";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * スタッフの割り当て解除処理
     */
    public function removeStaff(Request $request, TimetableTask $task, Staff $staff): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $this->taskService->removeStaff($task, $staff->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "スタッフ「{$staff->name}」の割り当てを解除しました。",
            ]);
        }

        return back()->with('success', "スタッフ「{$staff->name}」の割り当てを解除しました。");
    }

    /**
     * 出欠ステータスの更新処理
     */
    public function updateAttendance(UpdateAttendanceRequest $request, TimetableTask $task, TaskStaffAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $task);

        if ($assignment->timetable_task_id !== $task->id) {
            abort(404, '指定されたタスクに該当するアサイン情報が見つかりません。');
        }

        $this->taskService->updateAttendanceStatus($assignment->id, $request->validated('attendance_status'));

        return back()->with('success', '出欠ステータスを更新しました。');
    }
}
