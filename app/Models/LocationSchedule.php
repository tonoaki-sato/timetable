<?php

namespace App\Models;

use Database\Factories\LocationScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'location_id',
    'start_at',
    'end_at',
    'availability_status',
    'title',
    'note',
])]
class LocationSchedule extends Model
{
    /** @use HasFactory<LocationScheduleFactory> */
    use HasFactory;

    /**
     * 拠点確保ステータス一覧（シンプルな2択）
     *
     * @var array<string, string>
     */
    public const array AVAILABILITY_STATUSES = [
        'available' => '利用可（確保済）',
        'unavailable' => '未確保（調整中）',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 所属する拠点
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * 確保ステータスの日本語ラベル
     */
    public function getAvailabilityStatusLabelAttribute(): string
    {
        return self::AVAILABILITY_STATUSES[$this->availability_status] ?? $this->availability_status;
    }

    /**
     * 利用可（確保済）状態か判定
     */
    public function isAvailable(): bool
    {
        return $this->availability_status === 'available';
    }

    /**
     * 確保ステータスのバッジCSSクラス
     */
    public function getAvailabilityStatusBadgeClassAttribute(): string
    {
        return match ($this->availability_status) {
            'available' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'unavailable' => 'bg-amber-100 text-amber-800 border-amber-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    /**
     * 開始日時順ソート
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('start_at', 'asc')->orderBy('id', 'asc');
    }
}
