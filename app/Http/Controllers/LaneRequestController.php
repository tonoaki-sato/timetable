<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\LaneRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LaneRequestController extends Controller
{
    /**
     * 班長からの人員応援要請・必要機材申請の対応ステータス更新（管理者・本部オペレーター用）
     */
    public function updateStatus(Request $request, LaneRequest $laneRequest): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $laneRequest->lane ?? Lane::class);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,resolved,rejected'],
        ]);

        $status = $validated['status'];
        $laneRequest->update([
            'status' => $status,
            'resolved_by_user_id' => $request->user()?->id,
            'resolved_at' => in_array($status, ['approved', 'resolved', 'rejected'], true) ? now() : null,
        ]);

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

