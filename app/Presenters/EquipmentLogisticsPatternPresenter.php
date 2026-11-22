<?php

namespace App\Presenters;

use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;

class EquipmentLogisticsPatternPresenter
{
    public function __construct(
        protected EquipmentLogisticsPattern $pattern
    ) {}

    /**
     * 推奨資材区分ラベルを取得
     */
    public function targetTypeLabel(): string
    {
        return EquipmentLogisticsPattern::TARGET_TYPES[$this->pattern->target_type] ?? (string) $this->pattern->target_type;
    }

    /**
     * 詳細モーダル等用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->pattern->id,
            'name' => $this->pattern->name,
            'target_type' => $this->pattern->target_type,
            'target_type_label' => $this->targetTypeLabel(),
            'description' => $this->pattern->description ?? '',
            'update_url' => route('equipment-patterns.update', $this->pattern),
            'steps' => $this->pattern->steps->map(function (EquipmentLogisticsPatternStep $step): array {
                $stepPresenter = $step->presenter();

                return [
                    'id' => $step->id,
                    'display_order' => $step->display_order,
                    'day_phase' => $step->day_phase,
                    'phase_label' => $stepPresenter->phaseLabel(),
                    'action_type' => $step->action_type,
                    'action_label' => $stepPresenter->actionLabel(),
                    'title' => $step->title,
                    'operator_type' => $step->operator_type,
                    'operator_type_label' => $stepPresenter->operatorTypeLabel(),
                    'from_location' => $step->from_location ?? '',
                    'from_location_id' => $step->from_location_id,
                    'from_location_display' => $stepPresenter->fromLocationDisplay(),
                    'to_location' => $step->to_location ?? '',
                    'to_location_id' => $step->to_location_id,
                    'to_location_display' => $stepPresenter->toLocationDisplay(),
                    'witness_required' => (bool) $step->witness_required,
                    'notes' => $step->notes ?? '',
                ];
            })->values()->all(),
        ];
    }
}
