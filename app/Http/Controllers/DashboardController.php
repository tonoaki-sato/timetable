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

        // 4. ⏳ NEXT: 次に控えるタスク
        $dbNextTasks = $totalTasks->filter(function (TimetableTask $task) {
            return $task->status === 'not_started';
        })->sortBy('start_time')->take(3);

        $nextTasks = [];
        if ($dbNextTasks->isNotEmpty()) {
            foreach ($dbNextTasks as $task) {
                $nextTasks[] = [
                    'time' => substr($task->start_time, 0, 5).' - '.substr($task->end_time, 0, 5),
                    'lane' => $task->lane,
                    'title' => $task->title,
                    'location' => $task->location ?: '指定現場',
                    'required_people' => $task->required_people_count,
                ];
            }
        } else {
            $nextTasks = [
                [
                    'time' => '11:00 - 12:00',
                    'lane' => '衛生班 (有江さん)',
                    'title' => 'ゴミ箱チェック（圧縮・袋交換）',
                    'location' => '全ゴミステーション',
                    'required_people' => 3,
                ],
                [
                    'time' => '11:00 - 12:00',
                    'lane' => '給水班 (宮永さん)',
                    'title' => '給水設備 定期点検 (第1回)',
                    'location' => '税務署前・北川駐車場',
                    'required_people' => 2,
                ],
            ];
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
            'nextTasks' => $nextTasks,
            'equipmentRequests' => $equipmentRequests,
        ]);
    }
}
