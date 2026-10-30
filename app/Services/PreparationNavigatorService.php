<?php

namespace App\Services;

use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Support\Collection;

class PreparationNavigatorService
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository
    ) {}

    /**
     * ナビゲーター画面用の集約データを取得
     *
     * @return array{
     *     activeTimetableStates: Collection<int, array<string, mixed>>,
     *     completedTimetableStates: Collection<int, array<string, mixed>>,
     *     totalCount: int,
     *     emptyState: ?array<string, mixed>
     * }
     */
    public function getNavigatorViewData(): array
    {
        $activeTimetables = $this->timetableRepository->getActiveAndDraftWithWorkflowDetails();
        $completedTimetables = $this->timetableRepository->getRecentCompletedWithWorkflowDetails(2);
        $totalCount = $this->timetableRepository->getTotalCount();

        $activeTimetableStates = $activeTimetables->map(
            fn (Timetable $timetable): array => $this->calculateWorkflowStateForTimetable($timetable)
        );

        $completedTimetableStates = $completedTimetables->map(
            fn (Timetable $timetable): array => $this->calculateWorkflowStateForTimetable($timetable)
        );

        $emptyState = null;
        if ($totalCount === 0) {
            $emptyState = [
                'current_step' => 1,
                'action_title' => '運行日（タイムテーブル）を登録しましょう',
                'action_desc' => 'まつりの各日程枠（本祭り、前夜祭など）を登録して準備を開始します。',
                'action_url' => route('timetables.create'),
                'action_btn_text' => '新規タイムテーブルを登録 (Step 1)',
            ];
        }

        return [
            'activeTimetableStates' => $activeTimetableStates,
            'completedTimetableStates' => $completedTimetableStates,
            'totalCount' => $totalCount,
            'emptyState' => $emptyState,
        ];
    }

    /**
     * 推奨ステップ実行先URLの解決
     */
    public function resolveRunUrl(?int $timetableId = null): string
    {
        $totalCount = $this->timetableRepository->getTotalCount();
        if ($totalCount === 0) {
            return route('timetables.create');
        }

        $targetTimetable = null;
        if ($timetableId) {
            $targetTimetable = $this->timetableRepository->findWithWorkflowDetails($timetableId);
        }

        if (! $targetTimetable) {
            $activeTimetables = $this->timetableRepository->getActiveAndDraftWithWorkflowDetails();

            // 未完了ステップ（Step 2〜4で不足あり）の運行日を優先、無ければ先頭の運行日
            $targetTimetable = $activeTimetables->first(function (Timetable $tt): bool {
                $state = $this->calculateWorkflowStateForTimetable($tt);

                return ! $state['is_satisfied'];
            }) ?? $activeTimetables->firstWhere('status', 'active') ?? $activeTimetables->first();
        }

        if (! $targetTimetable) {
            $recentCompleted = $this->timetableRepository->getRecentCompletedWithWorkflowDetails(1);
            $targetTimetable = $recentCompleted->first();
        }

        if (! $targetTimetable) {
            return route('timetables.create');
        }

        $state = $this->calculateWorkflowStateForTimetable($targetTimetable);

        return $state['action_url'];
    }

    /**
     * 特定の運行日に対する運行準備ワークフロー状態を計算
     *
     * @return array{
     *     timetable: Timetable,
     *     current_step: int,
     *     action_title: string,
     *     action_desc: string,
     *     action_url: string,
     *     action_btn_text: string,
     *     shortage_tasks_count: int,
     *     total_tasks_count: int,
     *     lanes_count: int,
     *     lead_configured_count: int,
     *     is_satisfied: bool
     * }
     */
    public function calculateWorkflowStateForTimetable(Timetable $timetable): array
    {
        $lanes = $timetable->lanes;
        $tasks = $timetable->tasks;

        $lanesCount = $lanes->count();
        $leadConfiguredCount = $lanes->filter(fn ($l): bool => $l->lead_staff_id !== null)->count();
        $totalTasksCount = $tasks->count();

        $shortageTasks = $tasks->filter(fn ($t): bool => ! $t->is_satisfied);
        $shortageTasksCount = $shortageTasks->count();

        $isSatisfied = ($lanesCount > 0 && $leadConfiguredCount === $lanesCount && $totalTasksCount > 0 && $shortageTasksCount === 0);

        if ($timetable->status === 'completed') {
            $currentStep = 4;
            $actionTitle = '運行完了（実績・進行表の確認）';
            $actionDesc = "「{$timetable->name}」の運行は完了しています。マトリクス進行表で実績を確認できます。";
            $actionUrl = route('timetable.index', ['timetable_id' => $timetable->id]);
            $actionBtnText = '運行実績を確認';
            $isSatisfied = true;
        } elseif ($lanesCount === 0 || $leadConfiguredCount === 0) {
            $currentStep = 2;
            $actionTitle = '担当班の編成・班長・固定スタッフを登録しましょう';
            $actionDesc = "「{$timetable->name}」の各班（カラーコーン、給水など）に班長および固定スタッフを設定します。";
            $actionUrl = route('timetables.lanes.index', $timetable);
            $actionBtnText = '担当班・スタッフを編成する (Step 2)';
        } elseif ($totalTasksCount === 0) {
            $currentStep = 3;
            $actionTitle = '作業タスクを登録しましょう';
            $actionDesc = '各班のタイムテーブル作業タスクを登録します。設定済みの班長・固定スタッフは自動配置されます。';
            $actionUrl = route('timetable-tasks.create', ['timetable_id' => $timetable->id]);
            $actionBtnText = '作業タスクを登録する (Step 3)';
        } elseif ($shortageTasksCount > 0) {
            $currentStep = 4;
            $actionTitle = "人員不足タスク（{$shortageTasksCount}件）に余剰スタッフを配置しましょう";
            $actionDesc = 'タイムテーブル進行表で過不足を確認し、他班の余剰スタッフや空きメンバーを割り当てます。';
            $actionUrl = route('timetable.index', ['timetable_id' => $timetable->id]);
            $actionBtnText = 'タイムテーブル進行表で人員を配置する (Step 4)';
        } else {
            $currentStep = 4;
            $actionTitle = '全タスクの人員配置が充足しています！';
            $actionDesc = '当日のタイムテーブル運行マトリクス、または人員配置表印刷から最終確認を行えます。';
            $actionUrl = route('timetable.index', ['timetable_id' => $timetable->id]);
            $actionBtnText = 'タイムテーブル進行表を確認 (Step 4)';
        }

        return [
            'timetable' => $timetable,
            'current_step' => $currentStep,
            'action_title' => $actionTitle,
            'action_desc' => $actionDesc,
            'action_url' => $actionUrl,
            'action_btn_text' => $actionBtnText,
            'shortage_tasks_count' => $shortageTasksCount,
            'total_tasks_count' => $totalTasksCount,
            'lanes_count' => $lanesCount,
            'lead_configured_count' => $leadConfiguredCount,
            'is_satisfied' => $isSatisfied,
        ];
    }
}

