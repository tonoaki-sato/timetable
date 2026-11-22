<?php

namespace App\Presenters;

use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;

class EquipmentLogisticsPatternStepPresenter
{
    public function __construct(
        protected EquipmentLogisticsPatternStep $step
    ) {}

    /**
     * フェーズラベルを取得
     */
    public function phaseLabel(): string
    {
        return $this->step->day_phase_enum->label();
    }

    /**
     * アクションラベルを取得
     */
    public function actionLabel(): string
    {
        return $this->step->action_type_enum->label();
    }

    /**
     * 作業主体ラベルを取得
     */
    public function operatorTypeLabel(): string
    {
        return $this->step->operator_type_enum->label();
    }

    /**
     * 移動元表示ラベルを取得
     */
    public function fromLocationDisplay(): string
    {
        if (filled($this->step->from_location)) {
            return (string) $this->step->from_location;
        }

        return $this->step->fromLocationModel?->name ?? '（資材定常保管場所）';
    }

    /**
     * 移動先表示ラベルを取得
     */
    public function toLocationDisplay(): string
    {
        if (filled($this->step->to_location)) {
            return (string) $this->step->to_location;
        }

        return $this->step->toLocationModel?->name ?? '（現地指定）';
    }

    /**
     * ステップ編集モーダル用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(EquipmentLogisticsPattern $pattern): array
    {
        return [
            'id' => $this->step->id,
            'pattern_id' => $pattern->id,
            'pattern_name' => $pattern->name,
            'day_phase' => $this->step->day_phase,
            'action_type' => $this->step->action_type,
            'title' => $this->step->title,
            'from_location' => $this->step->from_location ?? '',
            'from_location_id' => $this->step->from_location_id,
            'from_location_name' => $this->step->fromLocationModel?->name ?? '',
            'from_location_display' => $this->fromLocationDisplay(),
            'to_location' => $this->step->to_location ?? '',
            'to_location_id' => $this->step->to_location_id,
            'to_location_name' => $this->step->toLocationModel?->name ?? '',
            'to_location_display' => $this->toLocationDisplay(),
            'operator_type' => $this->step->operator_type,
            'witness_required' => (bool) $this->step->witness_required,
            'display_order' => $this->step->display_order,
            'notes' => $this->step->notes ?? '',
            'update_url' => route('equipment-patterns.steps.update', [$pattern, $this->step]),
            'delete_url' => route('equipment-patterns.steps.destroy', [$pattern, $this->step]),
        ];
    }
}
