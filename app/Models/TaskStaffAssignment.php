<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Presenters\TaskStaffAssignmentPresenter;
use Database\Factories\TaskStaffAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'timetable_task_id',
    'staff_id',
    'role_description',
    'attendance_status',
    'notes',
])]
class TaskStaffAssignment extends Model
{
    /** @use HasFactory<TaskStaffAssignmentFactory> */
    use HasFactory;

    protected ?TaskStaffAssignmentPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): TaskStaffAssignmentPresenter
    {
        return $this->presenterInstance ??= new TaskStaffAssignmentPresenter($this);
    }

    /**
     * 対象タスクとのリレーション
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(TimetableTask::class, 'timetable_task_id');
    }

    /**
     * 対象スタッフとのリレーション
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * 出欠ステータス Enum
     */
    public function getAttendanceStatusEnumAttribute(): ?AttendanceStatus
    {
        return AttendanceStatus::tryFrom((string) $this->attendance_status);
    }

    /**
     * アサインまたは親タスクが完了済みかどうか
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->attendance_status === 'completed' || $this->task?->status === 'completed';
    }

    /**
     * 現在進行中タスクと一致するか判定
     */
    public function isCurrent(?self $currentTask): bool
    {
        return $currentTask !== null && $currentTask->id === $this->id;
    }

    /**
     * 次の予定タスクと一致するか判定
     */
    public function isNext(?self $nextTask): bool
    {
        return $nextTask !== null && $nextTask->id === $this->id;
    }
}
