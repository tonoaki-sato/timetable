<?php

namespace App\Models;

use App\Enums\AnnualPhase;
use App\Presenters\SchedulePresenter;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'timetable_id',
    'title',
    'category',
    'start_date',
    'end_date',
    'start_time',
    'end_time',
    'location',
    'status',
    'description',
    'staff_id',
    'created_by',
])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use HasFactory;

    /**
     * カテゴリ定義一覧（キー => 日本語ラベル）
     *
     * @var array<string, string>
     */
    public const array CATEGORIES = [
        'committee' => '会議・打ち合わせ',
        'permit' => '申請・協議',
        'briefing' => '募集・説明会',
        'operation' => '運行・設営',
        'other' => 'その他',
    ];

    /**
     * ステータス定義一覧（キー => 日本語ラベル）
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'planned' => '予定',
        'in_progress' => '進行中',
        'completed' => '完了',
        'cancelled' => '中止',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'staff_id' => 'integer',
            'created_by' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * 連動する運行スケジュール（タイムテーブル）とのリレーション
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }

    /**
     * 運行スケジュール（タイムテーブル）連動レコードかどうか
     */
    public function getIsLinkedToTimetableAttribute(): bool
    {
        return $this->timetable_id !== null;
    }

    /**
     * 担当スタッフ（または主催スタッフ）とのリレーション
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * 主催スタッフとのリレーション（assigneeのエイリアス）
     */
    public function host(): BelongsTo
    {
        return $this->assignee();
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * カテゴリの日本語表示
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * カテゴリ・種別の絵文字アイコン
     */
    public function getCategoryIconAttribute(): string
    {
        return (new SchedulePresenter($this))->categoryIcon();
    }

    /**
     * 指定カテゴリキーに対する絵文字アイコンを取得
     */
    public static function categoryFilterIcon(string $categoryKey): string
    {
        return match ($categoryKey) {
            'committee' => '🤝',
            'permit' => '📑',
            'briefing' => '📢',
            'operation' => '🎪',
            default => '📌',
        };
    }

    /**
     * カテゴリのバッジ表示用アイコン（連動時も純粋なカテゴリアイコン）
     */
    public function getCategoryBadgeIconAttribute(): string
    {
        return self::categoryFilterIcon((string) $this->category);
    }

    /**
     * カテゴリのバッジ表示用CSSクラス
     */
    public function getCategoryBadgeClassAttribute(): string
    {
        return (new SchedulePresenter($this))->categoryBadgeClass();
    }

    /**
     * ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ステータスのバッジ表示用CSSクラス
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return (new SchedulePresenter($this))->statusBadgeClass();
    }

    /**
     * 時間帯のフォーマット表示（例: 18:00 〜 20:00）
     */
    public function getFormattedTimeRangeAttribute(): ?string
    {
        return (new SchedulePresenter($this))->formattedTimeRange();
    }

    /**
     * 開催日時のフォーマット表示（例: 2027年1月10日(日) 18:00 〜 20:00）
     */
    public function getFormattedDatetimeAttribute(): string
    {
        return (new SchedulePresenter($this))->formattedDatetime();
    }

    /**
     * 開催案内用の全体共有テキスト（LINE告知等）
     */
    public function getShareTextAttribute(): string
    {
        return (new SchedulePresenter($this))->shareText();
    }

    /**
     * 開催予定かどうか
     */
    public function getIsUpcomingAttribute(): bool
    {
        if (! $this->start_date) {
            return false;
        }

        $effectiveDate = $this->end_date ?? $this->start_date;

        return $effectiveDate->isToday() || $effectiveDate->isFuture();
    }

    /**
     * 本日開催かどうか
     */
    public function getIsTodayAttribute(): bool
    {
        if (! $this->start_date) {
            return false;
        }

        return $this->start_date->isToday();
    }

    /**
     * 所属フェーズの取得アクセサ
     */
    public function getPhaseAttribute(): ?AnnualPhase
    {
        if ($this->start_date === null) {
            return null;
        }

        return AnnualPhase::fromMonth((int) $this->start_date->month);
    }

    /**
     * カテゴリによるスコープ
     */
    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if ($category === 'timetable') {
            return $query->whereNotNull('timetable_id');
        }

        if ($category) {
            return $query->where('category', $category)->whereNull('timetable_id');
        }

        return $query;
    }

    /**
     * ステータスによるスコープ
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * 担当スタッフによるスコープ
     */
    public function scopeAssignee(Builder $query, ?int $staffId): Builder
    {
        if ($staffId) {
            return $query->where('staff_id', $staffId);
        }

        return $query;
    }

    /**
     * キーワード検索スコープ（タイトル・場所・詳細・特記・担当者名）
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            return $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('assignee', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('kana', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * フェーズによる絞り込みスコープ
     */
    public function scopePhase(Builder $query, AnnualPhase|string|null $phase): Builder
    {
        if ($phase === null) {
            return $query;
        }

        $phaseEnum = is_string($phase) ? AnnualPhase::tryFrom($phase) : $phase;
        if ($phaseEnum === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($phaseEnum) {
            foreach ($phaseEnum->months() as $month) {
                $q->orWhereMonth('start_date', $month);
            }
        });
    }

    /**
     * 開催予定スコープ（直近順）
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('start_date', '>=', now()->toDateString())
                ->orWhere(function (Builder $sub) {
                    $sub->whereNotNull('end_date')
                        ->where('end_date', '>=', now()->toDateString());
                });
        })
            ->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc');
    }

    /**
     * 過去スコープ（開催日降順）
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('start_date', '<', now()->toDateString())
                ->where(function (Builder $sub) {
                    $sub->whereNull('end_date')
                        ->orWhere('end_date', '<', now()->toDateString());
                });
        })
            ->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'desc');
    }

    /**
     * 会議・打ち合わせに絞り込むスコープ
     */
    public function scopeMeetings(Builder $query): Builder
    {
        return $query->where('category', 'committee');
    }
}
