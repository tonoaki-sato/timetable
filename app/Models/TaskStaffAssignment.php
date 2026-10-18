<?php

namespace App\Models;

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

    /**
     * 出欠ステータス定義一覧
     *
     * @var array<string, string>
     */
    public const array ATTENDANCE_STATUSES = [
        'assigned' => '配置済',
        'checked_in' => '現場着・受付済',
        'completed' => '完了',
        'absent' => '欠席・キャンセル',
    ];

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
     * 出欠ステータスの日本語表示
     */
    public function getAttendanceStatusLabelAttribute(): string
    {
        return self::ATTENDANCE_STATUSES[$this->attendance_status] ?? $this->attendance_status;
    }
}
