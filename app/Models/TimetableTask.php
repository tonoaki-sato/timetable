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
    'lane',
    'location',
    'start_time',
    'end_time',
    'required_people_count',
    'is_at_least',
    'description',
    'is_critical',
    'requires_review',
    'status',
])]
class TimetableTask extends Model
{
    /** @use HasFactory<TimetableTaskFactory> */
    use HasFactory;

    /**
     * モデルイベントの登録
     */
    protected static function booted(): void
    {
        static::saving(function (TimetableTask $task) {
            if ($task->lane_id) {
                if (empty($task->lane)) {
                    $lane = Lane::find($task->lane_id);
                    if ($lane) {
                        $task->lane = $lane->name;
                    }
                }
            } elseif ($task->timetable_id && $task->lane) {
                $mappedName = match ($task->lane) {
                    'カラーコーン班' => 'カラーコーン',
                    '給水設備班' => '給水設備',
                    '衛生班' => '衛生',
                    '誘導・警備班' => '巡回',
                    '救護班', '本部連絡班' => '本陣',
                    default => $task->lane,
                };
                $lane = Lane::where('timetable_id', $task->timetable_id)
                    ->where('name', $mappedName)
                    ->first();
                if ($lane) {
                    $task->lane_id = $lane->id;
                }
            }
        });
    }

    /**
     * 担当班（レーン）の定義一覧（デフォルト10レーン＋旧互換名称）
     *
     * @var array<string, string>
     */
    public const array LANES = [
        'カラーコーン' => 'カラーコーン',
        '給水設備' => '給水設備',
        '衛生' => '衛生',
        '巡回' => '巡回',
        'キッズ村' => 'キッズ村',
        'ちょうちん' => 'ちょうちん',
        'ござ市' => 'ござ市',
        '本陣' => '本陣',
        '道路清掃' => '道路清掃',
        'ステカン' => 'ステカン',
        'カラーコーン班' => 'カラーコーン班',
        '給水設備班' => '給水設備班',
        '衛生班' => '衛生班',
        '誘導・警備班' => '誘導・警備班',
        '救護班' => '救護班',
        '本部連絡班' => '本部連絡班',
    ];

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
            'requires_review' => 'boolean',
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
     * 紐づく担当班（レーン）とのリレーション
     */
    public function laneRelation(): BelongsTo
    {
        return $this->belongsTo(Lane::class, 'lane_id');
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
     * 担当班によるスコープ
     */
    public function scopeLane(Builder $query, ?string $lane = null): Builder
    {
        if ($lane) {
            return $query->where('lane', $lane);
        }

        return $query;
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
     * 最優先タスクのスコープ
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('is_critical', true);
    }
}
