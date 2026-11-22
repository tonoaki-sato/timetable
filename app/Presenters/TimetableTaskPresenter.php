<?php

namespace App\Presenters;

use App\Enums\TimetableTaskStatus;
use App\Models\TimetableLane;
use App\Models\TimetableTask;

class TimetableTaskPresenter
{
    public function __construct(
        protected TimetableTask $task
    ) {}

    /**
     * 時間帯文字列（例: "09:00〜11:00"）を取得
     */
    public function timeRange(): string
    {
        return substr((string) $this->task->start_time, 0, 5).'〜'.substr((string) $this->task->end_time, 0, 5);
    }

    /**
     * ステータスの日本語表示を取得
     */
    public function statusLabel(): string
    {
        return TimetableTaskStatus::tryFrom((string) $this->task->status)?->label()
            ?? (string) $this->task->status;
    }

    /**
     * 必要人員の表示用ラベル（例:「3名以上」または「3名」）を取得
     */
    public function requiredPeopleLabel(): string
    {
        return $this->task->is_at_least
            ? "{$this->task->required_people_count}名以上"
            : "{$this->task->required_people_count}名";
    }

    /**
     * マトリクス・PDF進行表の人員規模ラベル（例: '【3名以上】'）を取得
     */
    public function matrixPeopleLabel(): string
    {
        if (! $this->task->required_people_count) {
            return '';
        }

        return $this->task->is_at_least
            ? "【{$this->task->required_people_count}名以上】"
            : "【{$this->task->required_people_count}名】";
    }

    /**
     * 担当班名の表示用ラベル（未設定時は '担当班未設定'）を取得
     */
    public function laneNameLabel(): string
    {
        return $this->task->timetableLane?->name ?? '担当班未設定';
    }

    /**
     * 担当班アイコンの表示用ラベル（未設定時は '📋'）を取得
     */
    public function laneIconLabel(): string
    {
        return $this->task->timetableLane?->icon ?: '📋';
    }

    /**
     * スタッフ配置画面のクライアント検索用テキスト（小文字）を取得
     */
    public function assignmentSearchText(): string
    {
        return mb_strtolower($this->task->title.' '.$this->laneNameLabel().' '.($this->task->location ?? '').' '.($this->task->content ?? ''));
    }

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
        return $this->task->timetableLane?->presenter()->colorClasses()['badge']
            ?? 'bg-slate-100 text-slate-800 border-slate-200';
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
            'status_label' => $this->statusLabel(),
            'content' => $this->task->content ?? '',
            'update_url' => route('timetable-tasks.update', $this->task),
        ];
    }
}
