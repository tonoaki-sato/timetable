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
    'from_location_id',
    'to_location_id',
    'operator_type',
    'witness_required',
    'notes',
    'display_order',
])]
class EquipmentLogisticsPatternStep extends Model
{
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
     * 移動元拠点モデルとのリレーション
     */
    public function fromLocationModel(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * 移動先拠点モデルとのリレーション
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
     * 移動元表示ラベルアクセサ
     */
    public function getFromLocationDisplayAttribute(): string
    {
        return $this->fromLocationModel?->name ?? '（未指定）';
    }

    /**
     * 移動先表示ラベルアクセサ
     */
    public function getToLocationDisplayAttribute(): string
    {
        return $this->toLocationModel?->name ?? '（未指定）';
    }
}
