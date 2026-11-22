<?php

namespace App\Models;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
use App\Enums\EquipmentPlanStatus;
use App\Presenters\EquipmentLogisticsPlanPresenter;
use Database\Factories\EquipmentLogisticsPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'timetable_id',
    'equipment_id',
    'target_date',
    'day_phase',
    'action_type',
    'title',
    'quantity',
    'quantity_note',
    'start_time',
    'end_time',
    'from_location',
    'to_location',
    'from_location_id',
    'to_location_id',
    'operator_type',
    'witness_required',
    'witness_staff_id',
    'status',
    'notes',
])]
class EquipmentLogisticsPlan extends Model
{
    /** @use HasFactory<EquipmentLogisticsPlanFactory> */
    use HasFactory;

    protected ?EquipmentLogisticsPlanPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): EquipmentLogisticsPlanPresenter
    {
        return $this->presenterInstance ??= new EquipmentLogisticsPlanPresenter($this);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'quantity' => 'integer',
            'witness_required' => 'boolean',
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

    public function getStatusEnumAttribute(): EquipmentPlanStatus
    {
        return EquipmentPlanStatus::tryFrom((string) $this->status) ?? EquipmentPlanStatus::Planned;
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

    public function setStatusAttribute(EquipmentPlanStatus|string|null $value): void
    {
        $this->attributes['status'] = $value instanceof EquipmentPlanStatus ? $value->value : (string) $value;
    }

    /**
     * 運行計画（タイムテーブル）とのリレーション
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 親資材とのリレーション
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
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
     * 立会・担当スタッフとのリレーション
     */
    public function witnessStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'witness_staff_id');
    }

    /**
     * 完了済み判定
     */
    public function isCompleted(): bool
    {
        return $this->status_enum === EquipmentPlanStatus::Completed;
    }

    /**
     * 中止済み判定
     */
    public function isCancelled(): bool
    {
        return $this->status_enum === EquipmentPlanStatus::Cancelled;
    }

    /**
     * 実施日または開始時刻が未指定かどうかを判定
     */
    public function isTimeUnspecified(): bool
    {
        return blank($this->target_date) || blank($this->start_time);
    }

    /**
     * 時間未指定の警告表示が必要かどうかを判定（完了・中止を除く）
     */
    public function needsTimeWarning(): bool
    {
        return $this->isTimeUnspecified() && ! $this->isCompleted() && ! $this->isCancelled();
    }

    /**
     * フェーズ優先・実施予定日時順（フェーズ順 → 日付・開始時刻・終了時刻の昇順、未設定は同フェーズ内末尾）でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        $phaseCases = [];
        foreach (EquipmentDayPhase::cases() as $index => $phase) {
            $order = $index + 1;
            $phaseCases[] = "WHEN '{$phase->value}' THEN {$order}";
        }

        $phaseColumn = $this->qualifyColumn('day_phase');
        $targetDateColumn = $this->qualifyColumn('target_date');
        $startTimeColumn = $this->qualifyColumn('start_time');
        $endTimeColumn = $this->qualifyColumn('end_time');
        $idColumn = $this->qualifyColumn('id');

        $phaseSql = sprintf('CASE %s %s ELSE 99 END ASC', $phaseColumn, implode(' ', $phaseCases));

        return $query->orderByRaw($phaseSql)
            ->orderByRaw("{$targetDateColumn} IS NULL ASC")
            ->orderBy($targetDateColumn, 'asc')
            ->orderByRaw("{$startTimeColumn} IS NULL ASC")
            ->orderBy($startTimeColumn, 'asc')
            ->orderByRaw("{$endTimeColumn} IS NULL ASC")
            ->orderBy($endTimeColumn, 'asc')
            ->orderBy($idColumn, 'asc');
    }

    /**
     * 特定の運行計画に属する計画を抽出
     */
    public function scopeForTimetable(Builder $query, Timetable|int $timetable): Builder
    {
        $id = $timetable instanceof Timetable ? $timetable->id : $timetable;

        return $query->where('timetable_id', $id);
    }

    /**
     * フェーズ絞り込みスコープ
     */
    public function scopePhase(Builder $query, ?string $phase): Builder
    {
        if ($phase) {
            return $query->where('day_phase', $phase);
        }

        return $query;
    }

    /**
     * ステータス絞り込みスコープ
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }
}
