<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInStaffShiftRequest;
use App\Http\Requests\ShowStaffShiftRequest;
use App\Http\Requests\StoreLaneRequestFromShiftRequest;
use App\Http\Requests\UpdateLaneTaskStatusRequest;
use App\Http\Requests\UpdateStaffAssignmentStatusRequest;
use App\Models\Lane;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Services\StaffService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class StaffShiftController extends Controller
{
    protected TimetableRepositoryInterface $timetableRepository;

    public function __construct(
        protected StaffService $staffService,
        ?TimetableRepositoryInterface $timetableRepository = null,
    ) {
        $this->timetableRepository = $timetableRepository ?? app(TimetableRepositoryInterface::class);
    }

    /**
     * 現場マイタイムテーブル画面の表示
     */
    public function show(ShowStaffShiftRequest $request, string $token): View
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたタイムテーブルが見つかりません。');
        }

        // 端末自動認識用の長期Cookie（30日間）を保持
        Cookie::queue('staff_token', $staff->access_token, 60 * 24 * 30);
        $request->session()->put('staff_id', $staff->id);

        $timetableFilter = $request->validated('timetable_id');
        $viewData = $this->staffService->getMyShiftViewData($staff, is_string($timetableFilter) ? $timetableFilter : null);

        return view('staff.myshift', $viewData->toArray());
    }

    /**
     * 運行情報ごとの現場到着チェックイン処理
     */
    public function checkIn(CheckInStaffShiftRequest $request, string $token): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        $timetable = $this->timetableRepository->findOrFail((int) $request->validated('timetable_id'));

        try {
            $checkIn = $this->staffService->checkInStaff($staff, $timetable);
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
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        if ($assignment->staff_id !== $staff->id) {
            abort(403, 'ご自身の担当タスクのみ進捗を報告できます。');
        }

        $status = $request->validated('status');

        try {
            $this->staffService->updateStaffAssignmentStatus($staff, $assignment->id, $status);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

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
     * 班長による担当班タスクの全体ステータス更新
     */
    public function updateLaneTaskStatus(UpdateLaneTaskStatusRequest $request, string $token, TimetableTask $task): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        $task->loadMissing('laneRelation');
        if (! $task->laneRelation || (int) $task->laneRelation->lead_staff_id !== (int) $staff->id) {
            abort(403, '担当する班の班長のみタスク全体の進捗を更新できます。');
        }

        $validated = $request->validated();

        try {
            $this->staffService->updateLaneTaskStatus($task, $validated['status']);
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

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
    public function storeLaneRequest(StoreLaneRequestFromShiftRequest $request, string $token, Lane $lane): JsonResponse|RedirectResponse
    {
        $staff = $this->staffService->getStaffByToken($token);

        if (! $staff || ! $staff->is_active) {
            abort(404, '指定されたスタッフが見つかりません。');
        }

        if ((int) $lane->lead_staff_id !== (int) $staff->id) {
            abort(403, '担当する班の班長のみ管理者への要請・申請を行えます。');
        }

        try {
            $laneRequest = $this->staffService->createLaneRequestFromLeader($lane, $staff, $request->validated());
        } catch (DomainException $e) {
            return $this->domainErrorResponse($request, $e);
        }

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
