<?php

namespace App\Presenters;

use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\TimetableLane;
use App\Models\TimetableTask;

class TimetableLanePresenter
{
    public function __construct(
        protected TimetableLane $timetableLane
    ) {}

    /**
     * カラーバッジ表示用のCSSクラス配列を取得（担当班マスター設定に委譲）
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function colorClasses(): array
    {
        return LanePresenter::colorClassesFor($this->timetableLane->color);
    }

    /**
     * アイコン表示用文字列を取得（未設定時は '📋'）
     */
    public function iconLabel(): string
    {
        return $this->timetableLane->lane?->presenter()->iconLabel() ?? '📋';
    }

    /**
     * 進行表マトリクス・PDF用のアイコン表示文字列を取得（未設定時は '◆'）
     */
    public function matrixIconLabel(): string
    {
        return $this->timetableLane->lane?->presenter()->matrixIconLabel() ?? '◆';
    }

    /**
     * 班長の苗字（短縮名）を取得
     */
    public function leadShortName(): string
    {
        $leadStaff = $this->timetableLane->leadStaff;
        if (! $leadStaff) {
            return '';
        }

        return explode(' ', $leadStaff->name)[0] ?? $leadStaff->name;
    }

    /**
     * PDF進行表ヘッダー用の班長表記（例: '（佐藤さん）'）
     */
    public function pdfLeadSuffix(): string
    {
        $leadName = $this->leadShortName();
        if ($leadName === '') {
            return '';
        }

        return (str_ends_with($leadName, 'さん') || str_ends_with($leadName, '外'))
            ? "（{$leadName}）"
            : "（{$leadName}さん）";
    }

    /**
     * 新規タスク追加時の推奨開始時刻を取得（最遅終了時刻または '08:00'）
     */
    public function suggestedStartTime(): string
    {
        $latestTask = $this->timetableLane->tasks->sortByDesc('end_time')->first();

        return $latestTask ? substr((string) $latestTask->end_time, 0, 5) : '08:00';
    }

    /**
     * 詳細・編集モーダル用ペイロード配列を生成
     *
     * @return array{
     *     id: int,
     *     timetable_id: int,
     *     lane_id: int,
     *     name: string,
     *     color: string,
     *     color_classes: array{badge: string, border: string, bg: string, text: string},
     *     icon: ?string,
     *     icon_label: string,
     *     description: ?string,
     *     display_order: int,
     *     notes: ?string,
     *     lead_staff_id: ?int,
     *     lead_staff_name: ?string,
     *     fixed_staff_ids: list<int>,
     *     fixed_staff: list<array{id: int, name: string, affiliation: ?string}>,
     *     tasks_count: int,
     *     tasks_url: string,
     *     tasks: list<array{id: int, title: string, start_time: string, end_time: string, location: ?string, status_label: string}>,
     *     lane_requests: list<array{id: int, type_label: string, status: string, status_label: string, message: ?string, task_title: ?string, requested_by_name: ?string, created_at_label: ?string}>,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    public function toModalPayload(): array
    {
        $fixedStaffList = $this->timetableLane->fixedStaff
            ->map(fn (Staff $staff): array => [
                'id' => (int) $staff->id,
                'name' => (string) $staff->name,
                'affiliation' => $staff->affiliation,
            ])
            ->values()
            ->all();

        $tasksPayload = $this->timetableLane->relationLoaded('tasks')
            ? $this->timetableLane->tasks
                ->map(fn (TimetableTask $task): array => [
                    'id' => (int) $task->id,
                    'title' => (string) $task->title,
                    'start_time' => substr((string) $task->start_time, 0, 5),
                    'end_time' => substr((string) $task->end_time, 0, 5),
                    'location' => $task->location,
                    'status_label' => $task->presenter()->statusLabel(),
                ])
                ->values()
                ->all()
            : [];

        $laneRequestsPayload = $this->timetableLane->relationLoaded('laneRequests')
            ? $this->timetableLane->laneRequests
                ->map(fn (LaneRequest $req): array => [
                    'id' => (int) $req->id,
                    'type_label' => $req->presenter()->typeLabel(),
                    'status' => (string) $req->status,
                    'status_label' => $req->presenter()->statusLabel(),
                    'message' => $req->message,
                    'task_title' => $req->task?->title,
                    'requested_by_name' => $req->requestedByStaff?->name,
                    'created_at_label' => $req->created_at?->format('m/d H:i'),
                ])
                ->values()
                ->all()
            : [];

        return [
            'id' => (int) $this->timetableLane->id,
            'timetable_id' => (int) $this->timetableLane->timetable_id,
            'lane_id' => (int) $this->timetableLane->lane_id,
            'name' => $this->timetableLane->name,
            'color' => $this->timetableLane->color,
            'color_classes' => $this->colorClasses(),
            'icon' => $this->timetableLane->icon,
            'icon_label' => $this->iconLabel(),
            'description' => $this->timetableLane->description,
            'display_order' => (int) $this->timetableLane->display_order,
            'notes' => $this->timetableLane->notes,
            'lead_staff_id' => $this->timetableLane->lead_staff_id !== null ? (int) $this->timetableLane->lead_staff_id : null,
            'lead_staff_name' => $this->timetableLane->leadStaff?->name,
            'fixed_staff_ids' => array_values(array_map('intval', $this->timetableLane->fixed_staff_ids)),
            'fixed_staff' => $fixedStaffList,
            'tasks_count' => (int) ($this->timetableLane->tasks_count ?? $this->timetableLane->tasks()->count()),
            'tasks_url' => route('timetables.tasks.index', ['timetable' => $this->timetableLane->timetable_id]).'#lane-'.$this->timetableLane->id,
            'tasks' => $tasksPayload,
            'lane_requests' => $laneRequestsPayload,
            'update_url' => route('timetables.lanes.update', [$this->timetableLane->timetable_id, $this->timetableLane->id]),
            'destroy_url' => route('timetables.lanes.destroy', [$this->timetableLane->timetable_id, $this->timetableLane->id]),
        ];
    }
}
