<?php

namespace App\Models;

use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'kana',
    'affiliation',
    'is_active',
    'access_token',
    'line_user_id',
    'line_display_name',
    'line_picture_url',
    'registered_via',
    'last_accessed_at',
    'checked_in_at',
])]
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    /**
     * モデルの起動処理
     */
    protected static function booted(): void
    {
        static::creating(function (Staff $staff) {
            if (empty($staff->access_token)) {
                $staff->access_token = Str::random(32);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_accessed_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * 専用マイシフトURLの取得
     */
    public function getMyShiftUrlAttribute(): string
    {
        return route('staff.myshift', ['token' => $this->access_token]);
    }

    /**
     * LINEグループでの呼び名（LINE表示名があればそれを優先、なければ本名）
     */
    public function getMentionNameAttribute(): string
    {
        return $this->line_display_name ?: $this->name;
    }

    /**
     * 当日チェックイン済みかどうか
     */
    public function getIsCheckedInAttribute(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * アサイン中間情報とのリレーション
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskStaffAssignment::class);
    }

    /**
     * 主担当（現場リーダー）を務めるレーン一覧とのリレーション
     */
    public function ledLanes(): HasMany
    {
        return $this->hasMany(Lane::class, 'lead_staff_id');
    }

    /**
     * 担当する年間スケジュール一覧とのリレーション
     */
    public function annualSchedules(): HasMany
    {
        return $this->hasMany(AnnualSchedule::class, 'staff_id');
    }

    /**
     * 主催する会議・打ち合わせ一覧とのリレーション
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'staff_id');
    }

    /**
     * 割り当てられたタスク一覧との多対多リレーション
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(TimetableTask::class, 'task_staff_assignments')
            ->withPivot(['id', 'role_description', 'attendance_status', 'notes'])
            ->withTimestamps();
    }

    /**
     * 稼働可能スタッフのスコープ
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * キーワード検索（氏名・フリガナ・所属）
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            return $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('kana', 'like', "%{$search}%")
                    ->orWhere('affiliation', 'like', "%{$search}%")
                    ->orWhere('line_display_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
