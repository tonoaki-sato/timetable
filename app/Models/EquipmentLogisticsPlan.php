<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EquipmentLogisticsPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
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
        return self::DAY_PHASES[$this->day_phase] ?? $this->day_phase;
    }

    /**
     * アクションラベルアクセサ
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_TYPES[$this->action_type] ?? $this->action_type;
    }

    /**
     * ステータスラベルアクセサ
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * 作業主体ラベルアクセサ
     */
    public function getOperatorLabelAttribute(): string
    {
        return self::OPERATOR_TYPES[$this->operator_type] ?? $this->operator_type;
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
        return $this->status === 'completed';
    }

    /**
     * 移動先拠点の貸切・利用可能期間（中抜け時間帯含む）を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkToLocationAvailability(): ?array
    {
        $location = $this->toLocationModel;
        if (! $location || $location->is_all_day || ! $this->target_date) {
            return null;
        }

        if (! $location->is_active) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        $schedules = $location->relationLoaded('schedules')
            ? $location->schedules
            : $location->schedules()->orderBy('start_at', 'asc')->get();

        if ($schedules->isEmpty()) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        [$startDateTime, $endDateTime] = $this->resolveStepDateTimeRange();
        if (! $startDateTime || ! $endDateTime) {
            return null;
        }

        // 実施時間帯を完全に包含するスロットを検索
        $matched = $schedules->first(function (LocationSchedule $schedule) use ($startDateTime, $endDateTime) {
            return $schedule->start_at <= $startDateTime && $endDateTime <= $schedule->end_at;
        });

        if ($matched) {
            if (! $matched->isAvailable()) {
                return [
                    'level' => 'warning',
                    'message' => "拠点「{$location->name}」の該当時間帯はまだ利用確保（利用可）になっていません。",
                ];
            }

            return null;
        }

        $minStart = Carbon::parse($schedules->min('start_at'));
        if ($startDateTime < $minStart) {
            $fromStr = $minStart->format('n/j H:i');

            return [
                'level' => 'warning',
                'message' => "移動先「{$location->name}」の利用開始（{$fromStr}）前です。利用可能になってから搬入してください。",
            ];
        }

        return [
            'level' => 'warning',
            'message' => "移動先「{$location->name}」の利用可能時間外（またはイベントブース使用時間帯）です。利用可能枠を確認してください。",
        ];
    }

    /**
     * 移動元拠点の搬出期限・中抜け時間帯を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkFromLocationAvailability(): ?array
    {
        $location = $this->fromLocationModel;
        if (! $location || $location->is_all_day || ! $this->target_date) {
            return null;
        }

        if (! $location->is_active) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        $schedules = $location->relationLoaded('schedules')
            ? $location->schedules
            : $location->schedules()->orderBy('start_at', 'asc')->get();

        if ($schedules->isEmpty()) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        [$startDateTime, $endDateTime] = $this->resolveStepDateTimeRange();
        if (! $startDateTime || ! $endDateTime) {
            return null;
        }

        $matched = $schedules->first(function (LocationSchedule $schedule) use ($startDateTime, $endDateTime) {
            return $schedule->start_at <= $startDateTime && $endDateTime <= $schedule->end_at;
        });

        if ($matched) {
            if (! $matched->isAvailable()) {
                return [
                    'level' => 'warning',
                    'message' => "拠点「{$location->name}」の該当時間帯はまだ利用確保（利用可）になっていません。",
                ];
            }

            return null;
        }

        return [
            'level' => 'warning',
            'message' => "移動元「{$location->name}」の利用終了時刻を超過、またはイベントブース使用時間帯に入っています。利用可能時間内に搬出を完了してください。",
        ];
    }

    /**
     * 移動元・移動先のすべての拠点制約警告リストを取得
     *
     * @return array<int, array{level: string, message: string}>
     */
    public function getLocationWarningsAttribute(): array
    {
        return array_values(array_filter([
            $this->checkToLocationAvailability(),
            $this->checkFromLocationAvailability(),
        ]));
    }

    /**
     * ステップの開始・終了予定日時を解決
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveStepDateTimeRange(): array
    {
        if (! $this->target_date) {
            return [null, null];
        }

        $dateStr = $this->target_date->format('Y-m-d');
        $startTimeStr = $this->start_time ?: '12:00';
        $endTimeStr = $this->end_time ?: $startTimeStr;

        try {
            $start = Carbon::parse($dateStr.' '.$startTimeStr);
            $end = Carbon::parse($dateStr.' '.$endTimeStr);
            if ($end < $start) {
                $end = $start->copy();
            }

            return [$start, $end];
        } catch (\Throwable) {
            return [null, null];
        }
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
