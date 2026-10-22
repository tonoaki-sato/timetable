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
     * URL紛失時の照合画面表示
     */
    public function showFindForm(): View
    {
        return view('staff.find');
    }

    /**
     * 氏名・フリガナによる照合処理
     */
    public function find(Request $request): View|RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string'],
            'kana' => ['required', 'string'],
        ], [
            'name.required' => 'お名前を入力してください。',
            'kana.required' => 'フリガナを入力してください。',
        ]);

        $matched = $this->staffService->findByNameAndKana(
            trim($request->input('name')),
            trim($request->input('kana'))
        );

        if ($matched->isEmpty()) {
            return back()->withInput()->withErrors([
                'name' => '一致するスタッフ情報が見つかりませんでした。入力内容をご確認いただくか、新規登録をお願いいたします。',
            ]);
        }

        if ($matched->count() === 1) {
            $staff = $matched->first();
            Cookie::queue('staff_token', $staff->access_token, 60 * 24 * 30);

            return redirect()->route('staff.myshift', ['token' => $staff->access_token]);
        }

        // 同姓同名が複数ある場合は選択肢を提示
        return view('staff.find', [
            'candidates' => $matched,
        ]);
    }
}
