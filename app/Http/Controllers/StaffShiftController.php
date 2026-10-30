<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class StaffShiftController extends Controller
{
    public function __construct(
        protected StaffService $staffService
    ) {}

    /**
     * 現場マイタイムテーブル画面の表示
     */
    public function show(Request $request, string $token): View
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたタイムテーブルが見つかりません。');
        }

        // 端末自動認識用の長期Cookie（30日間）を保持
        Cookie::queue('staff_token', $staff->access_token, 60 * 24 * 30);
        $request->session()->put('staff_id', $staff->id);

        // アサイン情報を運行日・開始時刻順でロード
        $staff->load([
            'assignments.task.timetable',
            'assignments.task.laneRelation.leadStaff',
        ]);

        $assignments = $staff->assignments->sortBy([
            fn (TaskStaffAssignment $a, TaskStaffAssignment $b) => ($a->task?->timetable?->target_date <=> $b->task?->timetable?->target_date),
            fn (TaskStaffAssignment $a, TaskStaffAssignment $b) => ($a->task?->start_time <=> $b->task?->start_time),
        ]);

        // 現在時刻に基づく Now & Next タスクの判定
        $now = now();
        $todayDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $currentTask = null;
        $nextTask = null;

        foreach ($assignments as $assignment) {
            $task = $assignment->task;
            if (! $task || ! $task->timetable) {
                continue;
            }

            $taskDate = $task->timetable->target_date?->format('Y-m-d');

            // 当日のタスクまたは直近のタスクを対象に判定
            if ($taskDate === $todayDate) {
                if ($task->start_time <= $currentTime && ($task->end_time === null || $task->end_time >= $currentTime)) {
                    if (! $currentTask) {
                        $currentTask = $assignment;
                    }
                } elseif ($task->start_time > $currentTime) {
                    if (! $nextTask) {
                        $nextTask = $assignment;
                    }
                }
            } elseif ($taskDate > $todayDate && ! $currentTask && ! $nextTask) {
                // 当日にタスクがなく未来日のタスクがある場合、最初のタスクを次の予定とする
                $nextTask = $assignment;
            }
        }

        $ledLanes = $staff->ledLanes()
            ->with([
                'timetable',
                'fixedStaff',
                'tasks.assignments.staff',
                'laneRequests.task',
            ])
            ->get();

        return view('staff.myshift', [
            'staff' => $staff,
            'assignments' => $assignments,
            'currentTask' => $currentTask,
            'nextTask' => $nextTask,
            'ledLanes' => $ledLanes,
        ]);
    }

    /**
     * 当日現場到着チェックイン処理
     */
    public function checkIn(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        $this->staffService->checkInStaff($staff);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'time' => $staff->checked_in_at?->format('H:i') ?? now()->format('H:i'),
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', '現場到着を受付完了しました！');
    }

    /**
     * 現場スタッフによる自身のタスク進捗更新（着手・完了報告）
     */
    public function updateAssignmentStatus(Request $request, string $token, TaskStaffAssignment $assignment): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        if ($assignment->staff_id !== $staff->id) {
            abort(403, 'ご自身の担当タスクのみ進捗を報告できます。');
        }

        $request->validate([
            'status' => ['required', 'string', 'in:assigned,checked_in,in_progress,completed,absent'],
        ]);

        $status = $request->string('status')->toString();
        $this->staffService->updateStaffAssignmentStatus($staff, $assignment->id, $status);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'status_label' => TaskStaffAssignment::ATTENDANCE_STATUSES[$status] ?? $status,
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', '作業進捗状況を更新しました。');
    }

    /**
     * 班長による担当班（レーン）タスクの全体ステータス更新
     */
    public function updateLaneTaskStatus(Request $request, string $token, TimetableTask $task): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        $task->loadMissing('laneRelation');
        if (! $task->laneRelation || (int) $task->laneRelation->lead_staff_id !== (int) $staff->id) {
            abort(403, '担当する班の班長のみタスク全体の進捗を更新できます。');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:not_started,in_progress,completed,cancelled'],
        ]);

        $task->update([
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $task->status,
                'status_label' => $task->status_label,
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', "班タスク「{$task->title}」の進捗を「{$task->status_label}」に更新しました。");
    }

    /**
     * 班長による管理者（本部）への人員不足要請・必要機材申請の送信
     */
    public function storeLaneRequest(Request $request, string $token, Lane $lane): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        if ((int) $lane->lead_staff_id !== (int) $staff->id) {
            abort(403, '担当する班の班長のみ管理者への要請・申請を行えます。');
        }

        $validated = $request->validate([
            'request_type' => ['required', 'string', 'in:staffing,equipment'],
            'timetable_task_id' => ['nullable', 'integer', 'exists:timetable_tasks,id'],
            'requested_count' => ['required', 'integer', 'min:1', 'max:100'],
            'equipment_name' => ['required_if:request_type,equipment', 'nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $laneRequest = LaneRequest::create([
            'timetable_id' => $lane->timetable_id,
            'lane_id' => $lane->id,
            'timetable_task_id' => $validated['timetable_task_id'] ?? null,
            'requested_by_staff_id' => $staff->id,
            'request_type' => $validated['request_type'],
            'requested_count' => (int) $validated['requested_count'],
            'equipment_name' => $validated['equipment_name'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        $label = $laneRequest->type_label;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "管理者（本部）へ「{$label}」を送信しました。",
                'lane_request' => $laneRequest,
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', "管理者（本部）へ「{$label}」を送信しました。");
    }
}
