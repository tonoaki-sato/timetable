<?php

namespace App\View\Components\Dashboard;

use App\DTOs\PhaseContext;
use App\Services\PhaseService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PhaseCard extends Component
{
    public readonly PhaseContext $context;

    public readonly Carbon $now;

    /**
     * Create a new component instance.
     */
    public function __construct(
        protected PhaseService $phaseService,
        ?Carbon $now = null,
        ?PhaseContext $phaseContext = null
    ) {
        $this->now = $now ?? Carbon::now('Asia/Tokyo');
        $this->context = $phaseContext ?? $this->phaseService->getCurrentContext($this->now);
    }

    /**
     * カード全体のリンク先URLを取得
     */
    public function actionUrl(): string
    {
        return $this->context->actionUrl ?? route('annual-schedules.index');
    }

    /**
     * フェーズ種別ラベル（運行フェーズ / 準備フェーズ）を取得
     */
    public function typeLabel(): string
    {
        return $this->context->isMainEventDay ? '運行フェーズ' : '準備フェーズ';
    }

    /**
     * 残タスク件数バッジの表示可否
     */
    public function shouldShowPendingBadge(): bool
    {
        return ! $this->context->isMainEventDay && $this->context->pendingTaskCount > 0;
    }

    /**
     * 残タスク件数を取得
     */
    public function pendingTaskCount(): int
    {
        return $this->context->pendingTaskCount;
    }

    /**
     * メイン表示名を取得
     */
    public function displayName(): string
    {
        if ($this->context->displayName !== null) {
            return $this->context->displayName;
        }

        if ($this->context->isMainEventDay) {
            return $this->context->activeTimetable?->name ?? 'NO NAME';
        }

        return $this->context->phase->label();
    }

    /**
     * サブステータス文言を取得
     */
    public function subStatus(): string
    {
        if ($this->context->subStatus !== null) {
            return $this->context->subStatus;
        }

        if ($this->context->isMainEventDay) {
            return '本番運行セッション進行中';
        }

        $nextTask = $this->context->nextTask;
        if ($nextTask === null || $nextTask->start_date === null) {
            return '直近の年間行事予定なし';
        }

        $dateStr = $nextTask->start_date->format('n/j');
        $daysDiff = (int) $this->now->startOfDay()->diffInDays($nextTask->start_date->startOfDay(), false);

        if ($daysDiff === 0) {
            return "本日: {$nextTask->title}";
        }

        if ($daysDiff > 0) {
            return "直近: {$dateStr} {$nextTask->title} (あと{$daysDiff}日)";
        }

        return "直近: {$dateStr} {$nextTask->title}";
    }

    /**
     * サブステータスの文字色クラスを取得
     */
    public function subStatusColorClass(): string
    {
        return $this->context->isMainEventDay ? 'text-emerald-600' : 'text-indigo-600';
    }

    /**
     * 表示アイコン（絵文字）を取得
     */
    public function icon(): string
    {
        if ($this->context->isMainEventDay) {
            return '🏮';
        }

        return $this->context->phase?->icon() ?? '📋';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.phase-card');
    }
}
