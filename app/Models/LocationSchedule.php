<?php

namespace App\Models;

use App\Presenters\LocationSchedulePresenter;
use Database\Factories\LocationScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'location_id',
    'start_at',
    'end_at',
    'availability_status',
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

    protected ?LocationSchedulePresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): LocationSchedulePresenter
    {
        return $this->presenterInstance ??= new LocationSchedulePresenter($this);
    }

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
     * 利用可（確保済）状態か判定
     */
    public function isAvailable(): bool
    {
        return $this->availability_status === 'available';
    }
}
