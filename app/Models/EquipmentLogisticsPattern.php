<?php

namespace App\Models;

use Database\Factories\EquipmentLogisticsPatternFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'target_type',
    'description',
    'display_order',
    'created_by',
])]
class EquipmentLogisticsPattern extends Model
{
    /** @use HasFactory<EquipmentLogisticsPatternFactory> */
    use HasFactory;

    /**
     * 推奨資材区分定義一覧
     *
     * @var array<string, string>
     */
    public const array TARGET_TYPES = [
        'any' => 'すべて（共通）',
        'committee' => '実行委員会管理（自前）',
        'rental' => 'イベント会社レンタル',
        'personal' => '私物（個人持ち込み）',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    /**
     * 推奨資材区分ラベルアクセサ
     */
    public function getTargetTypeLabelAttribute(): string
    {
        return self::TARGET_TYPES[$this->target_type] ?? $this->target_type;
    }

    /**
     * パターン構成ステップ一覧とのリレーション
     */
    public function steps(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPatternStep::class, 'pattern_id')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }
}

