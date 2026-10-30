<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PreparationNavigatorController extends Controller
{
    /**
     * まつり運行準備ナビゲーター画面の表示
     */
    public function index(Request $request): View
    {
        /** @var Collection<int, Timetable> $timetables */
        $timetables = Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->orderBy('target_date', 'asc')
            ->get();

        $selectedTimetableId = $request->query('timetable_id');
        $activeTimetable = null;

        if ($selectedTimetableId) {
            $activeTimetable = $timetables->firstWhere('id', (int) $selectedTimetableId);
        }

        if (! $activeTimetable) {
            $activeTimetable = $timetables->firstWhere('status', 'active') ?? $timetables->first();
        }

        $workflowState = $this->calculateWorkflowState($timetables, $activeTimetable);

        return view('preparation_navigator.index', [
            'timetables' => $timetables,
            'activeTimetable' => $activeTimetable,
            'workflowState' => $workflowState,
        ]);
    }

    /**
     * 推奨ステップを直接実行（リダイレクト）
     */
    public function run(Request $request): RedirectResponse
    {
        /** @var Collection<int, Timetable> $timetables */
        $timetables = Timetable::with(['lanes.fixedStaff', 'lanes.leadStaff', 'tasks.assignments'])
            ->orderBy('target_date', 'asc')
            ->get();

        $selectedTimetableId = $request->query('timetable_id');
        $activeTimetable = null;

        if ($selectedTimetableId) {
            $activeTimetable = $timetables->firstWhere('id', (int) $selectedTimetableId);
        }

        if (! $activeTimetable) {
            $activeTimetable = $timetables->firstWhere('status', 'active') ?? $timetables->first();
        }

        $workflowState = $this->calculateWorkflowState($timetables, $activeTimetable);

        return redirect($workflowState['action_url']);
    }

    /**
     * 運行準備ワークフロー（Step 1〜4）の状態を計算
     *
     * @param  Collection<int, Timetable>  $timetables
     * @return array{
     *     has_timetable: bool,
     *     active_timetable: ?Timetable,
     *     current_step: int,
     *     action_title: string,
     *     action_desc: string,
     *     action_url: string,
     *     action_btn_text: string,
     *     shortage_tasks_count: int,
     *     total_tasks_count: int,
     *     lanes_count: int,
     *     lead_configured_count: int
     * }
     */
    protected function calculateWorkflowState(Collection $timetables, ?Timetable $activeTimetable): array
    {
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
            'lanes_count' => 0,
            'lead_configured_count' => 0,
        ];

        if (! $workflowState['has_timetable']) {
            $workflowState['current_step'] = 1;
            $workflowState['action_title'] = '運行日（タイムテーブル）を登録しましょう';
            $workflowState['action_desc'] = 'まつりの各日程枠（本祭り、前夜祭など）を登録して準備を開始します。';
            $workflowState['action_url'] = route('timetables.create');
            $workflowState['action_btn_text'] = '新規タイムテーブルを登録 (Step 1)';
        } elseif ($activeTimetable) {
            $lanes = $activeTimetable->lanes;
            $tasks = $activeTimetable->tasks;
            $workflowState['lanes_count'] = $lanes->count();
            $workflowState['lead_configured_count'] = $lanes->filter(fn ($l) => $l->lead_staff_id !== null)->count();
            $workflowState['total_tasks_count'] = $tasks->count();

            $shortageTasks = $tasks->filter(fn ($t) => ! $t->is_satisfied);
            $workflowState['shortage_tasks_count'] = $shortageTasks->count();

            if ($lanes->isEmpty() || $workflowState['lead_configured_count'] === 0) {
                $workflowState['current_step'] = 2;
                $workflowState['action_title'] = '担当班の編成・班長・固定スタッフを登録しましょう';
                $workflowState['action_desc'] = "「{$activeTimetable->name}」の各班（カラーコーン、給水など）に班長および固定スタッフを設定します。";
                $workflowState['action_url'] = route('timetables.lanes.index', $activeTimetable);
                $workflowState['action_btn_text'] = '担当班・スタッフを編成する (Step 2)';
            } elseif ($tasks->isEmpty()) {
                $workflowState['current_step'] = 3;
                $workflowState['action_title'] = '作業タスクを登録しましょう';
                $workflowState['action_desc'] = '各班のタイムテーブル作業タスクを登録します。設定済みの班長・固定スタッフは自動配置されます。';
                $workflowState['action_url'] = route('timetable-tasks.create', ['timetable_id' => $activeTimetable->id]);
                $workflowState['action_btn_text'] = '作業タスクを登録する (Step 3)';
            } elseif ($workflowState['shortage_tasks_count'] > 0) {
                $workflowState['current_step'] = 4;
                $workflowState['action_title'] = "人員不足タスク（{$workflowState['shortage_tasks_count']}件）に余剰スタッフを配置しましょう";
                $workflowState['action_desc'] = 'タイムテーブル進行表で過不足を確認し、他班の余剰スタッフや空きメンバーを割り当てます。';
                $workflowState['action_url'] = route('timetable.index', ['timetable_id' => $activeTimetable->id]);
                $workflowState['action_btn_text'] = 'タイムテーブル進行表で人員を配置する (Step 4)';
            } else {
                $workflowState['current_step'] = 4;
                $workflowState['action_title'] = '全タスクの人員配置が充足しています！';
                $workflowState['action_desc'] = '当日のタイムテーブル運行マトリクス、または人員配置表印刷から最終確認を行えます。';
                $workflowState['action_url'] = route('timetable.index', ['timetable_id' => $activeTimetable->id]);
                $workflowState['action_btn_text'] = 'タイムテーブル進行表を確認 (Step 4)';
            }
        }

        return $workflowState;
    }
}

