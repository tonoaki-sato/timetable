<?php

namespace App\Http\Controllers;

use App\Models\LaneRequest;
use App\Services\NextTasksService;
use App\Services\NowTasksService;
use App\Services\ParkingConstraintService;
use App\Services\TrafficRegulationService;
use App\View\Components\Dashboard\NextTasksCard;
use App\View\Components\Dashboard\NowTasksCard;
use App\View\Components\Dashboard\ParkingMonitoringCard;
use App\View\Components\Dashboard\TrafficRegulationCard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected TrafficRegulationService $trafficRegulationService,
        protected NowTasksService $nowTasksService,
        protected NextTasksService $nextTasksService,
        protected ParkingConstraintService $parkingConstraintService
    ) {}

    /**
     * 本部運行総合ダッシュボードの表示
     */
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Tokyo');
        $nowDatetime = $now->translatedFormat('n月j日 (D) H:i:s');
        $trafficRegulationContext = $this->trafficRegulationService->getCurrentContext($now);
        $parkingMonitoringContext = $this->parkingConstraintService->getMonitoringContext($now);

        $laneRequests = LaneRequest::with(['lane', 'task', 'requestedByStaff'])
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'trafficRegulationContext' => $trafficRegulationContext,
            'parkingMonitoringContext' => $parkingMonitoringContext,
            'nowDatetime' => $nowDatetime,
            'laneRequests' => $laneRequests,
        ]);
    }

    /**
     * ダッシュボード各カードのリアルタイム表示更新用JSON取得
     */
    public function realtime(Request $request): JsonResponse
    {
        $now = Carbon::now('Asia/Tokyo');

        $trafficComponent = new TrafficRegulationCard($this->trafficRegulationService, $now);
        $nowTasksComponent = new NowTasksCard($this->nowTasksService, $now);
        $nextTasksComponent = new NextTasksCard($this->nextTasksService, NextTasksService::DEFAULT_LIMIT, $now);
        $parkingComponent = new ParkingMonitoringCard($this->parkingConstraintService, $now);

        return response()->json([
            'server_time_ms' => $now->valueOf(),
            'updated_at_label' => $now->format('H:i:s'),
            'cards' => [
                'traffic_regulation' => Blade::renderComponent($trafficComponent),
                'now_tasks' => Blade::renderComponent($nowTasksComponent),
                'next_tasks' => Blade::renderComponent($nextTasksComponent),
                'parking_monitoring' => Blade::renderComponent($parkingComponent),
            ],
        ]);
    }
}
