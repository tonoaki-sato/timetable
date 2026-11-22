<?php

namespace App\Http\Controllers;

use App\Actions\Staff\ExportStaffShiftIcsAction;
use App\Enums\AttendanceStatus;
use App\Http\Requests\CheckInStaffShiftRequest;
use App\Http\Requests\ShowStaffShiftRequest;
use App\Http\Requests\StoreLaneRequestFromShiftRequest;
use App\Http\Requests\UpdateLaneTaskStatusRequest;
use App\Http\Requests\UpdateStaffAssignmentStatusRequest;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Services\StaffService;
use App\Services\StaffShiftService;
use App\Services\StaffShiftViewService;
use App\Services\TimetableService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class StaffShiftController extends Controller
{
    public function __construct(
        protected StaffService $staffService,
        protected StaffShiftViewService $shiftViewService,
        protected StaffShiftService $shiftService,
        protected TimetableService $timetableService,
    ) {}

    /**
     * 現場マイタイムテーブル画面の表示
     */
    public function show(ShowStaffShiftRequest $request, string $token): View
    {
        $staff = $this->resolveActiveStaffOrAbort($token, '指定されたタイムテーブルが見つかりません。');

        // 端末自動認識用の長期Cookie（30日間）を保持
        Cookie::queue('staff_token', $staff->access_token, 60 * 24 * 30);
        $request->session()->put('staff_id', $staff->id);

        $timetableFilter = $request->validated('timetable_id');
        $viewData = $this->shiftViewService->getMyShiftViewData($staff, is_string($timetableFilter) ? $timetableFilter : null);

        return view('staff.myshift', $viewData->toArray());
    }

    /**
     * 現場マイシフトの .ics (iCalendar) エクスポート
     */
    public function exportIcs(
        ShowStaffShiftRequest $request,
        string $token,
        ExportStaffShiftIcsAction $exportStaffShiftIcsAction
    ): Response {
        $staff = $this->resolveActiveStaffOrAbort($token);

        $timetableFilter = $request->validated('timetable_id');

        return $exportStaffShiftIcsAction->execute(
            $staff,
            is_string($timetableFilter) ? $timetableFilter : null
        );
    }

    /**
     * 運行情報ごとの現場到着チェックイン処理
     */
    public function checkIn(CheckInStaffShiftRequest $request, string $token): JsonResponse|RedirectResponse
    {
        $staff = $this->resolveActiveStaffOrAbort($token);

        $timetable = $this->timetableService->findOrFail((int) $request->validated('timetable_id'));

        try {
            $checkIn = $this->shiftService->checkInStaff($staff, $timetable);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'time' => $checkIn->checked_in_at->format('H:i'),
                'timetable_id' => $timetable->id,
            ]);
        }

        return redirect()->route('staff.myshift', [
            'token' => $token,
            'timetable_id' => $timetable->id,
        ])->with('success', '現場到着を受付完了しました！');
    }

    /**
     * 現場スタッフによる自身のタスク進捗更新（着手・完了報告）
     */
    public function updateAssignmentStatus(UpdateStaffAssignmentStatusRequest $request, string $token, TaskStaffAssignment $assignment): JsonResponse|RedirectResponse
    {
        $staff = $this->resolveActiveStaffOrAbort($token);

        if ($assignment->staff_id !== $staff->id) {
            abort(403, 'ご自身の担当タスクのみ進捗を報告できます。');
        }

        $status = $request->validated('status');

        try {
            $this->shiftService->updateStaffAssignmentStatus($staff, $assignment->id, $status);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'status_label' => AttendanceStatus::tryFrom((string) $status)?->label() ?? $status,
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', '作業進捗状況を更新しました。');
    }

    /**
     * 班長による担当班タスクの全体ステータス更新
     */
    public function updateLaneTaskStatus(UpdateLaneTaskStatusRequest $request, string $token, TimetableTask $task): JsonResponse|RedirectResponse
    {
        $staff = $this->resolveActiveStaffOrAbort($token);

        if (! $this->shiftService->isLeaderOfTask($staff, $task)) {
            abort(403, '担当する班の班長のみタスク全体の進捗を更新できます。');
        }

        $validated = $request->validated();

        try {
            $this->shiftService->updateLaneTaskStatus($task, $validated['status']);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

        $statusLabel = $task->presenter()->statusLabel();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $task->status,
                'status_label' => $statusLabel,
            ]);
        }

        return redirect()->route('staff.myshift', ['token' => $token])
            ->with('success', "班タスク「{$task->title}」の進捗を「{$statusLabel}」に更新しました。");
    }

    /**
     * 班長による管理者（本部）への人員不足要請・必要機材申請の送信
     */
    public function storeLaneRequest(StoreLaneRequestFromShiftRequest $request, string $token, TimetableLane $timetableLane): JsonResponse|RedirectResponse
    {
        $staff = $this->resolveActiveStaffOrAbort($token);

        if ((int) $timetableLane->lead_staff_id !== (int) $staff->id) {
            abort(403, '担当する班の班長のみ管理者への要請・申請を行えます。');
        }

        try {
            $laneRequest = $this->shiftService->createLaneRequestFromLeader($timetableLane, $staff, $request->validated());
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

        $label = $laneRequest->presenter()->typeLabel();

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

    /**
     * アクセストークンから有効なスタッフを解決（見つからない・無効時は 404）
     */
    protected function resolveActiveStaffOrAbort(
        string $token,
        string $notFoundMessage = '指定されたスタッフが見つかりません。',
    ): Staff {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, $notFoundMessage);
        }

        return $staff;
    }

    /**
     * ドメイン例外時のJSON／リダイレクト共通レスポンス
     */
    protected function domainErrorResponse(Request $request, DomainException $e): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return back()->with('error', $e->getMessage());
    }
}
