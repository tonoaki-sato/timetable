<?php

namespace App\Presenters;

use App\Enums\ScheduleCategory;
use App\Enums\ScheduleStatus;
use App\Models\Schedule;

class SchedulePresenter
{
    public function __construct(
        protected Schedule $schedule
    ) {}

    /**
     * カテゴリ・種別の絵文字アイコン
     */
    public function categoryIcon(): string
    {
        if ($this->schedule->is_linked_to_timetable) {
            return '🏮';
        }

        return ScheduleCategory::tryFrom((string) $this->schedule->category)?->icon() ?? '📌';
    }

    /**
     * カテゴリのバッジ用CSSクラスを取得
     */
    public function categoryBadgeClass(): string
    {
        return ScheduleCategory::tryFrom((string) $this->schedule->category)?->badgeClasses()
            ?? 'bg-slate-100 text-slate-700 border-slate-200';
    }

    /**
     * ステータスのバッジ用CSSクラスを取得
     */
    public function statusBadgeClass(): string
    {
        return ScheduleStatus::tryFrom((string) $this->schedule->status)?->badgeClasses()
            ?? 'bg-slate-100 text-slate-600 border-slate-200';
    }

    /**
     * 時間帯のフォーマット表示（例: 18:00 〜 20:00）
     */
    public function formattedTimeRange(): ?string
    {
        if (! $this->schedule->start_time) {
            return null;
        }

        $start = substr($this->schedule->start_time, 0, 5);
        if ($this->schedule->end_time) {
            $end = substr($this->schedule->end_time, 0, 5);

            return "{$start} 〜 {$end}";
        }

        return "{$start} 〜";
    }

    /**
     * 開催日時のフォーマット表示（例: 2027年1月10日(日) 18:00 〜 20:00）
     */
    public function formattedDatetime(): string
    {
        if (! $this->schedule->start_date) {
            return '';
        }

        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdayNames[$this->schedule->start_date->dayOfWeek] ?? '';
        $dateStr = $this->schedule->start_date->format('Y年n月j日')."({$weekday})";

        if ($this->schedule->end_date && $this->schedule->end_date->ne($this->schedule->start_date)) {
            $endWeekday = $weekdayNames[$this->schedule->end_date->dayOfWeek] ?? '';
            $endDateStr = $this->schedule->end_date->format('Y年n月j日')."({$endWeekday})";

            return "{$dateStr} 〜 {$endDateStr}";
        }

        if ($this->formattedTimeRange()) {
            return "{$dateStr} {$this->formattedTimeRange()}";
        }

        return $dateStr;
    }

    /**
     * 開催案内用の全体共有テキスト（LINE告知等）
     */
    public function shareText(): string
    {
        $datetime = $this->formattedDatetime();
        $hostName = $this->schedule->assignee?->name ?? '未定（後から設定）';
        $location = $this->schedule->location ?: '未定';
        $title = $this->schedule->title;

        $header = $this->schedule->category === 'committee' ? '【会議開催のお知らせ】' : "【{$this->schedule->category_label}開催のお知らせ】";

        $lines = [
            $header,
            "「{$title}」の開催日程が決まりました。",
            '',
            "📅 日時：{$datetime}",
            "📍 場所：{$location}",
            "👤 担当：{$hostName}",
        ];

        if ($this->schedule->description) {
            $lines[] = '';
            $lines[] = '📝 議題・内容：';
            $lines[] = $this->schedule->description;
        }

        $lines[] = '--------------------------------';
        $lines[] = 'ご参加・ご確認よろしくお願いいたします！';

        return implode("\n", $lines);
    }

    /**
     * PDF表示用の日付・期間フォーマット（時間除く）
     */
    public function pdfDate(): string
    {
        if (! $this->schedule->start_date) {
            return '-';
        }

        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
        $startWeekday = $weekdayNames[$this->schedule->start_date->dayOfWeek] ?? '';
        $startDateStr = $this->schedule->start_date->format('Y/m/d')."({$startWeekday})";

        if ($this->schedule->end_date && $this->schedule->end_date->ne($this->schedule->start_date)) {
            $endWeekday = $weekdayNames[$this->schedule->end_date->dayOfWeek] ?? '';
            $endDateStr = $this->schedule->end_date->format('Y/m/d')."({$endWeekday})";

            return "{$startDateStr} 〜 {$endDateStr}";
        }

        return $startDateStr;
    }

    /**
     * PDF表示用の時間フォーマット
     */
    public function pdfTime(): string
    {
        return $this->formattedTimeRange() ?: '-';
    }
}
