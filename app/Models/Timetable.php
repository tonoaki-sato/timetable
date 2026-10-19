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
        ];
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
