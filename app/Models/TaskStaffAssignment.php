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
        'in_progress' => '着手中',
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

    /**
     * 出欠ステータスバッジのCSSクラスアクセサ
     */
    public function getAttendanceBadgeClassAttribute(): string
    {
        return match ($this->attendance_status) {
            'completed' => 'bg-emerald-100 text-emerald-700',
            'in_progress' => 'bg-indigo-100 text-indigo-700',
            'checked_in' => 'bg-sky-100 text-sky-700',
            'absent' => 'bg-rose-100 text-rose-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * マイシフト表示用の対象日ヘッダーラベル（例: '2026年10月11日（日）'）
     */
    public function getDateHeaderLabelAttribute(): string
    {
        $targetDate = $this->task?->timetable?->target_date;
        if (! $targetDate) {
            return '日付未設定';
        }
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$targetDate->dayOfWeek];

        return $targetDate->format('Y年m月d日')."（{$weekday}）";
    }

    /**
     * アサインまたは親タスクが完了済みかどうか
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->attendance_status === 'completed' || $this->task?->status === 'completed';
    }

    /**
     * マイシフトカードの詳細アコーディオンに表示する情報があるか判定
     */
    public function getHasDetailsAttribute(): bool
    {
        $task = $this->task;
        if (! $task) {
            return false;
        }
        $laneLeadStaff = $task->laneRelation?->leadStaff;

        return ! empty($task->content)
            || ! empty($this->notes)
            || ($laneLeadStaff && $laneLeadStaff->id !== $this->staff_id)
            || $task->assignments->count() > 1;
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
