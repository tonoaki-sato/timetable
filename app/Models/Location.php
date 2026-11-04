<?php

namespace App\Models;

use App\DTOs\LocationStatusEvaluation;
use App\Services\LocationAvailabilityService;
use Carbon\Carbon;
use Database\Factories\LocationFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'location_type_id',
    'name',
    'notes',
    'is_all_day',
    'display_order',
    'is_active',
    'created_by',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_all_day' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 拠点タイプ
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LocationType::class, 'location_type_id');
    }

    /**
     * 拠点利用可能時間帯スロット一覧
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(LocationSchedule::class)->orderBy('start_at', 'asc');
    }

    /**
     * 登録者ユーザー
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * この拠点へ搬入・保管される機材物流計画
     */
    public function plansTo(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'to_location_id');
    }

    /**
     * この拠点から搬出される機材物流計画
     */
    public function plansFrom(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'from_location_id');
    }

    /**
     * 種別ラベルのアクセサ
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label ?? $this->type?->name ?? '';
    }

    /**
     * 説明・備考アクセサ（notesのエイリアス）
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->notes;
    }

    /**
     * 利用可能開始日時のアクセサ（登録スケジュールの最早上限）
     */
    public function getAvailableFromAttribute(): ?Carbon
    {
        $min = $this->relationLoaded('schedules')
            ? $this->schedules->min('start_at')
            : $this->schedules()->min('start_at');

        return $min ? Carbon::parse($min) : null;
    }

    /**
     * 利用可能終了日時のアクセサ（登録スケジュールの最遅上限）
     */
    public function getAvailableToAttribute(): ?Carbon
    {
        $max = $this->relationLoaded('schedules')
            ? $this->schedules->max('end_at')
            : $this->schedules()->max('end_at');

        return $max ? Carbon::parse($max) : null;
    }

    /**
     * 指定日時にこの拠点・駐車場が利用（貸切）可能か判定
     */
    public function isAvailableAt(?DateTimeInterface $dateTime = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_all_day) {
            return true;
        }

        $dateTime = $dateTime
            ? Carbon::parse($dateTime)->timezone('Asia/Tokyo')
            : Carbon::now('Asia/Tokyo');

        $schedules = $this->relationLoaded('schedules')
            ? $this->schedules
            : $this->schedules()->get();

        if ($schedules->isEmpty()) {
            return false;
        }

        return $schedules->contains(function (LocationSchedule $schedule) use ($dateTime) {
            $inSlot = $schedule->start_at <= $dateTime && $dateTime <= $schedule->end_at;

            return $inSlot && $schedule->isAvailable();
        });
    }

    /**
     * 有効拠点のみ抽出
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順ソート
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 指定された日時（デフォルト: 現在時刻 Asia/Tokyo）における状態を評価
     */
    public function evaluateStatusAt(?DateTimeInterface $dateTime = null): LocationStatusEvaluation
    {
        return app(LocationAvailabilityService::class)->evaluateLocationStatus($this, $dateTime);
    }
}
