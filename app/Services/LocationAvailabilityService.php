<?php

namespace App\Services;

use App\DTOs\LocationStatusEvaluation;
use App\Enums\LocationCurrentStatus;
use App\Enums\LocationDailyAvailabilityStatus;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\LocationSchedule;
use Carbon\Carbon;
use DateTimeInterface;

class LocationAvailabilityService
{
    /**
     * 移動先拠点の貸切・利用可能期間（中抜け時間帯含む）を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkToLocationAvailability(EquipmentLogisticsPlan $plan): ?array
    {
        $location = $plan->toLocationModel;
        if (! $location || $location->is_all_day || ! $plan->target_date) {
            return null;
        }

        if (! $location->is_active) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        $schedules = $location->relationLoaded('schedules')
            ? $location->schedules
            : $location->schedules()->orderBy('start_at', 'asc')->get();

        if ($schedules->isEmpty()) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        [$startDateTime, $endDateTime] = $this->resolveStepDateTimeRange($plan);
        if (! $startDateTime || ! $endDateTime) {
            return null;
        }

        // 実施時間帯を完全に包含するスロットを検索
        $matched = $schedules->first(function (LocationSchedule $schedule) use ($startDateTime, $endDateTime) {
            return $schedule->start_at <= $startDateTime && $endDateTime <= $schedule->end_at;
        });

        if ($matched) {
            if (! $matched->isAvailable()) {
                return [
                    'level' => 'warning',
                    'message' => "拠点「{$location->name}」の該当時間帯はまだ利用確保（利用可）になっていません。",
                ];
            }

            return null;
        }

        $minStart = Carbon::parse($schedules->min('start_at'));
        if ($startDateTime < $minStart) {
            $fromStr = $minStart->format('n/j H:i');

            return [
                'level' => 'warning',
                'message' => "移動先「{$location->name}」の利用開始（{$fromStr}）前です。利用可能になってから搬入してください。",
            ];
        }

        return [
            'level' => 'warning',
            'message' => "移動先「{$location->name}」の利用可能時間外（またはイベントブース使用時間帯）です。利用可能枠を確認してください。",
        ];
    }

    /**
     * 移動元拠点の搬出期限・中抜け時間帯を判定し、警告があれば配列で返却
     *
     * @return array{level: string, message: string}|null
     */
    public function checkFromLocationAvailability(EquipmentLogisticsPlan $plan): ?array
    {
        $location = $plan->fromLocationModel;
        if (! $location || $location->is_all_day || ! $plan->target_date) {
            return null;
        }

        if (! $location->is_active) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        $schedules = $location->relationLoaded('schedules')
            ? $location->schedules
            : $location->schedules()->orderBy('start_at', 'asc')->get();

        if ($schedules->isEmpty()) {
            return [
                'level' => 'warning',
                'message' => "拠点「{$location->name}」は現在無効か、利用可能時間帯が登録されていません。",
            ];
        }

        [$startDateTime, $endDateTime] = $this->resolveStepDateTimeRange($plan);
        if (! $startDateTime || ! $endDateTime) {
            return null;
        }

        $matched = $schedules->first(function (LocationSchedule $schedule) use ($startDateTime, $endDateTime) {
            return $schedule->start_at <= $startDateTime && $endDateTime <= $schedule->end_at;
        });

        if ($matched) {
            if (! $matched->isAvailable()) {
                return [
                    'level' => 'warning',
                    'message' => "拠点「{$location->name}」の該当時間帯はまだ利用確保（利用可）になっていません。",
                ];
            }

            return null;
        }

        return [
            'level' => 'warning',
            'message' => "移動元「{$location->name}」の利用終了時刻を超過、またはイベントブース使用時間帯に入っています。利用可能時間内に搬出を完了してください。",
        ];
    }

    /**
     * 移動元・移動先のすべての拠点制約警告リストを取得
     *
     * @return array<int, array{level: string, message: string}>
     */
    public function getLocationWarnings(EquipmentLogisticsPlan $plan): array
    {
        return array_values(array_filter([
            $this->checkToLocationAvailability($plan),
            $this->checkFromLocationAvailability($plan),
        ]));
    }

    /**
     * ステップの開始・終了予定日時を解決
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function resolveStepDateTimeRange(EquipmentLogisticsPlan $plan): array
    {
        if (! $plan->target_date) {
            return [null, null];
        }

        $dateStr = $plan->target_date->format('Y-m-d');
        $startTimeStr = $plan->start_time ?: '12:00';
        $endTimeStr = $plan->end_time ?: $startTimeStr;

        try {
            $start = Carbon::parse($dateStr.' '.$startTimeStr);
            $end = Carbon::parse($dateStr.' '.$endTimeStr);
            if ($end < $start) {
                $end = $start->copy();
            }

            return [$start, $end];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    /**
     * 指定された日時における拠点の状態を評価
     */
    public function evaluateLocationStatus(Location $location, ?DateTimeInterface $dateTime = null): LocationStatusEvaluation
    {
        $dateTime = $dateTime
            ? Carbon::parse($dateTime)->timezone('Asia/Tokyo')
            : Carbon::now('Asia/Tokyo');

        if (! $location->is_active) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::Inactive,
                detail: '拠点は現在無効（非稼働）に設定されています。'
            );
        }

        if ($location->is_all_day) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::Available,
                detail: '終日利用可能です。'
            );
        }

        $schedules = $location->relationLoaded('schedules')
            ? $location->schedules
            : $location->schedules()->orderBy('start_at', 'asc')->get();

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

            if ($matched->isAvailable()) {
                return new LocationStatusEvaluation(
                    status: LocationCurrentStatus::Available,
                    detail: "{$slotTitle}で利用可能です（{$matched->end_at->format('H:i')}まで）。",
                    matchedSchedule: $matched
                );
            }

            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::UnavailablePending,
                detail: "{$slotTitle}は未確保（調整中）のため利用できません。",
                matchedSchedule: $matched
            );
        }

        // 枠外の場合
        $latestEnd = $schedules->max('end_at');
        if ($latestEnd && $dateTime > $latestEnd) {
            return new LocationStatusEvaluation(
                status: LocationCurrentStatus::NoSchedule,
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

    /**
     * 対象日付における拠点の1日利用枠ステータスを評価
     */
    public function evaluateDailyAvailability(Location $location, Carbon|string $targetDate): LocationDailyAvailabilityStatus
    {
        if ($location->is_all_day) {
            return LocationDailyAvailabilityStatus::Available;
        }

        $targetDateStr = $targetDate instanceof Carbon ? $targetDate->format('Y-m-d') : $targetDate;

        $schedules = $location->relationLoaded('schedules')
            ? $location->schedules
            : $location->schedules()->get();

        $todaySlots = $schedules->filter(function (LocationSchedule $s) use ($targetDateStr) {
            return $s->start_at->format('Y-m-d') === $targetDateStr || $s->end_at->format('Y-m-d') === $targetDateStr;
        });

        if ($todaySlots->isEmpty()) {
            return LocationDailyAvailabilityStatus::NoSchedule;
        }

        $hasUnavailable = $todaySlots->contains(function (LocationSchedule $s) {
            return ! $s->isAvailable();
        });

        if ($hasUnavailable) {
            return LocationDailyAvailabilityStatus::PartiallyUnavailable;
        }

        return LocationDailyAvailabilityStatus::Available;
    }
}
