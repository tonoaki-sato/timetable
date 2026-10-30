<?php

namespace App\Models;

use Database\Factories\LocationScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'location_id',
    'start_at',
    'end_at',
    'application_status',
    'title',
    'note',
])]
class LocationSchedule extends Model
{
    /** @use HasFactory<LocationScheduleFactory> */
    use HasFactory;

    /**
     * 申請ステータス一覧
     *
     * @var array<string, string>
     */
    public const array APPLICATION_STATUSES = [
        'unapplied' => '未申請（利用不可）',
        'applying' => '申請中（利用不可）',
        'approved' => '承認済（利用可能）',
        'not_required' => '申請不要（利用可能）',
        'rejected' => '却下（利用不可）',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 所属する拠点
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * 申請ステータスの日本語ラベル
     */
    public function getApplicationStatusLabelAttribute(): string
    {
        return self::APPLICATION_STATUSES[$this->application_status] ?? $this->application_status;
    }

    /**
     * 申請が利用可能な状態（承認済または申請不要）か
     */
    public function isPermitted(): bool
    {
        return in_array($this->application_status, ['approved', 'not_required'], true);
    }

    /**
     * 申請ステータスのバッジCSSクラス
     */
    public function getApplicationStatusBadgeClassAttribute(): string
    {
        return match ($this->application_status) {
            'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'not_required' => 'bg-blue-100 text-blue-800 border-blue-300',
            'unapplied' => 'bg-rose-100 text-rose-800 border-rose-300',
            'applying' => 'bg-amber-100 text-amber-800 border-amber-300',
            'rejected' => 'bg-red-100 text-red-900 border-red-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }

    /**
     * 開始日時順ソート
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('start_at', 'asc')->orderBy('id', 'asc');
    }
}
