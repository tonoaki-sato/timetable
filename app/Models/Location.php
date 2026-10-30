<?php

namespace App\Models;

use App\DTOs\LocationStatusEvaluation;
use App\Enums\LocationCurrentStatus;
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
        $dateTime = $dateTime
            ? Carbon::parse($dateTime)->timezone('Asia/Tokyo')
            : Carbon::now('Asia/Tokyo');

        if (! $this->is_active) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::Inactive,
                detail: '拠点は現在無効（非稼働）に設定されています。'
            );
        }

        if ($this->is_all_day) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::Available,
                detail: '終日利用可能です。'
            );
        }

        $schedules = $this->relationLoaded('schedules')
            ? $this->schedules
            : $this->schedules()->orderBy('start_at', 'asc')->get();

        if ($schedules->isEmpty()) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::NoSchedule,
                detail: '利用可能時間帯がまだ登録されていません。'
            );
        }

        // 指定日時を含むスロットを検索
        $matched = $schedules->first(function (LocationSchedule $schedule) use ($dateTime) {
            return $schedule->start_at <= $dateTime && $dateTime <= $schedule->end_at;
        });

        if ($matched) {
            $slotTitle = $matched->title ? "「{$matched->title}」" : $matched->start_at->format('H:i').'〜'.$matched->end_at->format('H:i');

            return match ($matched->application_status) {
                'unapplied' => new LocationStatusEvaluation(
                    status: LocationCurrentStatus::UnavailableUnapplied,
                    detail: "{$slotTitle}は実行委員会として未申請のため利用できません。",
                    matchedSchedule: $matched
                ),
                'applying' => new LocationStatusEvaluation(
                    status: LocationCurrentStatus::UnavailableApplying,
                    detail: "{$slotTitle}は利用申請の審査中（許可待ち）です。",
                    matchedSchedule: $matched
                ),
                'rejected' => new LocationStatusEvaluation(
                    status: LocationCurrentStatus::UnavailableRejected,
                    detail: "{$slotTitle}は申請が却下されたため利用できません。",
                    matchedSchedule: $matched
                ),
                'approved', 'not_required' => new LocationStatusEvaluation(
                    status: LocationCurrentStatus::Available,
                    detail: "{$slotTitle}で利用可能です（{$matched->end_at->format('H:i')}まで）。",
                    matchedSchedule: $matched
                ),
                default => new LocationStatusEvaluation(
                    status: LocationCurrentStatus::UnavailableOutOfHours,
                    detail: "{$slotTitle}の申請ステータスが不正です。",
                    matchedSchedule: $matched
                ),
            };
        }

        // 枠外の場合
        $latestEnd = $schedules->max('end_at');
        if ($latestEnd && $dateTime > $latestEnd) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::Completed,
                detail: 'すべての利用可能期間が終了しました。'
            );
        }

        // 次の利用可能枠を検索
        $next = $schedules->first(function (LocationSchedule $schedule) use ($dateTime) {
            return $schedule->start_at > $dateTime;
        });

        $nextDetail = $next
            ? '次回利用可能枠: '.$next->start_at->format('n/j H:i').'〜'.$next->end_at->format('H:i')
            : '現在利用時間外です。';

        return new LocationStatusEvaluation(
            status: LocationCurrentStatus::UnavailableOutOfHours,
            detail: $nextDetail,
            nextSchedule: $next
        );
    }
}
