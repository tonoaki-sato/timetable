<?php

namespace App\Models;

use App\Presenters\TimetableLanePresenter;
use Database\Factories\TimetableLaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'timetable_id',
    'lane_id',
    'lead_staff_id',
    'display_order',
    'notes',
])]
class TimetableLane extends Model
{
    /** @use HasFactory<TimetableLaneFactory> */
    use HasFactory;

    protected ?TimetableLanePresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): TimetableLanePresenter
    {
        return $this->presenterInstance ??= new TimetableLanePresenter($this);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'lane_id' => 'integer',
            'lead_staff_id' => 'integer',
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 表示順序でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 親タイムテーブルとのリレーション
     *
     * @return BelongsTo<Timetable, $this>
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 親担当班マスターとのリレーション
     *
     * @return BelongsTo<Lane, $this>
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class, 'lane_id');
    }

    /**
     * 班長（主担当スタッフ）とのリレーション
     *
     * @return BelongsTo<Staff, $this>
     */
    public function leadStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'lead_staff_id');
    }

    /**
     * 固定スタッフ一覧との多対多リレーション
     *
     * @return BelongsToMany<Staff, $this>
     */
    public function fixedStaff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'timetable_lane_fixed_staff')
            ->withTimestamps();
    }

    /**
     * 班長からの要請・機材申請一覧とのリレーション
     *
     * @return HasMany<LaneRequest, $this>
     */
    public function laneRequests(): HasMany
    {
        return $this->hasMany(LaneRequest::class, 'timetable_lane_id')->orderByDesc('id');
    }

    /**
     * 所属作業タスク一覧とのリレーション
     *
     * @return HasMany<TimetableTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TimetableTask::class, 'timetable_lane_id')->orderBy('start_time', 'asc');
    }

    /**
     * 班長（主担当）が設定されているか
     */
    public function hasLead(): bool
    {
        return $this->lead_staff_id !== null;
    }

    /**
     * 担当班名（マスター委譲）
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->lane?->name ?? '');
    }

    /**
     * テーマカラー（マスター委譲）
     */
    public function getColorAttribute(): string
    {
        return (string) ($this->lane?->color ?? 'slate');
    }

    /**
     * アイコン（マスター委譲）
     */
    public function getIconAttribute(): ?string
    {
        return $this->lane?->icon;
    }

    /**
     * 標準役割概要（マスター委譲）
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->lane?->description;
    }

    /**
     * 固定スタッフID配列アクセサ
     *
     * @return array<int, int>
     */
    public function getFixedStaffIdsAttribute(): array
    {
        return $this->fixedStaff->pluck('id')->all();
    }
}
