<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\TimetableTask;
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

        // アクティブな運行タイムテーブルを取得
        $activeTimetable = Timetable::with(['tasks.assignments.staff'])
            ->where('status', 'active')
            ->orderBy('target_date')
            ->first()
            ?? Timetable::with(['tasks.assignments.staff'])->orderBy('target_date')->first();

        $totalTasks = collect();
        if ($activeTimetable !== null) {
            $totalTasks = $activeTimetable->tasks;
        }

        // 人員充足状況サマリー（実DB集計）
        $requiredPeopleCount = $totalTasks->sum('required_people_count');
        $assignedPeopleCount = $totalTasks->sum(fn (TimetableTask $t) => $t->assigned_count);

        if ($requiredPeopleCount === 0 && $totalTasks->isEmpty()) {
            $requiredPeopleCount = 40;
            $assignedPeopleCount = 38;
        }

        // 5. 現場機材調整要請
        $equipmentRequests = [
            [
                'id' => 1,
                'title' => 'コーン追加 2本・バー 1本',
                'time' => '10:18 受付',
                'location' => 'ガスト前 店舗前（客列整理用）',
                'liaison' => '漆原さん 承認済',
                'status' => '運搬中',
            ],
        ];

        return view('dashboard', [
            'trafficRegulationContext' => $trafficRegulationContext,
            'activeTimetable' => $activeTimetable,
            'nowDatetime' => $nowDatetime,
            'requiredPeopleCount' => $requiredPeopleCount,
            'assignedPeopleCount' => $assignedPeopleCount,
            'equipmentRequests' => $equipmentRequests,
        ]);
    }
}
