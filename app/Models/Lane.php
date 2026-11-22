<?php

namespace App\Models;

use App\Presenters\LanePresenter;
use Database\Factories\LaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'name',
    'color',
    'icon',
    'description',
    'display_order',
    'is_active',
])]
class Lane extends Model
{
    /** @use HasFactory<LaneFactory> */
    use HasFactory;

    protected ?LanePresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): LanePresenter
    {
        return $this->presenterInstance ??= new LanePresenter($this);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 有効な担当班マスターのみ抽出するスコープ
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順序でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 運行日別担当班編成一覧とのリレーション
     *
     * @return HasMany<TimetableLane, $this>
     */
    public function timetableLanes(): HasMany
    {
        return $this->hasMany(TimetableLane::class, 'lane_id');
    }

    /**
     * この担当班を編成している運行計画一覧との多対多リレーション
     *
     * @return BelongsToMany<Timetable, $this>
     */
    public function timetables(): BelongsToMany
    {
        return $this->belongsToMany(Timetable::class, 'timetable_lanes')
            ->withPivot(['id', 'lead_staff_id', 'display_order', 'notes'])
            ->withTimestamps();
    }

    /**
     * 日別編成経由で紐づく全作業タスク一覧とのリレーション
     *
     * @return HasManyThrough<TimetableTask, TimetableLane, $this>
     */
    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(TimetableTask::class, TimetableLane::class, 'lane_id', 'timetable_lane_id');
    }
}
