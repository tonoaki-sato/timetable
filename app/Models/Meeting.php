<?php

namespace App\Models;

use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'meeting_date',
    'start_time',
    'end_time',
    'location',
    'staff_id',
    'description',
    'notice_note',
    'created_by',
])]
class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'staff_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /**
     * 主催スタッフとのリレーション
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * 担当スタッフとのリレーション（AnnualScheduleと同一仕様）
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * 担当スタッフとのリレーション
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * 登録ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 時間帯のフォーマット表示（例: 18:00 〜 20:00）
     */
    public function getFormattedTimeRangeAttribute(): string
    {
        $start = substr($this->start_time ?? '', 0, 5);
        if ($this->end_time) {
            $end = substr($this->end_time, 0, 5);

            return "{$start} 〜 {$end}";
        }

        return "{$start} 〜";
    }

    /**
     * 開催日時のフォーマット表示（例: 2027年1月10日(日) 18:00 〜 20:00）
     */
    public function getFormattedDatetimeAttribute(): string
    {
        if (! $this->meeting_date) {
            return '';
        }

        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdayNames[$this->meeting_date->dayOfWeek] ?? '';
        $dateStr = $this->meeting_date->format('Y年n月j日')."({$weekday})";

        return "{$dateStr} {$this->formatted_time_range}";
    }

    /**
     * 開催案内用の全体LINEテキスト
     */
    public function getLineShareTextAttribute(): string
    {
        $datetime = $this->formatted_datetime;
        $hostName = $this->host?->name ?? '未定（後から設定）';
        $location = $this->location;
        $title = $this->title;

        $lines = [
            '【会議開催のお知らせ】',
            "「{$title}」の開催日程が決まりました。",
            '',
            "📅 日時：{$datetime}",
            "📍 場所：{$location}",
            "👤 主催：{$hostName}",
        ];

        if ($this->description) {
            $lines[] = '';
            $lines[] = '📝 議題・内容：';
            $lines[] = $this->description;
        }

        if ($this->notice_note) {
            $lines[] = '';
            $lines[] = '📌 連絡事項：';
            $lines[] = $this->notice_note;
        }

        $lines[] = '--------------------------------';
        $lines[] = 'ご参加よろしくお願いいたします！';

        return implode("\n", $lines);
    }

    /**
     * 前日リマインド用の全体LINEテキスト
     */
    public function getLineRemindTextAttribute(): string
    {
        $datetime = $this->formatted_datetime;
        $hostName = $this->host?->name ?? '未定（後から設定）';
        $location = $this->location;
        $title = $this->title;

        $lines = [
            '【リマインド】明日開催の会議案内',
            "明日、「{$title}」が開催されます。",
            '',
            "📅 日時：{$datetime}",
            "📍 場所：{$location}",
            "👤 主催：{$hostName}",
        ];

        if ($this->description) {
            $lines[] = '';
            $lines[] = '📝 議題・内容：';
            $lines[] = $this->description;
        }

        if ($this->notice_note) {
            $lines[] = '';
            $lines[] = '📌 連絡事項：';
            $lines[] = $this->notice_note;
        }

        $lines[] = '--------------------------------';
        $lines[] = 'お忘れなきよう、よろしくお願いいたします！';

        return implode("\n", $lines);
    }

    /**
     * 開催予定かどうか
     */
    public function getIsUpcomingAttribute(): bool
    {
        if (! $this->meeting_date) {
            return false;
        }

        return $this->meeting_date->isToday() || $this->meeting_date->isFuture();
    }

    /**
     * 開催予定の会議スコープ（直近順）
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('meeting_date', '>=', now()->toDateString())
            ->orderBy('meeting_date', 'asc')
            ->orderBy('start_time', 'asc');
    }

    /**
     * 過去の会議スコープ（開催日降順）
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('meeting_date', '<', now()->toDateString())
            ->orderBy('meeting_date', 'desc')
            ->orderBy('start_time', 'desc');
    }

    /**
     * 主催者（担当スタッフ）によるスコープ
     */
    public function scopeHost(Builder $query, ?int $staffId): Builder
    {
        return $this->scopeAssignee($query, $staffId);
    }

    /**
     * 担当スタッフによるスコープ（AnnualScheduleと同一仕様）
     */
    public function scopeAssignee(Builder $query, ?int $staffId): Builder
    {
        if ($staffId) {
            return $query->where('staff_id', $staffId);
        }

        return $query;
    }

    /**
     * 検索スコープ（会議名・場所・内容・主催者名）
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            return $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('host', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('kana', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
