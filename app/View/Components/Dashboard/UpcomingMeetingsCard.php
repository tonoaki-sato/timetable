<?php

namespace App\View\Components\Dashboard;

use App\Models\Meeting;
use App\Services\MeetingService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class UpcomingMeetingsCard extends Component
{
    /** @var Collection<int, Meeting> */
    public readonly Collection $meetings;

    public readonly int $limit;

    public readonly Carbon $now;

    /**
     * Create a new component instance.
     *
     * @param  Collection<int, Meeting>|null  $meetings
     */
    public function __construct(
        protected MeetingService $meetingService,
        int $limit = 3,
        ?Collection $meetings = null,
        ?Carbon $now = null
    ) {
        $this->limit = $limit;
        $this->now = $now ?? Carbon::now('Asia/Tokyo');
        $this->meetings = $meetings ?? $this->meetingService->getUpcomingMeetings($this->limit);
    }

    /**
     * 会議予定が存在するかどうか
     */
    public function hasMeetings(): bool
    {
        return $this->meetings->isNotEmpty();
    }

    /**
     * 開催日までの残り日数を取得
     */
    public function daysUntil(Meeting $meeting): ?int
    {
        if (! $meeting->meeting_date) {
            return null;
        }

        return (int) $this->now->copy()->startOfDay()->diffInDays($meeting->meeting_date->copy()->startOfDay(), false);
    }

    /**
     * 状態バッジ情報（テキスト・CSSクラス）を取得
     *
     * @return array{text: string, class: string}|null
     */
    public function badgeInfo(Meeting $meeting): ?array
    {
        if (! $meeting->meeting_date) {
            return null;
        }

        if ($meeting->meeting_date->isSameDay($this->now)) {
            return [
                'text' => '本日開催',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
            ];
        }

        $days = $this->daysUntil($meeting);
        if ($days !== null && $days > 0 && $days <= 3) {
            return [
                'text' => "あと{$days}日",
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
        }

        return null;
    }

    /**
     * 曜日の日本語表記を取得
     */
    public function weekdayText(Meeting $meeting): string
    {
        if (! $meeting->meeting_date) {
            return '';
        }

        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];

        return $weekdayNames[$meeting->meeting_date->dayOfWeek] ?? '';
    }

    /**
     * 土日かどうか判定
     */
    public function isWeekend(Meeting $meeting): bool
    {
        return in_array($meeting->meeting_date?->dayOfWeek, [0, 6], true);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard.upcoming-meetings-card');
    }
}
