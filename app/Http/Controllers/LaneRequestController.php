<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLaneRequestStatusRequest;
use App\Models\LaneRequest;
use App\Services\TimetableLaneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LaneRequestController extends Controller
{
    public function __construct(
        protected TimetableLaneService $timetableLaneService
    ) {}

    /**
     * 班長からの人員応援要請・必要機材申請の対応ステータス更新（管理者・本部オペレーター用）
     */
    public function updateStatus(UpdateLaneRequestStatusRequest $request, LaneRequest $laneRequest): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $laneRequest);

        $validated = $request->validated();

        $laneRequest = $this->timetableLaneService->updateLaneRequestStatus(
            $laneRequest,
            $validated['status'],
            $request->user()?->id
        );

        $presenter = $laneRequest->presenter();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $laneRequest->status,
                'status_label' => $presenter->statusLabel(),
            ]);
        }

        return back()->with('success', "班長からの「{$presenter->typeLabel()}」を「{$presenter->statusLabel()}」に更新しました。");
    }
}
