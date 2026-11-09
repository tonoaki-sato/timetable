<?php

namespace App\Models;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
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

    public function getDayPhaseEnumAttribute(): EquipmentDayPhase
    {
        return EquipmentDayPhase::tryFrom((string) $this->day_phase) ?? EquipmentDayPhase::Other;
    }

    public function getActionTypeEnumAttribute(): EquipmentActionType
    {
        return EquipmentActionType::tryFrom((string) $this->action_type) ?? EquipmentActionType::Other;
    }

    public function getOperatorTypeEnumAttribute(): EquipmentOperatorType
    {
        return EquipmentOperatorType::tryFrom((string) $this->operator_type) ?? EquipmentOperatorType::Committee;
    }

    public function setDayPhaseAttribute(EquipmentDayPhase|string|null $value): void
    {
        $this->attributes['day_phase'] = $value instanceof EquipmentDayPhase ? $value->value : (string) $value;
    }

    public function setActionTypeAttribute(EquipmentActionType|string|null $value): void
    {
        $this->attributes['action_type'] = $value instanceof EquipmentActionType ? $value->value : (string) $value;
    }

    public function setOperatorTypeAttribute(EquipmentOperatorType|string|null $value): void
    {
        $this->attributes['operator_type'] = $value instanceof EquipmentOperatorType ? $value->value : (string) $value;
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
        return $this->day_phase_enum->label();
    }

    /**
     * アクションラベルアクセサ
     */
    public function getActionLabelAttribute(): string
    {
        return $this->action_type_enum->label();
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
