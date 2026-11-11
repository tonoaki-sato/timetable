<?php

namespace App\Models;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
use App\Enums\EquipmentPlanStatus;
use App\Services\LocationAvailabilityService;
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

    /**
     * フェーズ定義一覧（時系列7区分）
     *
     * @var array<string, string>
     */
    public const array DAY_PHASES = [
        'other' => 'その他（事前準備等）',
        'pre_day' => '前日（準備日）',
        'day1_morning' => '1日目 朝（設置）',
        'day1_evening' => '1日目 夜（夜間保管移動）',
        'day2_morning' => '2日目 朝（設置）',
        'day2_evening' => '2日目 夜（倉庫格納・回収待ち移動）',
        'post_day' => '翌日（業者回収）',
    ];

    /**
     * アクション種別定義一覧
     *
     * @var array<string, string>
     */
    public const array ACTION_TYPES = [
        'storage_out' => '倉庫出し・移動',
        'vendor_receive' => '業者受取・立会',
        'staging_move' => '中継・待機移動',
        'setup' => '会場設置',
        'night_storage' => '夜間保管移動',
        'return_storage' => '倉庫返還・格納',
        'collection_staging' => '業者回収待ち集約',
        'vendor_setup' => 'イベント会社搬入・設営',
        'vendor_pickup' => 'イベント会社解体・搬出回収',
        'other' => 'その他作業',
    ];

    /**
     * 作業主体定義一覧
     *
     * @var array<string, string>
     */
    public const array OPERATOR_TYPES = [
        'committee' => '委員会スタッフ',
        'event_company' => 'イベント会社',
    ];

    /**
     * ステータス定義一覧
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'planned' => '計画中',
        'in_progress' => '進行中',
        'completed' => '完了',
        'cancelled' => '中止',
    ];

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
     * ステータスラベルアクセサ
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status_enum->label();
    }

    /**
     * 作業主体ラベルアクセサ
     */
    public function getOperatorLabelAttribute(): string
    {
        return $this->operator_type_enum->label();
    }

    /**
     * 移動元表示名アクセサ
     */
    public function getResolvedFromLocationAttribute(): string
    {
        return $this->fromLocationModel?->name ?? $this->from_location ?: '（指定なし）';
    }

    /**
     * 移動先表示名アクセサ
     */
    public function getResolvedToLocationAttribute(): string
    {
        return $this->toLocationModel?->name ?? $this->to_location ?: '（指定なし）';
    }

    /**
     * 時間帯表示フォーマットアクセサ
     */
    public function getFormattedTimeRangeAttribute(): string
    {
        if (! $this->start_time && ! $this->end_time) {
            return '';
        }

        $start = $this->start_time ? substr($this->start_time, 0, 5) : '';
        $end = $this->end_time ? substr($this->end_time, 0, 5) : '';

        if ($start && $end) {
            return "{$start}〜{$end}";
        }

        return $start ?: $end;
    }

    /**
     * ステップ数量・数量補足の表示フォーマットアクセサ
     * - quantity と quantity_note の両方がある場合: "{約}{quantity}{unit}（{quantity_note}）"
     * - quantity のみの場合: "{約}{quantity}{unit}"
     * - quantity が null で quantity_note のみの場合: "{quantity_note}"
     */
    public function getFormattedQuantityAttribute(): string
    {
        $equipment = $this->equipment;
        $prefix = $equipment?->is_approximate_quantity ? '約' : '';
        $unit = $equipment?->unit ?? '個';

        if ($this->quantity !== null && $this->quantity_note) {
            return "{$prefix}{$this->quantity}{$unit}（{$this->quantity_note}）";
        }

        if ($this->quantity !== null) {
            return "{$prefix}{$this->quantity}{$unit}";
        }

        if ($this->quantity_note) {
            return $this->quantity_note;
        }

        return '';
    }

    /**
     * 完了済み判定
     */
    public function isCompleted(): bool
    {
        return $this->status === EquipmentPlanStatus::Completed || $this->status === 'completed';
    }

    /**
     * 中止済み判定
     */
    public function isCancelled(): bool
    {
        return $this->status === EquipmentPlanStatus::Cancelled || $this->status === 'cancelled';
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
     * 一覧テーブル行のハイライト用CSSクラスアクセサ
     */
    public function getRowHighlightClassAttribute(): string
    {
        if ($this->isCompleted()) {
            return 'bg-emerald-50/15 hover:bg-slate-50/70';
        }

        if ($this->isCancelled()) {
            return 'bg-slate-50/50 text-slate-400 hover:bg-slate-100/50';
        }

        if ($this->needsTimeWarning()) {
            return 'bg-amber-50/70 hover:bg-amber-100/60';
        }

        if ($this->status === EquipmentPlanStatus::InProgress || $this->status === 'in_progress') {
            return 'bg-sky-50/25 hover:bg-slate-50/70';
        }

        return 'hover:bg-slate-50/70';
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
     * 移動先拠点の貸切・利用可能期間（中抜け時間帯含む）を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkToLocationAvailability(): ?array
    {
        return app(LocationAvailabilityService::class)->checkToLocationAvailability($this);
    }

    /**
     * 移動元拠点の搬出期限・中抜け時間帯を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkFromLocationAvailability(): ?array
    {
        return app(LocationAvailabilityService::class)->checkFromLocationAvailability($this);
    }

    /**
     * 移動元・移動先のすべての拠点制約警告リストを取得
     *
     * @return array<int, array{level: string, message: string}>
     */
    public function getLocationWarningsAttribute(): array
    {
        return app(LocationAvailabilityService::class)->getLocationWarnings($this);
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

    /**
     * フェーズバッジのCSSクラスアクセサ
     */
    public function getPhaseBadgeClassAttribute(): string
    {
        return match ($this->day_phase) {
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
     * ステータス更新アクションのURLを取得
     */
    public function updateStatusUrl(?Timetable $timetable = null): string
    {
        return $timetable
            ? route('timetables.logistics.plans.update-status', [$timetable, $this])
            : route('logistics.plans.update-status', $this);
    }

    /**
     * ステップ更新アクションのURLを取得
     */
    public function updateUrl(?Timetable $timetable = null): string
    {
        return $timetable
            ? route('timetables.logistics.plans.update', [$timetable, $this])
            : route('logistics.plans.update', $this);
    }

    /**
     * ステップ削除アクションのURLを取得
     */
    public function destroyUrl(?Timetable $timetable = null): string
    {
        return $timetable
            ? route('timetables.logistics.plans.destroy', [$timetable, $this])
            : route('logistics.plans.destroy', $this);
    }

    /**
     * モーダル表示・編集用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(?Timetable $timetable = null): array
    {
        return [
            'id' => $this->id,
            'equipment_id' => $this->equipment_id,
            'equipment_name' => $this->equipment?->name ?? '',
            'status' => $this->status,
            'status_label' => $this->status_label,
            'day_phase' => $this->day_phase,
            'phase_label' => $this->phase_label,
            'phase_badge_class' => $this->phase_badge_class,
            'action_type' => $this->action_type,
            'action_label' => $this->action_label,
            'title' => $this->title,
            'target_date' => $this->target_date?->format('Y-m-d'),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : '',
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : '',
            'formatted_time_range' => $this->formatted_time_range,
            'from_location_id' => $this->from_location_id,
            'from_location' => $this->from_location,
            'resolved_from_location' => $this->resolved_from_location,
            'to_location_id' => $this->to_location_id,
            'to_location' => $this->to_location,
            'resolved_to_location' => $this->resolved_to_location,
            'quantity' => $this->quantity,
            'quantity_note' => $this->quantity_note,
            'formatted_quantity' => $this->formatted_quantity,
            'operator_type' => $this->operator_type,
            'operator_label' => $this->operator_label,
            'witness_required' => $this->witness_required ? 1 : 0,
            'witness_staff_id' => $this->witness_staff_id,
            'witness_staff_name' => $this->witnessStaff?->name ?? '',
            'notes' => $this->notes,
            'update_url' => $this->updateUrl($timetable),
        ];
    }
}
