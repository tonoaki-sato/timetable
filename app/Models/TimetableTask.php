<?php

namespace App\Models;

use App\Enums\TimetableTaskStatus;
use App\Enums\TimetableTaskType;
use App\Presenters\TimetableTaskPresenter;
use Database\Factories\TimetableTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'timetable_id',
    'timetable_lane_id',
    'task_type',
    'title',
    'content',
    'location',
    'start_time',
    'end_time',
    'required_people_count',
    'is_at_least',
    'is_critical',
    'status',
])]
class TimetableTask extends Model
{
    /** @use HasFactory<TimetableTaskFactory> */
    use HasFactory;

    protected ?TimetableTaskPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): TimetableTaskPresenter
    {
        return $this->presenterInstance ??= new TimetableTaskPresenter($this);
    }

    /**
     * ステータス Enum
     */
    public function getStatusEnumAttribute(): TimetableTaskStatus
    {
        return TimetableTaskStatus::tryFrom((string) $this->status) ?? TimetableTaskStatus::NotStarted;
    }

    /**
     * タスク種別 Enum（定常 / スポット）
     */
    public function getTaskTypeEnumAttribute(): TimetableTaskType
    {
        return TimetableTaskType::tryFrom((string) $this->task_type) ?? TimetableTaskType::Spot;
    }

    /**
     * 定常タスクかどうか
     */
    public function isRoutine(): bool
    {
        return $this->task_type_enum === TimetableTaskType::Routine;
    }

    /**
     * スポットタスクかどうか
     */
    public function isSpot(): bool
    {
        return $this->task_type_enum === TimetableTaskType::Spot;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_people_count' => 'integer',
            'is_at_least' => 'boolean',
            'is_critical' => 'boolean',
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
     * 紐づく日別担当班編成とのリレーション
     *
     * @return BelongsTo<TimetableLane, $this>
     */
    public function timetableLane(): BelongsTo
    {
        return $this->belongsTo(TimetableLane::class, 'timetable_lane_id');
    }

    /**
     * アサイン中間情報とのリレーション
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskStaffAssignment::class);
    }

    /**
     * アサインされたスタッフ一覧とのリレーション
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'task_staff_assignments')
            ->withPivot(['id', 'role_description', 'attendance_status', 'notes'])
            ->withTimestamps();
    }

    /**
     * 有効アサイン済み人数（欠席以外）
     */
    public function getAssignedCountAttribute(): int
    {
        if ($this->relationLoaded('assignments')) {
            return $this->assignments->where('attendance_status', '!=', 'absent')->count();
        }

        return $this->assignments()->where('attendance_status', '!=', 'absent')->count();
    }

    /**
     * 人員充足判定（「〇〇名以上」の場合は基準値以上で充足）
     */
    public function getIsSatisfiedAttribute(): bool
    {
        return $this->assigned_count >= $this->required_people_count;
    }

    /**
     * 不足人数（0以上の数値）
     */
    public function getShortageCountAttribute(): int
    {
        return max(0, $this->required_people_count - $this->assigned_count);
    }

    /**
     * ステータスによるスコープ
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * 人員充足率（0〜100%）
     */
    public function getFulfillmentPercentAttribute(): int
    {
        return $this->required_people_count > 0
            ? min(100, (int) round(($this->assigned_count / $this->required_people_count) * 100))
            : 100;
    }
}
