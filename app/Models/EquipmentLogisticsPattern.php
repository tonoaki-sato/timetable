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

    /**
     * ステップが1件以上登録されているか
     */
    public function getHasStepsAttribute(): bool
    {
        return $this->steps->isNotEmpty();
    }

    /**
     * 編集モーダル用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_type' => $this->target_type,
            'description' => $this->description ?? '',
            'update_url' => route('equipment-patterns.update', $this),
            'steps' => $this->steps->map(fn (EquipmentLogisticsPatternStep $step) => [
                'id' => $step->id,
                'day_phase' => $step->day_phase,
                'action_type' => $step->action_type,
                'title' => $step->title,
                'operator_type' => $step->operator_type,
                'from_location_id' => $step->from_location_id,
                'to_location_id' => $step->to_location_id,
                'witness_required' => (bool) $step->witness_required,
                'notes' => $step->notes ?? '',
            ])->values()->all(),
        ];
    }
}
