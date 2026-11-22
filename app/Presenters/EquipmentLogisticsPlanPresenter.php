<?php

namespace App\Presenters;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentPlanStatus;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;

class EquipmentLogisticsPlanPresenter
{
    public function __construct(
        protected EquipmentLogisticsPlan $plan
    ) {}

    /**
     * フェーズラベルを取得
     */
    public function phaseLabel(): string
    {
        return $this->plan->day_phase_enum->label();
    }

    /**
     * アクションラベルを取得
     */
    public function actionLabel(): string
    {
        return $this->plan->action_type_enum->label();
    }

    /**
     * ステータスラベルを取得
     */
    public function statusLabel(): string
    {
        return $this->plan->status_enum->label();
    }

    /**
     * 作業主体ラベルを取得
     */
    public function operatorLabel(): string
    {
        return $this->plan->operator_type_enum->label();
    }

    /**
     * 移動元表示名を取得
     */
    public function resolvedFromLocation(): string
    {
        return $this->plan->fromLocationModel?->name ?? $this->plan->from_location ?: '（指定なし）';
    }

    /**
     * 移動先表示名を取得
     */
    public function resolvedToLocation(): string
    {
        return $this->plan->toLocationModel?->name ?? $this->plan->to_location ?: '（指定なし）';
    }

    /**
     * 時間帯表示フォーマットを取得
     */
    public function formattedTimeRange(): string
    {
        if (! $this->plan->start_time && ! $this->plan->end_time) {
            return '';
        }

        $start = $this->plan->start_time ? substr($this->plan->start_time, 0, 5) : '';
        $end = $this->plan->end_time ? substr($this->plan->end_time, 0, 5) : '';

        if ($start && $end) {
            return "{$start}〜{$end}";
        }

        return $start ?: $end;
    }

    /**
     * ステップ数量・数量補足の表示フォーマットを取得
     */
    public function formattedQuantity(): string
    {
        $equipment = $this->plan->equipment;
        $prefix = $equipment?->is_approximate_quantity ? '約' : '';
        $unit = $equipment?->unit ?? '個';

        if ($this->plan->quantity !== null && $this->plan->quantity_note) {
            return "{$prefix}{$this->plan->quantity}{$unit}（{$this->plan->quantity_note}）";
        }

        if ($this->plan->quantity !== null) {
            return "{$prefix}{$this->plan->quantity}{$unit}";
        }

        if ($this->plan->quantity_note) {
            return (string) $this->plan->quantity_note;
        }

        return '';
    }

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
     * タイムラインガントブロックの配色CSSクラスを取得
     */
    public function ganttBadgeClass(): string
    {
        if ($this->plan->isCancelled()) {
            return 'bg-slate-100 border-slate-300 text-slate-500 opacity-75 line-through';
        }

        return match ($this->plan->action_type_enum) {
            EquipmentActionType::StorageOut => 'bg-sky-50 border-sky-300 text-sky-900',
            EquipmentActionType::VendorReceive => 'bg-purple-50 border-purple-300 text-purple-900',
            EquipmentActionType::StagingMove => 'bg-indigo-50 border-indigo-300 text-indigo-900',
            EquipmentActionType::Setup => 'bg-emerald-50 border-emerald-300 text-emerald-900',
            EquipmentActionType::NightStorage => 'bg-amber-50 border-amber-300 text-amber-900',
            EquipmentActionType::ReturnStorage => 'bg-teal-50 border-teal-300 text-teal-900',
            EquipmentActionType::CollectionStaging => 'bg-orange-50 border-orange-300 text-orange-900',
            EquipmentActionType::VendorSetup => 'bg-rose-50 border-rose-300 text-rose-900',
            EquipmentActionType::VendorPickup => 'bg-slate-100 border-slate-300 text-slate-800',
            EquipmentActionType::Other => 'bg-slate-50 border-slate-300 text-slate-800',
        };
    }

    /**
     * ステップ更新アクションのURLを取得
     */
    public function updateUrl(?Timetable $timetable = null): string
    {
        return route('timetables.logistics.plans.update', [$timetable ?? $this->plan->timetable_id, $this->plan]);
    }

    /**
     * ステップ削除アクションのURLを取得
     */
    public function destroyUrl(?Timetable $timetable = null): string
    {
        return route('timetables.logistics.plans.destroy', [$timetable ?? $this->plan->timetable_id, $this->plan]);
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
            'status_label' => $this->statusLabel(),
            'day_phase' => $this->plan->day_phase,
            'phase_label' => $this->phaseLabel(),
            'phase_badge_class' => $this->phaseBadgeClass(),
            'action_type' => $this->plan->action_type,
            'action_label' => $this->actionLabel(),
            'title' => $this->plan->title,
            'target_date' => $this->plan->target_date?->format('Y-m-d'),
            'start_time' => $this->plan->start_time ? substr($this->plan->start_time, 0, 5) : '',
            'end_time' => $this->plan->end_time ? substr($this->plan->end_time, 0, 5) : '',
            'formatted_time_range' => $this->formattedTimeRange(),
            'from_location_id' => $this->plan->from_location_id,
            'from_location' => $this->plan->from_location,
            'resolved_from_location' => $this->resolvedFromLocation(),
            'to_location_id' => $this->plan->to_location_id,
            'to_location' => $this->plan->to_location,
            'resolved_to_location' => $this->resolvedToLocation(),
            'quantity' => $this->plan->quantity,
            'quantity_note' => $this->plan->quantity_note,
            'formatted_quantity' => $this->formattedQuantity(),
            'operator_type' => $this->plan->operator_type,
            'operator_label' => $this->operatorLabel(),
            'witness_required' => $this->plan->witness_required ? 1 : 0,
            'witness_staff_id' => $this->plan->witness_staff_id,
            'witness_staff_name' => $this->plan->witnessStaff?->name ?? '',
            'notes' => $this->plan->notes,
            'update_url' => $this->updateUrl($timetable),
        ];
    }
}
