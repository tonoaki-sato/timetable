<?php

namespace App\Models;

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
    'lane_id',
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

    /**
     * ステータス定義一覧
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'not_started' => '未着手',
        'in_progress' => '進行中',
        'completed' => '完了',
        'cancelled' => '中止',
    ];

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
     * 紐づく担当班とのリレーション
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class, 'lane_id');
    }

    /**
     * 紐づく担当班とのリレーション（後方互換）
     */
    public function laneRelation(): BelongsTo
    {
        return $this->lane();
    }

    /**
     * 後方互換用: レーン名文字列アクセサ
     */
    public function getLaneAttribute(): string
    {
        return $this->laneRelation?->name ?? '';
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
     * 必要人員の表示用ラベル（例:「3名以上」または「3名」）
     */
    public function getRequiredPeopleLabelAttribute(): string
    {
        return $this->is_at_least
            ? "{$this->required_people_count}名以上"
            : "{$this->required_people_count}名";
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
     * ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
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
     * 担当班名の表示用ラベル（未設定時は '担当班未設定'）
     */
    public function getLaneNameLabelAttribute(): string
    {
        return $this->laneRelation?->name ?? '担当班未設定';
    }

    /**
     * 担当班アイコンの表示用ラベル（未設定時は '📋'）
     */
    public function getLaneIconLabelAttribute(): string
    {
        return $this->laneRelation?->icon ?: '📋';
    }

    /**
     * 担当班バッジのCSSクラス
     */
    public function getLaneBadgeClassAttribute(): string
    {
        return $this->laneRelation?->color_classes['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200';
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

    /**
     * 充足プログレスバーの背景色クラス
     */
    public function getProgressColorClassAttribute(): string
    {
        return $this->is_satisfied
            ? 'bg-emerald-500'
            : ($this->assigned_count > 0 ? 'bg-amber-500' : 'bg-slate-300');
    }

    /**
     * スタッフ配置画面のクライアント検索用テキスト（小文字）
     */
    public function getAssignmentSearchTextAttribute(): string
    {
        return mb_strtolower($this->title.' '.$this->lane_name_label.' '.($this->location ?? '').' '.($this->content ?? ''));
    }

    /**
     * マトリクス・PDF進行表の人員規模ラベル（例: '【3名以上】'）
     */
    public function getMatrixPeopleLabelAttribute(): string
    {
        if (! $this->required_people_count) {
            return '';
        }

        return $this->is_at_least
            ? "【{$this->required_people_count}名以上】"
            : "【{$this->required_people_count}名】";
    }

    /**
     * ステータスバッジのCSSクラス
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'in_progress' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            'cancelled' => 'bg-slate-200 text-slate-500 border-slate-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    /**
     * タスク編集モーダル用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(?Lane $lane = null): array
    {
        $targetLane = $lane ?? $this->laneRelation;

        return [
            'id' => $this->id,
            'timetable_id' => $this->timetable_id,
            'lane_id' => $this->lane_id,
            'lane_name' => $targetLane?->name ?? '',
            'lane_icon' => $targetLane?->icon ?: '📋',
            'lead_staff_name' => $targetLane?->leadStaff?->name,
            'title' => $this->title,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'required_people_count' => $this->required_people_count,
            'is_at_least' => (bool) $this->is_at_least,
            'is_critical' => (bool) $this->is_critical,
            'location' => $this->location ?? '',
            'status' => $this->status,
            'status_label' => $this->status_label,
            'content' => $this->content ?? '',
            'update_url' => route('timetable-tasks.update', $this),
        ];
    }
}
