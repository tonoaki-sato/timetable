<?php

namespace App\Http\Controllers;

use App\Services\TrafficRegulationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected TrafficRegulationService $trafficRegulationService
    ) {}

    /**
     * 本部運行総合ダッシュボードの表示
     */
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Tokyo');
        $nowDatetime = $now->translatedFormat('n月j日 (D) H:i:s');
        $trafficRegulationContext = $this->trafficRegulationService->getCurrentContext($now);

        return view('dashboard', [
            'trafficRegulationContext' => $trafficRegulationContext,
            'nowDatetime' => $nowDatetime,
        ]);
    }
}
