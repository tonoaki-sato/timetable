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
     * チェックイン済みかどうか（属性が明示設定された場合は運行情報単位の値を優先）
     */
    public function getIsCheckedInAttribute(): bool
    {
        if (array_key_exists('is_checked_in', $this->attributes)) {
            return (bool) $this->attributes['is_checked_in'];
        }

        if ($this->relationLoaded('checkIns')) {
            return $this->checkIns->isNotEmpty();
        }

        return $this->checkIns()->exists();
    }

    /**
     * 指定運行計画に対する到着チェックイン記録を取得
     */
    public function checkInForTimetable(int $timetableId): ?StaffCheckIn
    {
        if ($this->relationLoaded('checkIns')) {
            return $this->checkIns->firstWhere('timetable_id', $timetableId);
        }

        return $this->checkIns()->where('timetable_id', $timetableId)->first();
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
     * 運行情報ごとの現場到着チェックイン記録一覧とのリレーション
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(StaffCheckIn::class)->orderByDesc('checked_in_at');
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
     * 班長（主担当）を務めているかどうか
     */
    public function getIsLeaderAttribute(): bool
    {
        if (isset($this->attributes['led_lanes_exists'])) {
            return (bool) $this->attributes['led_lanes_exists'];
        }

        if ($this->relationLoaded('ledLanes')) {
            return $this->ledLanes->isNotEmpty();
        }

        return $this->ledLanes()->exists();
    }

    /**
     * 班長を務めるユニークな班（レーン）名リスト
     *
     * @return array<int, string>
     */
    public function getLedLaneNamesAttribute(): array
    {
        if (! $this->relationLoaded('ledLanes')) {
            $this->load('ledLanes');
        }

        return $this->ledLanes->pluck('name')->unique()->values()->all();
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
     * 担当・主催するスケジュール一覧とのリレーション
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'staff_id');
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
