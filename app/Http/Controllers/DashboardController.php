<?php

namespace App\Http\Controllers;

use App\Models\LaneRequest;
use App\Models\Timetable;
use App\Services\NextTasksService;
use App\Services\NowTasksService;
use App\Services\TrafficRegulationService;
use App\View\Components\Dashboard\NextTasksCard;
use App\View\Components\Dashboard\NowTasksCard;
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
        protected NextTasksService $nextTasksService
    ) {}

    /**
     * 本部運行総合ダッシュボードの表示
     */
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Tokyo');
        $nowDatetime = $now->translatedFormat('n月j日 (D) H:i:s');
        $trafficRegulationContext = $this->trafficRegulationService->getCurrentContext($now);

        $laneRequests = LaneRequest::with(['lane', 'task', 'requestedByStaff'])
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // まつり運行準備ワークフロー（Step 1〜5）の進捗状況を判定
        $timetables = Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->orderBy('target_date', 'asc')
            ->get();

        $activeTimetable = $timetables->firstWhere('status', 'active') ?? $timetables->first();

        $workflowState = [
            'has_timetable' => $timetables->isNotEmpty(),
            'active_timetable' => $activeTimetable,
            'current_step' => 1,
            'action_title' => '',
            'action_desc' => '',
            'action_url' => '',
            'action_btn_text' => '',
            'shortage_tasks_count' => 0,
            'total_tasks_count' => 0,
            'assigned_staff_count' => 0,
            'lanes_count' => 0,
            'lead_configured_count' => 0,
        ];

        if (! $workflowState['has_timetable']) {
            $workflowState['current_step'] = 2;
            $workflowState['action_title'] = '運行日（タイムテーブル）を登録しましょう';
            $workflowState['action_desc'] = 'まつりの各日程枠（本祭り、前夜祭など）を登録して準備を開始します。';
            $workflowState['action_url'] = route('timetables.create');
            $workflowState['action_btn_text'] = '新規タイムテーブルを登録 (Step 2)';
        } elseif ($activeTimetable) {
            $lanes = $activeTimetable->lanes;
            $tasks = $activeTimetable->tasks;
            $workflowState['lanes_count'] = $lanes->count();
            $workflowState['lead_configured_count'] = $lanes->filter(fn ($l) => $l->lead_staff_id !== null)->count();
            $workflowState['total_tasks_count'] = $tasks->count();

            $shortageTasks = $tasks->filter(fn ($t) => ! $t->is_satisfied);
            $workflowState['shortage_tasks_count'] = $shortageTasks->count();

            if ($lanes->isEmpty() || $workflowState['lead_configured_count'] === 0) {
                $workflowState['current_step'] = 3;
                $workflowState['action_title'] = '担当班の編成・班長・固定スタッフを登録しましょう';
                $workflowState['action_desc'] = "「{$activeTimetable->name}」の各班（カラーコーン、給水など）に班長および固定スタッフを設定します。";
                $workflowState['action_url'] = route('timetables.lanes.index', $activeTimetable);
                $workflowState['action_btn_text'] = '担当班・スタッフを編成する (Step 3)';
            } elseif ($tasks->isEmpty()) {
                $workflowState['current_step'] = 4;
                $workflowState['action_title'] = '作業タスクを登録しましょう';
                $workflowState['action_desc'] = '各班のタイムテーブル作業タスクを登録します。設定済みの班長・固定スタッフは自動配置されます。';
                $workflowState['action_url'] = route('timetable-tasks.create', ['timetable_id' => $activeTimetable->id]);
                $workflowState['action_btn_text'] = '作業タスクを登録する (Step 4)';
            } elseif ($workflowState['shortage_tasks_count'] > 0) {
                $workflowState['current_step'] = 5;
                $workflowState['action_title'] = "人員不足タスク（{$workflowState['shortage_tasks_count']}件）に余剰スタッフを配置しましょう";
                $workflowState['action_desc'] = 'タイムテーブル進行表で過不足を確認し、他班の余剰スタッフや空きメンバーを割り当てます。';
                $workflowState['action_url'] = route('timetable.index', ['timetable_id' => $activeTimetable->id]);
                $workflowState['action_btn_text'] = 'タイムテーブル進行表で人員を配置する (Step 5)';
            } else {
                $workflowState['current_step'] = 5;
                $workflowState['action_title'] = '全タスクの人員配置が充足しています！';
                $workflowState['action_desc'] = '当日のタイムテーブル運行マトリクス、または人員配置表印刷から最終確認を行えます。';
                $workflowState['action_url'] = route('timetable.index', ['timetable_id' => $activeTimetable->id]);
                $workflowState['action_btn_text'] = 'タイムテーブル進行表を確認';
            }
        }

        return view('dashboard', [
            'trafficRegulationContext' => $trafficRegulationContext,
            'nowDatetime' => $nowDatetime,
            'laneRequests' => $laneRequests,
            'workflowState' => $workflowState,
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

        return response()->json([
            'server_time_ms' => $now->valueOf(),
            'updated_at_label' => $now->format('H:i:s'),
            'cards' => [
                'traffic_regulation' => Blade::renderComponent($trafficComponent),
                'now_tasks' => Blade::renderComponent($nowTasksComponent),
                'next_tasks' => Blade::renderComponent($nextTasksComponent),
            ],
        ]);
    }
}
