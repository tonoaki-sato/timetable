<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLaneRequestStatusRequest;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Services\LaneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class LaneRequestController extends Controller
{
    public function __construct(
        protected LaneService $laneService
    ) {}

    /**
     * 班長からの人員応援要請・必要機材申請の対応ステータス更新（管理者・本部オペレーター用）
     */
    public function updateStatus(UpdateLaneRequestStatusRequest $request, LaneRequest $laneRequest): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $laneRequest->lane ?? Lane::class);

        $validated = $request->validated();

        $laneRequest = $this->laneService->updateLaneRequestStatus(
            $laneRequest,
            $validated['status'],
            $request->user()?->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $laneRequest->status,
                'status_label' => $laneRequest->status_label,
            ]);
        }

        return back()->with('success', "班長からの「{$laneRequest->type_label}」を「{$laneRequest->status_label}」に更新しました。");
    }
}
