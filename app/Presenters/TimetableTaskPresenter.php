<?php

namespace App\Presenters;

use App\Models\TimetableLane;
use App\Models\TimetableTask;

class TimetableTaskPresenter
{
    public function __construct(
        protected TimetableTask $task
    ) {}

    /**
     * ステータスバッジのCSSクラスを取得
     */
    public function statusBadgeClass(): string
    {
        return $this->task->status_enum->badgeClasses();
    }

    /**
     * 充足プログレスバーの背景色クラスを取得
     */
    public function progressColorClass(): string
    {
        if ($this->task->is_satisfied) {
            return 'bg-emerald-500';
        }

        return $this->task->assigned_count > 0 ? 'bg-amber-500' : 'bg-slate-300';
    }

    /**
     * 担当班バッジのCSSクラスを取得
     */
    public function laneBadgeClass(): string
    {
        return $this->task->timetableLane?->color_classes['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200';
    }

    /**
     * タスク編集モーダル用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(?TimetableLane $timetableLane = null): array
    {
        $targetLane = $timetableLane ?? $this->task->timetableLane;

        return [
            'id' => $this->task->id,
            'timetable_id' => $this->task->timetable_id,
            'timetable_lane_id' => $this->task->timetable_lane_id,
            'lane_name' => $targetLane?->name ?? '',
            'lane_icon' => $targetLane?->icon ?: '📋',
            'lead_staff_name' => $targetLane?->leadStaff?->name,
            'title' => $this->task->title,
            'start_time' => substr((string) $this->task->start_time, 0, 5),
            'end_time' => substr((string) $this->task->end_time, 0, 5),
            'required_people_count' => $this->task->required_people_count,
            'is_at_least' => (bool) $this->task->is_at_least,
            'is_critical' => (bool) $this->task->is_critical,
            'location' => $this->task->location ?? '',
            'status' => $this->task->status,
            'status_label' => $this->task->status_label,
            'content' => $this->task->content ?? '',
            'update_url' => route('timetable-tasks.update', $this->task),
        ];
    }
}
