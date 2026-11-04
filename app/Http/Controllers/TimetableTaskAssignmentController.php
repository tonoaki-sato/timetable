<?php

namespace App\Http\Controllers;

use App\Actions\TaskAssignment\AssignStaffAction;
use App\Actions\TaskAssignment\AutoAssignLanePoolAction;
use App\Actions\TaskAssignment\BulkAssignStaffAction;
use App\Actions\TaskAssignment\SwapStaffAssignmentAction;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\BulkAssignStaffRequest;
use App\Http\Requests\SwapStaffRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\TimetableTaskAssignModalResource;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Services\StaffAllocationService;
use App\Services\TimetableTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimetableTaskAssignmentController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected StaffAllocationService $allocationService,
        protected AssignStaffAction $assignStaffAction,
        protected BulkAssignStaffAction $bulkAssignStaffAction,
        protected AutoAssignLanePoolAction $autoAssignLanePoolAction,
        protected SwapStaffAssignmentAction $swapStaffAction
    ) {}

    /**
     * スタッフのタスク割り当て処理（重複検知および強制承認対応）
     */
    public function assignStaff(AssignStaffRequest $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $staffId = (int) $request->validated('staff_id');
        $roleDescription = $request->validated('role_description');
        $isForced = $request->boolean('force');

        $result = $this->assignStaffAction->execute($task, $staffId, $roleDescription, $isForced);

        if (! $result['success'] && $result['has_conflict']) {
            $conflictingTask = $result['conflicting_task'];
            $conflictingLane = $conflictingTask?->lane ?: '担当班未設定';
            $startTime = substr($conflictingTask?->start_time ?? '', 0, 5);
            $endTime = substr($conflictingTask?->end_time ?? '', 0, 5);

            if ($request->wantsJson()) {
                return response()->json([
                    'has_conflict' => true,
                    'message' => $result['message'],
                    'conflicting_task' => [
                        'id' => $conflictingTask?->id,
                        'title' => $conflictingTask?->title,
                        'lane' => $conflictingLane,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                ], 409);
            }

            return back()->with([
                'conflict_warning' => $result['message'],
                'conflict_staff_id' => $staffId,
                'conflict_role_description' => $roleDescription,
                'conflict_task_title' => $conflictingTask?->title,
            ])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return back()->with('success', $result['message']);
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

        $result = $this->bulkAssignStaffAction->execute($task, $staffIds, $roleDescription, $isForced);

        if (! $result['success'] && $result['has_conflict']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'has_conflict' => true,
                    'message' => $result['message'],
                    'conflicts' => $result['conflicts'],
                ], 409);
            }

            return back()->with([
                'bulk_conflict_warning' => $result['message'],
                'bulk_conflict_staff_ids' => $staffIds,
                'bulk_conflict_role_description' => $roleDescription,
                'bulk_conflict_list' => $result['conflicts'],
            ])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'assigned_count' => $result['assigned_count'],
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * 班（レーン）専属メンバーの自動一括割り当て処理
     */
    public function assignLanePool(Request $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $result = $this->autoAssignLanePoolAction->execute($task);

        if (! $result['success']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], $result['status_code']);
            }

            return back()->with($result['warning_type'] ?? 'error', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'assigned_count' => $result['assigned_count'],
            ]);
        }

        return back()->with($result['warning_type'] ?? 'success', $result['message']);
    }

    /**
     * クイックアサインモーダル用のタスク詳細・スタッフ候補データ取得 (JSON)
     */
    public function getAssignModalData(TimetableTask $task): JsonResponse
    {
        $this->authorize('view', $task);

        $detailedTask = $this->taskService->getTaskDetails($task->id) ?? $task;
        $activeStaff = $this->taskService->getActiveStaff();
        $prioritizedStaff = $this->allocationService->getPrioritizedStaffForTask($detailedTask, $activeStaff);

        return response()->json((new TimetableTaskAssignModalResource($detailedTask, $prioritizedStaff))->resolve());
    }

    /**
     * スタッフの交代（ワンタップ代替補充）処理
     */
    public function swapStaff(SwapStaffRequest $request, TimetableTask $task): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $task);

        $oldStaffId = (int) $request->validated('old_staff_id');
        $newStaffId = (int) $request->validated('new_staff_id');
        $roleDescription = $request->validated('role_description');
        $isForced = $request->boolean('force');

        $result = $this->swapStaffAction->execute($task, $oldStaffId, $newStaffId, $roleDescription, $isForced);

        if (! $result['success']) {
            if ($result['has_conflict'] ?? false) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'has_conflict' => true,
                        'conflict_task' => $result['conflict_task'],
                        'message' => $result['message'],
                    ], 409);
                }

                return back()->with('swap_conflict_warning', $result['message'])
                    ->with('swap_old_staff_id', $result['old_staff']->id)
                    ->with('swap_new_staff_id', $result['new_staff']->id)
                    ->with('swap_role_description', $result['role_description']);
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $result['message']], $result['status_code'] ?? 422);
            }

            return back()->with('error', $result['message']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return back()->with('success', $result['message']);
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
