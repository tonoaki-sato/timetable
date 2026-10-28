<?php

namespace App\View\Components\Dashboard;

use App\DTOs\NowTasksContext;
use App\Models\TimetableTask;
use App\Services\NowTasksService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class NowTasksCard extends Component
{
    public readonly NowTasksContext $context;

    public readonly Carbon $now;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected NowTasksService $nowTasksService,
        ?Carbon $now = null,
        ?NowTasksContext $nowTasksContext = null
    ) {
        $this->now = $now ?? Carbon::now('Asia/Tokyo');
        $this->context = $nowTasksContext ?? $this->nowTasksService->getCurrentContext($this->now);
    }

    /**
     * 本番運行日かどうか
     */
    public function isMainEventDay(): bool
    {
        return $this->context->isMainEventDay;
    }

    /**
     * 現在進行中のタスクが存在するかどうか
     */
    public function hasTasks(): bool
    {
        return $this->context->tasks->isNotEmpty();
    }

    /**
     * 現在進行中のタスク件数
     */
    public function taskCount(): int
    {
        return $this->context->tasks->count();
    }

    /**
     * タスクの時間帯表示（例: 10:00 - 11:00）
     */
    public function formatTimeRange(TimetableTask $task): string
    {
        $start = substr((string) $task->start_time, 0, 5);
        $end = substr((string) $task->end_time, 0, 5);

        return "{$start} - {$end}";
    }

    /**
     * 担当班（レーン）の表示文字列（アイコン＋班名＋主担当名）
     */
    public function formatLaneDisplay(TimetableTask $task): string
    {
        $lane = $task->laneRelation;
        if ($lane === null) {
            return '';
        }

        $icon = $lane->icon ? "{$lane->icon} " : '';
        $name = $lane->name;
        $leadName = $lane->leadStaff?->name;

        if ($leadName !== null && $leadName !== '') {
            return "{$icon}{$name} ({$leadName})";
        }

        return "{$icon}{$name}";
    }

    /**
     * 作業内容・指示の抜粋テキストを取得
     */
    public function formatContentSummary(TimetableTask $task): ?string
    {
        $raw = $task->content ?: $task->description;
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($raw)) ?? '';

        return Str::limit($normalized, 45);
    }

    /**
     * ステータスバッジのCSSクラスを取得
     */
    public function statusBadgeClasses(TimetableTask $task): string
    {
        return match ($task->status) {
            'in_progress' => 'text-indigo-700 bg-indigo-50 border border-indigo-200',
            'not_started' => 'text-amber-800 bg-amber-50 border border-amber-200',
            'completed' => 'text-emerald-700 bg-emerald-50 border border-emerald-200',
            default => 'text-slate-700 bg-slate-100 border border-slate-200',
        };
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.now-tasks-card');
    }
}
