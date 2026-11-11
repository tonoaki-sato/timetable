<?php

namespace App\Models;

use Database\Factories\TimetableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'target_date',
    'name',
    'description',
    'start_time',
    'end_time',
    'status',
    'road_periods',
    'festival_periods',
    'created_by',
])]
class Timetable extends Model
{
    /** @use HasFactory<TimetableFactory> */
    use HasFactory;

    /**
     * ステータス定義一覧
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'draft' => '準備中',
        'active' => '運行中',
        'completed' => '運行完了',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'road_periods' => 'array',
            'festival_periods' => 'array',
        ];
    }

    /**
     * 指定時刻における道路状況を取得
     *
     * @return array{status: string, label: string}
     */
    public function getRoadStatusAt(string $timeStr): array
    {
        if (empty($this->road_periods)) {
            return ['status' => 'open', 'label' => '-'];
        }

        foreach ($this->road_periods as $period) {
            $start = substr($period['start_time'] ?? '', 0, 5);
            $end = substr($period['end_time'] ?? '', 0, 5);

            if ($start && $end && $timeStr >= $start && $timeStr < $end) {
                return [
                    'status' => $period['status'] ?? 'closed',
                    'label' => $period['label'] ?? '通行止め',
                ];
            }
        }

        return ['status' => 'open', 'label' => '-'];
    }

    /**
     * 指定時刻におけるまつり状況を取得
     *
     * @return array{status: string, label: string}
     */
    public function getFestivalStatusAt(string $timeStr): array
    {
        if (empty($this->festival_periods)) {
            return ['status' => 'inactive', 'label' => '-'];
        }

        foreach ($this->festival_periods as $period) {
            $start = substr($period['start_time'] ?? '', 0, 5);
            $end = substr($period['end_time'] ?? '', 0, 5);

            if ($start && $end && $timeStr >= $start && $timeStr < $end) {
                return [
                    'status' => $period['status'] ?? 'active',
                    'label' => $period['label'] ?? '開催中',
                ];
            }
        }

        return ['status' => 'inactive', 'label' => '-'];
    }

    /**
     * 車両通行止めが設定されているか判定
     */
    public function hasRoadClosure(): bool
    {
        if (empty($this->road_periods)) {
            return false;
        }

        foreach ($this->road_periods as $period) {
            if (($period['status'] ?? 'closed') === 'closed') {
                return true;
            }
        }

        return false;
    }

    /**
     * 道路通行止めの代表的な時間帯を取得
     *
     * @return array{start: string, end: string, label: string}|null
     */
    public function getPrimaryRoadClosureRange(): ?array
    {
        if (empty($this->road_periods)) {
            return null;
        }

        foreach ($this->road_periods as $period) {
            if (($period['status'] ?? 'closed') === 'closed') {
                return [
                    'start' => substr($period['start_time'] ?? '', 0, 5),
                    'end' => substr($period['end_time'] ?? '', 0, 5),
                    'label' => $period['label'] ?? '通行止め',
                ];
            }
        }

        return null;
    }

    /**
     * 道路規制・通行止めの予定が設定されているか判定
     */
    public function hasRoadPeriods(): bool
    {
        if (empty($this->road_periods) || ! is_array($this->road_periods)) {
            return false;
        }

        foreach ($this->road_periods as $period) {
            if (! empty($period['start_time']) && ! empty($period['end_time'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * まつり運行の予定が設定されているか判定
     */
    public function hasFestivalPeriods(): bool
    {
        if (empty($this->festival_periods) || ! is_array($this->festival_periods)) {
            return false;
        }

        foreach ($this->festival_periods as $period) {
            if (! empty($period['start_time']) && ! empty($period['end_time'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * target_date のミューテタ（DB保存時に Y-m-d 形式を担保）
     */
    public function setTargetDateAttribute(mixed $value): void
    {
        $this->attributes['target_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : (is_string($value) ? substr($value, 0, 10) : $value);
    }

    /**
     * 紐づく作業タスク一覧とのリレーション
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TimetableTask::class)->orderBy('start_time', 'asc');
    }

    /**
     * 紐づく担当班一覧とのリレーション
     */
    public function lanes(): HasMany
    {
        return $this->hasMany(Lane::class)->orderBy('display_order', 'asc');
    }

    /**
     * 紐づく拠点一覧とのリレーション
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class)->orderBy('display_order', 'asc');
    }

    /**
     * 紐づく資機材搬出入計画一覧とのリレーション
     */
    public function logisticsPlans(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class)->ordered();
    }

    /**
     * 連動するスケジュールとのリレーション
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class, 'timetable_id');
    }

    /**
     * デフォルト10レーンを一括生成
     */
    public function createDefaultLanes(): void
    {
        foreach (Lane::DEFAULT_LANES as $laneData) {
            $this->lanes()->firstOrCreate(
                ['name' => $laneData['name']],
                [
                    'icon' => $laneData['icon'],
                    'color' => $laneData['color'],
                    'description' => $laneData['description'],
                    'display_order' => $laneData['display_order'],
                    'lead_staff_id' => null,
                ]
            );
        }
    }

    /**
     * デフォルト拠点一覧を一括生成
     */
    public function createDefaultLocations(): void
    {
        $types = LocationType::all()->keyBy('name');

        foreach (Location::DEFAULT_LOCATIONS as $locData) {
            $type = $types->get($locData['type_slug']) ?? LocationType::first();
            if (! $type) {
                continue;
            }

            $this->locations()->firstOrCreate(
                ['name' => $locData['name']],
                [
                    'location_type_id' => $type->id,
                    'notes' => $locData['notes'],
                    'is_all_day' => $locData['is_all_day'],
                    'display_order' => $locData['display_order'],
                    'is_active' => true,
                    'created_by' => $this->created_by,
                ]
            );
        }
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ステータスによる絞り込みスコープ
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * 本日開催かどうか
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->target_date !== null && $this->target_date->isToday();
    }

    /**
     * 開催中（active）かどうか
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 終了（completed）かどうか
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 対象日の曜日ラベル（例: '土'）
     */
    public function getWeekdayLabelAttribute(): string
    {
        if (! $this->target_date) {
            return '';
        }

        return ['日', '月', '火', '水', '木', '金', '土'][$this->target_date->dayOfWeek] ?? '';
    }

    /**
     * 対象日が土日かどうか
     */
    public function getIsWeekendAttribute(): bool
    {
        return $this->target_date !== null && in_array($this->target_date->dayOfWeek, [0, 6], true);
    }

    /**
     * 曜日テキストのカラークラス
     */
    public function getWeekdayColorClassAttribute(): string
    {
        if (! $this->target_date) {
            return 'text-slate-400';
        }

        return match ($this->target_date->dayOfWeek) {
            0 => 'text-rose-500',
            6 => 'text-sky-500',
            default => 'text-slate-400',
        };
    }

    /**
     * ステータスバッジのCSSクラス
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
            'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-blue-50 text-blue-700 border-blue-200',
        };
    }

    /**
     * 搬出入計画画面のURLを取得
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function logisticsUrl(array $queryParams = []): string
    {
        return route('timetables.logistics.index', array_merge(['timetable' => $this], $queryParams));
    }

    /**
     * フォーム表示用の歩行者天国（道路規制）時間帯配列を取得
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolveRoadPeriodsForForm(mixed $oldPeriods = null, ?self $timetable = null): array
    {
        $periods = $oldPeriods ?? ($timetable?->road_periods ?? []);

        return is_array($periods) ? $periods : [];
    }

    /**
     * フォーム表示用のまつり開催時間帯配列を取得
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolveFestivalPeriodsForForm(mixed $oldPeriods = null, ?self $timetable = null): array
    {
        $periods = $oldPeriods ?? ($timetable?->festival_periods ?? []);

        return is_array($periods) ? $periods : [];
    }
}
