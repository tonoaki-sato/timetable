<?php

namespace App\Presenters;

use App\Models\TaskStaffAssignment;

class TaskStaffAssignmentPresenter
{
    public function __construct(
        protected TaskStaffAssignment $assignment
    ) {}

    /**
     * 出欠ステータスの日本語表示を取得
     */
    public function attendanceStatusLabel(): string
    {
        return $this->assignment->attendance_status_enum?->label()
            ?? (string) $this->assignment->attendance_status;
    }

    /**
     * マイシフト表示用の対象日ヘッダーラベル（例: '2026年10月11日（日）'）を取得
     */
    public function dateHeaderLabel(): string
    {
        $targetDate = $this->assignment->task?->timetable?->target_date;
        if (! $targetDate) {
            return '日付未設定';
        }

        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$targetDate->dayOfWeek];

        return $targetDate->format('Y年m月d日')."（{$weekday}）";
    }

    /**
     * マイシフトカードの詳細アコーディオンに表示する情報があるか判定
     */
    public function hasDetails(): bool
    {
        $task = $this->assignment->task;
        if (! $task) {
            return false;
        }

        $laneLeadStaff = $task->timetableLane?->leadStaff;

        return ! empty($task->content)
            || ! empty($this->assignment->notes)
            || ($laneLeadStaff && $laneLeadStaff->id !== $this->assignment->staff_id)
            || $task->assignments->count() > 1;
    }
}
