<?php

namespace App\Models;

use App\Enums\TimetableStatus;
use App\Presenters\TimetablePresenter;
use App\Support\NavigationCacheInvalidatingBuilder;
use Carbon\Carbon;
use Database\Factories\TimetableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'target_date',
    'name',
    'description',
    'start_time',
    'end_time',
    'status',
    'site_base_map_id',
    'road_periods',
    'festival_periods',
    'created_by',
])]
#[UseEloquentBuilder(NavigationCacheInvalidatingBuilder::class)]
class Timetable extends Model
{
    /** @use HasFactory<TimetableFactory> */
    use HasFactory;

    protected ?TimetablePresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): TimetablePresenter
    {
        return $this->presenterInstance ??= new TimetablePresenter($this);
    }

    /**
     * ステータス Enum
     */
    public function getStatusEnumAttribute(): TimetableStatus
    {
        return TimetableStatus::tryFrom((string) $this->status) ?? TimetableStatus::Draft;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'site_base_map_id' => 'integer',
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
     * 紐づく運行日別担当班編成一覧とのリレーション
     */
    public function timetableLanes(): HasMany
    {
        return $this->hasMany(TimetableLane::class)->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 現場配置図面を経由した全配置アイテム（ブース・現場設備）一覧
     */
    public function layoutItems(): HasManyThrough
    {
        return $this->hasManyThrough(LayoutItem::class, LayoutMap::class);
    }

    /**
     * 現場配置アイテム一覧（ルートバインディング用リレーション）
     */
    public function items(): HasManyThrough
    {
        return $this->layoutItems();
    }

    /**
     * 紐づく資機材搬出入計画一覧とのリレーション
     */
    public function logisticsPlans(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class)->ordered();
    }

    /**
     * 運行完了状態かどうか
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 運行日当日に到達しているか判定（target_date <= 本日）
     */
    public function hasReachedOperationDay(?Carbon $now = null): bool
    {
        if (! $this->target_date) {
            return false;
        }

        $todayDate = ($now ?? now())->format('Y-m-d');

        return $this->target_date->format('Y-m-d') <= $todayDate;
    }

    /**
     * マイシフト画面上で現場操作（到着受付・進捗報告・班長要請）が可能か判定
     */
    public function canOperateOnMyShift(?Carbon $now = null): bool
    {
        return ! $this->isCompleted() && $this->hasReachedOperationDay($now);
    }

    /**
     * 連動するスケジュールとのリレーション
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class, 'timetable_id');
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * 対象日が土日かどうか
     */
    public function getIsWeekendAttribute(): bool
    {
        return $this->target_date !== null && in_array($this->target_date->dayOfWeek, [0, 6], true);
    }

    /**
     * この運行情報で使用する白地図マスター
     *
     * @return BelongsTo<SiteBaseMap, $this>
     */
    public function siteBaseMap(): BelongsTo
    {
        return $this->belongsTo(SiteBaseMap::class, 'site_base_map_id');
    }

    /**
     * 運行情報に紐づく白地図上の全配置アイテムを取得
     *
     * @return HasMany<SiteMapPlacement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(SiteMapPlacement::class, 'timetable_id');
    }

    /**
     * 運行情報に紐づく配置図面マスター一覧とのリレーション
     */
    public function layoutMaps(): HasMany
    {
        return $this->hasMany(LayoutMap::class);
    }

    /**
     * 運行情報に紐づく配置図面マスターとの1対1リレーション
     */
    public function layoutMap(): HasOne
    {
        return $this->hasOne(LayoutMap::class);
    }

    /**
     * 運行情報に紐づくスタッフ到着チェックイン記録一覧とのリレーション
     */
    public function staffCheckIns(): HasMany
    {
        return $this->hasMany(StaffCheckIn::class);
    }

    /**
     * 運行情報に紐づく班長応援・機材申請一覧とのリレーション
     */
    public function laneRequests(): HasMany
    {
        return $this->hasMany(LaneRequest::class);
    }
}
