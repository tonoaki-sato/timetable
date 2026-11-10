<?php

namespace App\Http\Controllers;

use App\Services\GlobalDashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected GlobalDashboardService $globalDashboardService
    ) {}

    /**
     * 本部総合統括ダッシュボードの表示
     */
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Tokyo');
        $nowDatetime = $now->translatedFormat('n月j日 (D) H:i:s');
        $overview = $this->globalDashboardService->getOverview($now);

        return view('dashboard', [
            'overview' => $overview,
            'nowDatetime' => $nowDatetime,
        ]);
    }

    /**
     * ダッシュボードのサーバー時刻・稼働ステータス取得（リアルタイム同期用）
     */
    public function realtime(Request $request): JsonResponse
    {
        $now = Carbon::now('Asia/Tokyo');
        $overview = $this->globalDashboardService->getOverview($now);

        return response()->json([
            'server_time_ms' => $now->valueOf(),
            'updated_at_label' => $now->format('H:i:s'),
            'status' => 'ok',
            'days_until_main_event' => $overview->daysUntilMainEvent,
            'schedule_completion_rate' => $overview->scheduleCompletionRate,
        ]);
    }
}
