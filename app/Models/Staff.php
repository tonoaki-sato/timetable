<?php

namespace App\Models;

use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'kana',
    'affiliation',
    'is_active',
])]
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
                    ->orWhere('affiliation', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
