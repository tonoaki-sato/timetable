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
    'staff_type',
    'is_active',
    'access_token',
    'registered_via',
    'last_accessed_at',
    'checked_in_at',
    'token_reissued_at',
    'reissue_count',
])]
class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    /**
     * 班員スタッフ区分定義（固定スタッフ / 充足スタッフ）
     *
     * @var array<string, string>
     */
    public const array STAFF_TYPES = [
        'fixed' => '固定スタッフ',
        'fulfillment' => '充足スタッフ',
    ];

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
            'token_reissued_at' => 'datetime',
            'reissue_count' => 'integer',
        ];
    }

    /**
     * トークンが再発行されたスタッフかどうか
     */
    public function getIsReissuedAttribute(): bool
    {
        return $this->reissue_count > 0;
    }

    /**
     * 専用マイシフトURLの取得
     */
    public function getMyShiftUrlAttribute(): string
    {
        return route('staff.myshift', ['token' => $this->access_token]);
    }

    /**
     * 当日チェックイン済みかどうか
     */
    public function getIsCheckedInAttribute(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * スタッフ区分の日本語表示ラベル
     */
    public function getStaffTypeLabelAttribute(): string
    {
        return self::STAFF_TYPES[$this->staff_type ?? 'fulfillment'] ?? '充足スタッフ';
    }

    /**
     * 固定スタッフかどうか
     */
    public function isFixed(): bool
    {
        return ($this->staff_type ?? 'fulfillment') === 'fixed';
    }

    /**
     * 充足スタッフかどうか
     */
    public function isFulfillment(): bool
    {
        return ! $this->isFixed();
    }

    /**
     * アサイン中間情報とのリレーション
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskStaffAssignment::class);
    }

    /**
     * 班長（主担当）を務めるレーン一覧とのリレーション
     */
    public function ledLanes(): HasMany
    {
        return $this->hasMany(Lane::class, 'lead_staff_id');
    }

    /**
     * 固定スタッフとして所属するレーン一覧との多対多リレーション
     */
    public function fixedLanes(): BelongsToMany
    {
        return $this->belongsToMany(Lane::class, 'lane_fixed_staff')
            ->withTimestamps();
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
                    ->orWhere('affiliation', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
