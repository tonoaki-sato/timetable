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
    'start_time',
    'end_time',
    'from_location',
    'to_location',
    'from_location_id',
    'to_location_id',
    'operator_type',
    'witness_required',
    'witness_staff_id',
    'contact_info',
    'status',
    'notes',
    'display_order',
])]
class EquipmentLogisticsPlan extends Model
{
    /** @use HasFactory<EquipmentLogisticsPlanFactory> */
    use HasFactory;

    /**
     * フェーズ定義一覧
     *
     * @var array<string, string>
     */
    public const array DAY_PHASES = [
        'pre_day' => '前日（準備日）',
        'day1_morning' => '1日目 朝（設置）',
        'day1_evening' => '1日目 夜（夜間保管移動）',
        'day2_morning' => '2日目 朝（設置）',
        'day2_evening' => '2日目 夜（倉庫格納・回収待ち移動）',
        'post_day' => '翌日（業者回収）',
        'other' => 'その他',
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
        return $this->belongsTo(LogisticsLocation::class, 'from_location_id');
    }

    /**
     * 移動先拠点モデルとのリレーション
     */
    public function toLocationModel(): BelongsTo
    {
        return $this->belongsTo(LogisticsLocation::class, 'to_location_id');
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
     * 完了済み判定
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 移動先拠点の貸切・利用可能期間を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkToLocationAvailability(): ?array
    {
        $location = $this->toLocationModel;
        if (! $location || $location->is_all_day) {
            return null;
        }

        if (! $this->target_date) {
            return null;
        }

        $timeStr = $this->start_time ?: '12:00';
        try {
            $scheduledDateTime = Carbon::parse($this->target_date->format('Y-m-d').' '.$timeStr);
        } catch (\Throwable) {
            return null;
        }

        if ($location->available_from && $scheduledDateTime < $location->available_from) {
            $fromStr = $location->available_from->format('n/j H:i');

            return [
                'level' => 'warning',
                'message' => "移動先「{$location->name}」の貸切開始（{$fromStr}）前です。貸切完了後に搬入してください。",
            ];
        }

        if ($location->available_to && $scheduledDateTime > $location->available_to) {
            $toStr = $location->available_to->format('n/j H:i');

            return [
                'level' => 'warning',
                'message' => "移動先「{$location->name}」の貸切終了（{$toStr}）を超過しています。",
            ];
        }

        return null;
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
