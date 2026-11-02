<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pattern_id',
    'day_phase',
    'action_type',
    'title',
    'quantity_rule',
    'quantity_note',
    'start_time',
    'end_time',
    'from_location_role',
    'to_location_role',
    'from_location_id',
    'to_location_id',
    'from_location',
    'to_location',
    'operator_type',
    'witness_required',
    'notes',
    'display_order',
])]
class EquipmentLogisticsPatternStep extends Model
{
    /**
     * 数量計算ルール一覧
     *
     * @var array<string, string>
     */
    public const array QUANTITY_RULES = [
        'all' => '全量（資材数量そのまま）',
        'half_1' => '前半分割（約半分①）',
        'half_2' => '後半分割（約半分②）',
        'custom' => '数値なし（補足テキストのみ）',
    ];

    /**
     * 動的拠点ロール一覧
     *
     * @var array<string, string>
     */
    public const array LOCATION_ROLES = [
        'equipment_storage' => '資材の定常保管場所',
        'bansho_space' => '番所前スペース（一時集約・荷捌き）',
        'staging_parking' => '前日中継駐車場（適用時に選択）',
        'night_parking_1' => '1日目夜間保管場所①（適用時に選択）',
        'night_parking_2' => '1日目夜間保管場所②（適用時に選択）',
        'fixed' => '固定拠点指定',
        'text' => '自由テキスト指定（会場設置・業者車両等）',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'witness_required' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * 所属パターンとのリレーション
     */
    public function pattern(): BelongsTo
    {
        return $this->belongsTo(EquipmentLogisticsPattern::class, 'pattern_id');
    }

    /**
     * 固定移動元拠点モデルとのリレーション
     */
    public function fromLocationModel(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * 固定移動先拠点モデルとのリレーション
     */
    public function toLocationModel(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * フェーズラベルアクセサ
     */
    public function getPhaseLabelAttribute(): string
    {
        return EquipmentLogisticsPlan::DAY_PHASES[$this->day_phase] ?? $this->day_phase;
    }

    /**
     * アクションラベルアクセサ
     */
    public function getActionLabelAttribute(): string
    {
        return EquipmentLogisticsPlan::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }

    /**
     * 数量ルールラベルアクセサ
     */
    public function getQuantityRuleLabelAttribute(): string
    {
        return self::QUANTITY_RULES[$this->quantity_rule] ?? $this->quantity_rule;
    }

    /**
     * 移動元表示ラベルアクセサ
     */
    public function getFromLocationDisplayAttribute(): string
    {
        return match ($this->from_location_role) {
            'equipment_storage' => '📦 定常保管場所',
            'bansho_space' => '⛺ 番所前スペース',
            'staging_parking' => '🅿️ 前日中継駐車場',
            'night_parking_1' => '🌙 夜間保管場所①',
            'night_parking_2' => '🌙 夜間保管場所②',
            'fixed' => $this->fromLocationModel?->name ?? $this->from_location ?? '（固定拠点）',
            default => $this->from_location ?: '（指定なし）',
        };
    }

    /**
     * 移動先表示ラベルアクセサ
     */
    public function getToLocationDisplayAttribute(): string
    {
        return match ($this->to_location_role) {
            'equipment_storage' => '📦 定常保管場所',
            'bansho_space' => '⛺ 番所前スペース',
            'staging_parking' => '🅿️ 前日中継駐車場',
            'night_parking_1' => '🌙 夜間保管場所①',
            'night_parking_2' => '🌙 夜間保管場所②',
            'fixed' => $this->toLocationModel?->name ?? $this->to_location ?? '（固定拠点）',
            default => $this->to_location ?: '（指定なし）',
        };
    }
}

