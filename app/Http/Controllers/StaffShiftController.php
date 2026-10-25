<?php

namespace App\Http\Controllers;

use App\Models\TaskStaffAssignment;
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
            'assignments.task.laneRelation',
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
            }
        }

        return view('staff.myshift', [
            'staff' => $staff,
            'assignments' => $assignments,
            'currentTask' => $currentTask,
            'nextTask' => $nextTask,
            'liffId' => config('services.line.liff_id', ''),
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
     * マイシフト画面からのLINEアカウントワンタップ紐付け
     */
    public function bindLine(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        $validated = $request->validate([
            'line_user_id' => ['required', 'string', 'max:64'],
            'line_display_name' => ['nullable', 'string', 'max:100'],
            'line_picture_url' => ['nullable', 'string', 'max:500'],
        ]);

        $success = $this->staffService->bindLineProfile($staff, $validated);

        if (! $success) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'このLINEアカウントは既に別のスタッフ情報に紐づけられています。',
                    'errors' => [
                        'line_user_id' => ['このLINEアカウントは既に別のスタッフ情報に紐づけられています。'],
                    ],
                ], 422);
            }

            return back()->withErrors(['line_user_id' => 'このLINEアカウントは既に別のスタッフ情報に紐づけられています。']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'staff' => [
                    'line_display_name' => $staff->fresh()->line_display_name,
                ],
                'line_display_name' => $staff->fresh()->line_display_name,
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', 'LINEアカウントとの連携が完了しました！');
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
}
