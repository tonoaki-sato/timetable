<?php

namespace App\Presenters;

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

        return match ($this->schedule->category) {
            'committee' => '🤝',
            'permit' => '📑',
            'briefing' => '📢',
            'operation' => '🎪',
            default => '📌',
        };
    }

    /**
     * カテゴリのバッジ用CSSクラスを取得
     */
    public function categoryBadgeClass(): string
    {
        return match ($this->schedule->category) {
            'committee' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'permit' => 'bg-amber-50 text-amber-700 border-amber-200',
            'briefing' => 'bg-sky-50 text-sky-700 border-sky-200',
            'operation' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    /**
     * ステータスのバッジ用CSSクラスを取得
     */
    public function statusBadgeClass(): string
    {
        return match ($this->schedule->status) {
            'planned' => 'bg-blue-50 text-blue-700 border-blue-200',
            'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
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
}
