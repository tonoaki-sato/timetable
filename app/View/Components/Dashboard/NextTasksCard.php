<?php

namespace App\View\Components\Dashboard;

use App\DTOs\NextTasksContext;
use App\Models\TimetableTask;
use App\Services\NextTasksService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NextTasksCard extends Component
{
    public readonly NextTasksContext $context;

    public readonly Carbon $now;

    public readonly int $limit;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected NextTasksService $nextTasksService,
        int $limit = NextTasksService::DEFAULT_LIMIT,
        ?Carbon $now = null,
        ?NextTasksContext $nextTasksContext = null
    ) {
        $this->limit = $limit;
        $this->now = $now ?? Carbon::now('Asia/Tokyo');
        $this->context = $nextTasksContext ?? $this->nextTasksService->getCurrentContext($this->now, $this->limit);
    }

    /**
     * 本番運行日かどうか
     */
    public function isMainEventDay(): bool
    {
        return $this->context->isMainEventDay;
    }

    /**
     * 次に控えるタスクが存在するかどうか
     */
    public function hasTasks(): bool
    {
        return $this->context->tasks->isNotEmpty();
    }

    /**
     * 全体の次期タスク件数が表示上限を超えているか
     */
    public function hasMoreTasks(): bool
    {
        return $this->context->totalNextCount > $this->context->tasks->count();
    }

    /**
     * タスクの時間帯表示（例: 11:00 - 12:00）
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
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.next-tasks-card');
    }
}

