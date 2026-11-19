<?php

namespace App\Presenters;

use App\Enums\EquipmentPlanStatus;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;

class EquipmentLogisticsPlanPresenter
{
    public function __construct(
        protected EquipmentLogisticsPlan $plan
    ) {}

    /**
     * 一覧テーブル行のハイライト用CSSクラスを取得
     */
    public function rowHighlightClass(): string
    {
        if ($this->plan->isCompleted()) {
            return 'bg-emerald-50/15 hover:bg-slate-50/70';
        }

        if ($this->plan->isCancelled()) {
            return 'bg-slate-50/50 text-slate-400 hover:bg-slate-100/50';
        }

        if ($this->plan->needsTimeWarning()) {
            return 'bg-amber-50/70 hover:bg-amber-100/60';
        }

        if ($this->plan->status_enum === EquipmentPlanStatus::InProgress) {
            return 'bg-sky-50/25 hover:bg-slate-50/70';
        }

        return 'hover:bg-slate-50/70';
    }

    /**
     * フェーズバッジのCSSクラスを取得
     */
    public function phaseBadgeClass(): string
    {
        return match ($this->plan->day_phase) {
            'pre_day' => 'bg-purple-50 text-purple-700 border-purple-200',
            'day1_morning' => 'bg-sky-50 text-sky-700 border-sky-200',
            'day1_evening' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'day2_morning' => 'bg-teal-50 text-teal-700 border-teal-200',
            'day2_evening' => 'bg-amber-50 text-amber-700 border-amber-200',
            'post_day' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    /**
     * モーダル表示・編集用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(?Timetable $timetable = null): array
    {
        return [
            'id' => $this->plan->id,
            'equipment_id' => $this->plan->equipment_id,
            'equipment_name' => $this->plan->equipment?->name ?? '',
            'status' => $this->plan->status,
            'status_label' => $this->plan->status_label,
            'day_phase' => $this->plan->day_phase,
            'phase_label' => $this->plan->phase_label,
            'phase_badge_class' => $this->phaseBadgeClass(),
            'action_type' => $this->plan->action_type,
            'action_label' => $this->plan->action_label,
            'title' => $this->plan->title,
            'target_date' => $this->plan->target_date?->format('Y-m-d'),
            'start_time' => $this->plan->start_time ? substr($this->plan->start_time, 0, 5) : '',
            'end_time' => $this->plan->end_time ? substr($this->plan->end_time, 0, 5) : '',
            'formatted_time_range' => $this->plan->formatted_time_range,
            'from_location_id' => $this->plan->from_location_id,
            'from_location' => $this->plan->from_location,
            'resolved_from_location' => $this->plan->resolved_from_location,
            'to_location_id' => $this->plan->to_location_id,
            'to_location' => $this->plan->to_location,
            'resolved_to_location' => $this->plan->resolved_to_location,
            'quantity' => $this->plan->quantity,
            'quantity_note' => $this->plan->quantity_note,
            'formatted_quantity' => $this->plan->formatted_quantity,
            'operator_type' => $this->plan->operator_type,
            'operator_label' => $this->plan->operator_label,
            'witness_required' => $this->plan->witness_required ? 1 : 0,
            'witness_staff_id' => $this->plan->witness_staff_id,
            'witness_staff_name' => $this->plan->witnessStaff?->name ?? '',
            'notes' => $this->plan->notes,
            'update_url' => $this->plan->updateUrl($timetable),
        ];
    }
}
