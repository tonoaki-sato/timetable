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
    'display_order',
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
     * 進捗ステータスバッジのCSSクラスアクセサ
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 line-through opacity-75',
            'in_progress' => 'bg-amber-50 text-amber-800 border-amber-300 ring-1 ring-amber-200',
            default => 'bg-white text-slate-700 border-slate-200',
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
     * モーダル編集用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'day_phase' => $this->day_phase,
            'action_type' => $this->action_type,
            'title' => $this->title,
            'target_date' => $this->target_date?->format('Y-m-d'),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : '',
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : '',
            'from_location_id' => $this->from_location_id,
            'from_location' => $this->from_location,
            'to_location_id' => $this->to_location_id,
            'to_location' => $this->to_location,
            'quantity' => $this->quantity,
            'quantity_note' => $this->quantity_note,
            'operator_type' => $this->operator_type,
            'witness_required' => $this->witness_required ? 1 : 0,
            'witness_staff_id' => $this->witness_staff_id,
            'notes' => $this->notes,
            'display_order' => $this->display_order,
        ];
    }
}
