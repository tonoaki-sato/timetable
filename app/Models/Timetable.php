<?php

namespace App\Models;

use Database\Factories\TimetableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'target_date',
    'name',
    'description',
    'start_time',
    'end_time',
    'status',
    'created_by',
])]
class Timetable extends Model
{
    /** @use HasFactory<TimetableFactory> */
    use HasFactory;

    /**
     * ステータス定義一覧
     *
     * @var array<string, string>
     */
    public const array STATUSES = [
        'draft' => '準備中',
        'active' => '運行中',
        'completed' => '運行完了',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    /**
     * 紐づく作業タスク一覧とのリレーション
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TimetableTask::class)->orderBy('start_time', 'asc');
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ステータスによる絞り込みスコープ
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status) {
            return $query->where('status', $status);
        }

        return $query;
    }
}
