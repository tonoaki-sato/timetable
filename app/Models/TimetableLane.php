<?php

namespace App\Models;

use App\Presenters\TimetableLanePresenter;
use Database\Factories\TimetableLaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'timetable_id',
    'lane_id',
    'lead_staff_id',
    'display_order',
    'notes',
])]
class TimetableLane extends Model
{
    /** @use HasFactory<TimetableLaneFactory> */
    use HasFactory;

    protected ?TimetableLanePresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): TimetableLanePresenter
    {
        return $this->presenterInstance ??= new TimetableLanePresenter($this);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'lane_id' => 'integer',
            'lead_staff_id' => 'integer',
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 表示順序でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 親タイムテーブルとのリレーション
     *
     * @return BelongsTo<Timetable, $this>
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 親担当班マスターとのリレーション
     *
     * @return BelongsTo<Lane, $this>
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class, 'lane_id');
    }

    /**
     * 班長（主担当スタッフ）とのリレーション
     *
     * @return BelongsTo<Staff, $this>
     */
    public function leadStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'lead_staff_id');
    }

    /**
     * 固定スタッフ一覧との多対多リレーション
     *
     * @return BelongsToMany<Staff, $this>
     */
    public function fixedStaff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'timetable_lane_fixed_staff')
            ->withTimestamps();
    }

    /**
     * 班長からの要請・機材申請一覧とのリレーション
     *
     * @return HasMany<LaneRequest, $this>
     */
    public function laneRequests(): HasMany
    {
        return $this->hasMany(LaneRequest::class, 'timetable_lane_id')->orderByDesc('id');
    }

    /**
     * 所属作業タスク一覧とのリレーション
     *
     * @return HasMany<TimetableTask, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TimetableTask::class, 'timetable_lane_id')->orderBy('start_time', 'asc');
    }

    /**
     * 班長（主担当）が設定されているか
     */
    public function hasLead(): bool
    {
        return $this->lead_staff_id !== null;
    }

    /**
     * 担当班名（マスター委譲）
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->lane?->name ?? '');
    }

    /**
     * テーマカラー（マスター委譲）
     */
    public function getColorAttribute(): string
    {
        return (string) ($this->lane?->color ?? 'slate');
    }

    /**
     * アイコン（マスター委譲）
     */
    public function getIconAttribute(): ?string
    {
        return $this->lane?->icon;
    }

    /**
     * 標準役割概要（マスター委譲）
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->lane?->description;
    }

    /**
     * カラーバッジ表示用のCSSクラス配列
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function getColorClassesAttribute(): array
    {
        return $this->presenter()->colorClasses();
    }

    /**
     * アイコン表示用アクセサ（未設定時は '📋'）
     */
    public function getIconLabelAttribute(): string
    {
        return $this->lane?->icon_label ?? '📋';
    }

    /**
     * 進行表マトリクス・PDF用のアイコン表示アクセサ（未設定時は '◆'）
     */
    public function getMatrixIconLabelAttribute(): string
    {
        return $this->lane?->matrix_icon_label ?? '◆';
    }

    /**
     * 班長の苗字（短縮名）アクセサ
     */
    public function getLeadShortNameAttribute(): string
    {
        return $this->leadStaff
            ? (explode(' ', $this->leadStaff->name)[0] ?? $this->leadStaff->name)
            : '';
    }

    /**
     * PDF進行表ヘッダー用の班長表記（例: '（佐藤さん）'）
     */
    public function getPdfLeadSuffixAttribute(): string
    {
        return $this->presenter()->pdfLeadSuffix();
    }

    /**
     * 新規タスク追加時の推奨開始時刻（最遅終了時刻または '08:00'）
     */
    public function getSuggestedStartTimeAttribute(): string
    {
        $latestTask = $this->tasks->sortByDesc('end_time')->first();

        return $latestTask ? substr((string) $latestTask->end_time, 0, 5) : '08:00';
    }

    /**
     * 固定スタッフID配列アクセサ
     *
     * @return array<int, int>
     */
    public function getFixedStaffIdsAttribute(): array
    {
        return $this->fixedStaff->pluck('id')->all();
    }

    /**
     * 編集フォーム表示用の選択済み固定スタッフID配列を取得
     *
     * @param  array<int, int|string>|null  $oldFixedIds
     * @return array<int, int|string>
     */
    public function resolveSelectedFixedIds(?array $oldFixedIds = null): array
    {
        return $oldFixedIds !== null ? $oldFixedIds : $this->fixed_staff_ids;
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
        $fixedStaffList = $this->fixedStaff
            ->map(fn (Staff $staff): array => [
                'id' => (int) $staff->id,
                'name' => (string) $staff->name,
                'affiliation' => $staff->affiliation,
            ])
            ->values()
            ->all();

        $tasksPayload = $this->relationLoaded('tasks')
            ? $this->tasks
                ->map(fn (TimetableTask $task): array => [
                    'id' => (int) $task->id,
                    'title' => (string) $task->title,
                    'start_time' => substr((string) $task->start_time, 0, 5),
                    'end_time' => substr((string) $task->end_time, 0, 5),
                    'location' => $task->location,
                    'status_label' => (string) $task->status_label,
                ])
                ->values()
                ->all()
            : [];

        $laneRequestsPayload = $this->relationLoaded('laneRequests')
            ? $this->laneRequests
                ->map(fn (LaneRequest $req): array => [
                    'id' => (int) $req->id,
                    'type_label' => (string) $req->type_label,
                    'status' => (string) $req->status,
                    'status_label' => (string) $req->status_label,
                    'message' => $req->message,
                    'task_title' => $req->task?->title,
                    'requested_by_name' => $req->requestedByStaff?->name,
                    'created_at_label' => $req->created_at?->format('m/d H:i'),
                ])
                ->values()
                ->all()
            : [];

        return [
            'id' => (int) $this->id,
            'timetable_id' => (int) $this->timetable_id,
            'lane_id' => (int) $this->lane_id,
            'name' => $this->name,
            'color' => $this->color,
            'color_classes' => $this->color_classes,
            'icon' => $this->icon,
            'icon_label' => $this->icon_label,
            'description' => $this->description,
            'display_order' => (int) $this->display_order,
            'notes' => $this->notes,
            'lead_staff_id' => $this->lead_staff_id !== null ? (int) $this->lead_staff_id : null,
            'lead_staff_name' => $this->leadStaff?->name,
            'fixed_staff_ids' => array_values(array_map('intval', $this->fixed_staff_ids)),
            'fixed_staff' => $fixedStaffList,
            'tasks_count' => (int) ($this->tasks_count ?? $this->tasks()->count()),
            'tasks_url' => route('timetables.tasks.index', ['timetable' => $this->timetable_id]).'#lane-'.$this->id,
            'tasks' => $tasksPayload,
            'lane_requests' => $laneRequestsPayload,
            'update_url' => route('timetables.lanes.update', [$this->timetable_id, $this->id]),
            'destroy_url' => route('timetables.lanes.destroy', [$this->timetable_id, $this->id]),
        ];
    }
}
