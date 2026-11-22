<?php

namespace App\Models;

use App\Enums\LaneRequestStatus;
use App\Enums\LaneRequestType;
use App\Presenters\LaneRequestPresenter;
use App\Support\NavigationCacheInvalidatingBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'timetable_id',
    'timetable_lane_id',
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
    protected ?LaneRequestPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): LaneRequestPresenter
    {
        return $this->presenterInstance ??= new LaneRequestPresenter($this);
    }

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
     * 対象日別担当班編成とのリレーション
     *
     * @return BelongsTo<TimetableLane, $this>
     */
    public function timetableLane(): BelongsTo
    {
        return $this->belongsTo(TimetableLane::class, 'timetable_lane_id');
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
}
