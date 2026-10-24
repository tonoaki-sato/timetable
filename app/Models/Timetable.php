<?php

namespace App\Models;

use Database\Factories\TimetableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * 紐づく担当班（レーン）一覧とのリレーション
     */
    public function lanes(): HasMany
    {
        return $this->hasMany(Lane::class)->orderBy('display_order', 'asc');
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
                    'is_core' => $laneData['is_core'] ?? false,
                    'is_active' => true,
                    'lead_staff_id' => null,
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
}
