<?php

namespace App\Models;

use App\Enums\LaneRequestStatus;
use App\Enums\LaneRequestType;
use App\Support\NavigationCacheInvalidatingBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'timetable_id',
    'lane_id',
    'timetable_task_id',
    'requested_by_staff_id',
    'request_type',
    'requested_count',
    'equipment_name',
    'reason',
    'status',
    'resolved_by_user_id',
    'resolved_at',
])]
#[UseEloquentBuilder(NavigationCacheInvalidatingBuilder::class)]
class LaneRequest extends Model
{
    /**
     * 要請・申請種別一覧
     *
     * @var array<string, string>
     */
    public const array TYPES = [
        'staffing' => '人員応援要請',
        'equipment' => '必要機材申請',
    ];

    /**
     * 対応ステータス定義一覧
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'pending' => '要請・申請中',
        'approved' => '承認・手配済',
        'resolved' => '対応完了',
        'rejected' => '見送り・却下',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_count' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * 親タイムテーブルとのリレーション
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 対象班（レーン）とのリレーション
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class);
    }

    /**
     * 対象作業タスクとのリレーション
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(TimetableTask::class, 'timetable_task_id');
    }

    /**
     * 申請した班長スタッフとのリレーション
     */
    public function requestedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_staff_id');
    }

    /**
     * 要請・申請を処理した管理者ユーザーとのリレーション
     */
    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    /**
     * 要請・申請種別 Enum
     */
    public function getRequestTypeEnumAttribute(): ?LaneRequestType
    {
        return LaneRequestType::tryFrom((string) $this->request_type);
    }

    /**
     * 対応ステータス Enum
     */
    public function getStatusEnumAttribute(): ?LaneRequestStatus
    {
        return LaneRequestStatus::tryFrom((string) $this->status);
    }

    /**
     * 種別の日本語表示ラベル
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->request_type_enum?->label() ?? (string) $this->request_type;
    }

    /**
     * ステータスの日本語表示ラベル
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status_enum?->label() ?? (string) $this->status;
    }
}
