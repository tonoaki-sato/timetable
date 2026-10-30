<?php

namespace App\Models;

use Database\Factories\LogisticsLocationFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'type',
    'is_all_day',
    'available_from',
    'available_to',
    'description',
    'display_order',
    'is_active',
    'created_by',
])]
class LogisticsLocation extends Model
{
    /** @use HasFactory<LogisticsLocationFactory> */
    use HasFactory;

    /**
     * 拠点種別定義一覧
     *
     * @var array<string, string>
     */
    public const array TYPES = [
        'parking' => '駐車場',
        'facility' => '施設・倉庫',
        'open_space' => '空きスペース',
        'other' => 'その他',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_all_day' => 'boolean',
            'is_active' => 'boolean',
            'available_from' => 'datetime',
            'available_to' => 'datetime',
            'display_order' => 'integer',
        ];
    }

    /**
     * 指定された日時にこの拠点・駐車場が利用（貸切）可能か判定
     */
    public function isAvailableAt(?DateTimeInterface $dateTime = null): bool
    {
        if ($this->is_all_day) {
            return true;
        }

        if (! $dateTime) {
            return true;
        }

        if ($this->available_from && $dateTime < $this->available_from) {
            return false;
        }

        if ($this->available_to && $dateTime > $this->available_to) {
            return false;
        }

        return true;
    }

    /**
     * 種別の日本語ラベル
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * 有効拠点のみ抽出するスコープ
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
