<?php

namespace App\Services;

use App\DTOs\ParkingLocationStatus;
use App\DTOs\ParkingMonitoringContext;
use App\Enums\ParkingConstraintStatus;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LogisticsLocation;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ParkingConstraintService
{
    /**
     * 返却期限接近と判定する閾値（分）: 2時間
     */
    public const int WARNING_THRESHOLD_MINUTES = 120;

    /**
     * 駐車場利用時間制約監視コンテキストを取得
     */
    public function getMonitoringContext(?Carbon $now = null, bool $parkingOnly = true): ParkingMonitoringContext
    {
        $now = $this->normalizeToTokyo($now ?? Carbon::now('Asia/Tokyo'));

        $query = LogisticsLocation::active()->ordered();
        if ($parkingOnly) {
            $query->where('type', 'parking');
        } else {
            $query->where(function ($q) {
                $q->where('type', 'parking')->orWhere('is_all_day', false);
            });
        }

        $locations = $query->get();

        $locationStatuses = collect();
        $violationCount = 0;
        $warningCount = 0;
        $inUseCount = 0;

        foreach ($locations as $location) {
            $status = $this->evaluateLocationStatus($location, $now);
            $locationStatuses->push($status);

            if ($status->status === ParkingConstraintStatus::Violation) {
                $violationCount++;
            } elseif ($status->status === ParkingConstraintStatus::Warning) {
                $warningCount++;
            } elseif ($status->status === ParkingConstraintStatus::InUse) {
                $inUseCount++;
            }
        }

        return new ParkingMonitoringContext(
            now: $now,
            locations: $locationStatuses,
            violationCount: $violationCount,
            warningCount: $warningCount,
            inUseCount: $inUseCount
        );
    }

    /**
     * 個別拠点の利用ステータス判定
     */
    public function evaluateLocationStatus(LogisticsLocation $location, Carbon $now): ParkingLocationStatus
    {
        $now = $this->normalizeToTokyo($now);

        // 当該拠点に関わる資材計画（搬入・保管先、または搬出元）を取得
        $plansToLocation = EquipmentLogisticsPlan::with(['equipment.lane'])
            ->where('to_location_id', $location->id)
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $plansFromLocation = EquipmentLogisticsPlan::with(['equipment.lane'])
            ->where('from_location_id', $location->id)
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $periodLabel = $this->formatPeriodLabel($location);
        $violations = [];
        $warnings = [];

        // 終日利用可能拠点は時間制約なし
        if ($location->is_all_day) {
            return new ParkingLocationStatus(
                location: $location,
                status: ParkingConstraintStatus::InUse,
                statusDetail: '終日利用可',
                periodLabel: $periodLabel,
                countdownText: null,
                activePlans: $plansToLocation,
                storedEquipmentCount: $this->countStoredEquipments($plansToLocation),
                violations: [],
                warnings: []
            );
        }

        $from = $this->normalizeToTokyo($location->available_from);
        $to = $this->normalizeToTokyo($location->available_to);

        // 1. スケジュール上の計画違反チェック
        foreach ($plansToLocation as $plan) {
            $scheduledTime = $this->resolvePlanDateTime($plan);
            if (! $scheduledTime) {
                continue;
            }

            if ($from && $scheduledTime < $from) {
                $violations[] = "{$plan->equipment?->name}: 貸切開始（{$from->format('n/j H:i')}）前の搬入計画（{$scheduledTime->format('n/j H:i')}）";
            }
            if ($to && $scheduledTime > $to) {
                $violations[] = "{$plan->equipment?->name}: 貸切終了（{$to->format('n/j H:i')}）超過の配置計画（{$scheduledTime->format('n/j H:i')}）";
            }
        }

        // 2. 実時間・進行状況の判定
        $hasRemainingEquipment = $this->checkRemainingEquipment($plansToLocation, $plansFromLocation, $now);
        $countdownText = null;
        $status = ParkingConstraintStatus::Standby;
        $statusDetail = '待機中';

        if ($from && $now < $from) {
            // 貸切開始前
            $minutesToStart = (int) $now->diffInMinutes($from, false);
            $countdownText = '利用開始まで '.$this->formatDuration($minutesToStart);

            if ($this->hasEarlyArrival($plansToLocation)) {
                $violations[] = '貸切開始前ですが、すでに搬入・配置作業が着手されています。';
                $status = ParkingConstraintStatus::Violation;
                $statusDetail = '時間外フライング搬入';
            } elseif (! empty($violations)) {
                $status = ParkingConstraintStatus::Violation;
                $statusDetail = 'スケジュール制約違反あり';
            } else {
                $status = ParkingConstraintStatus::Standby;
                $statusDetail = '貸切前 (待機)';
            }
        } elseif ($to && $now > $to) {
            // 貸切終了時刻超過後
            $minutesOver = (int) $to->diffInMinutes($now, false);
            $countdownText = '返却期限超過 (+'.$this->formatDuration($minutesOver).')';

            if ($hasRemainingEquipment) {
                $violations[] = "貸切終了時刻（{$to->format('H:i')}）を超過していますが、資材が未撤収です。至急撤収・原状復帰を確認してください。";
                $status = ParkingConstraintStatus::Violation;
                $statusDetail = '返却期限超過・未撤収';
            } elseif (! empty($violations)) {
                $status = ParkingConstraintStatus::Violation;
                $statusDetail = '計画制約違反あり';
            } else {
                $status = ParkingConstraintStatus::Standby;
                $statusDetail = '返還完了 (原状復帰済)';
            }
        } else {
            // 貸切期間中 ($from <= $now <= $to)
            $minutesRemaining = $to ? (int) $now->diffInMinutes($to, false) : 9999;
            if ($to) {
                $countdownText = '返却期限まであと '.$this->formatDuration($minutesRemaining);
            }

            if (! empty($violations)) {
                $status = ParkingConstraintStatus::Violation;
                $statusDetail = '制約違反あり';
            } elseif ($to && $minutesRemaining <= self::WARNING_THRESHOLD_MINUTES && $hasRemainingEquipment) {
                $warnings[] = "貸切終了（{$to->format('H:i')}）まであと{$this->formatDuration($minutesRemaining)}です。資材の撤収・原状復帰を完了させてください。";
                $status = ParkingConstraintStatus::Warning;
                $statusDetail = "本日{$to->format('H:i')}返却必須 (撤収未完了)";
            } elseif ($hasRemainingEquipment || $plansToLocation->isNotEmpty()) {
                $status = ParkingConstraintStatus::InUse;
                $statusDetail = '利用中 (適正)';
            } else {
                $status = ParkingConstraintStatus::InUse;
                $statusDetail = '利用中 (適正)';
            }
        }

        return new ParkingLocationStatus(
            location: $location,
            status: $status,
            statusDetail: $statusDetail,
            periodLabel: $periodLabel,
            countdownText: $countdownText,
            activePlans: $plansToLocation,
            storedEquipmentCount: $this->countStoredEquipments($plansToLocation),
            violations: $violations,
            warnings: $warnings
        );
    }

    /**
     * 該当拠点の期間ラベルを生成
     */
    protected function formatPeriodLabel(LogisticsLocation $location): string
    {
        if ($location->is_all_day) {
            return '終日使用可能';
        }

        $from = $this->normalizeToTokyo($location->available_from);
        $to = $this->normalizeToTokyo($location->available_to);

        if (! $from && ! $to) {
            return '期間指定なし';
        }

        $fromStr = $from ? $from->format('n/j H:i') : '開始制限なし';
        $toStr = $to ? $to->format('n/j H:i') : '終了制限なし';

        return "{$fromStr} 〜 {$toStr}";
    }

    /**
     * 計画の予定日時を算出 (Asia/Tokyo)
     */
    protected function resolvePlanDateTime(EquipmentLogisticsPlan $plan): ?Carbon
    {
        if (! $plan->target_date) {
            return null;
        }

        $timeStr = $plan->start_time ?: '12:00';
        try {
            return Carbon::parse($plan->target_date->format('Y-m-d').' '.$timeStr, 'Asia/Tokyo');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 任意の日時を Asia/Tokyo の壁時計時刻として正規化
     */
    protected function normalizeToTokyo(?DateTimeInterface $dateTime): ?Carbon
    {
        if (! $dateTime) {
            return null;
        }

        return Carbon::parse($dateTime->format('Y-m-d H:i:s'), 'Asia/Tokyo');
    }

    /**
     * 貸切開始前にすでに搬入・進行されているか判定
     *
     * @param  EloquentCollection<int, EquipmentLogisticsPlan>  $plansToLocation
     */
    protected function hasEarlyArrival(EloquentCollection $plansToLocation): bool
    {
        return $plansToLocation->contains(function (EquipmentLogisticsPlan $plan) {
            return in_array($plan->status, ['in_progress', 'completed'], true);
        });
    }

    /**
     * 該当拠点に資材が残存している（または現在保管対象である）か判定
     *
     * @param  EloquentCollection<int, EquipmentLogisticsPlan>  $plansToLocation
     * @param  EloquentCollection<int, EquipmentLogisticsPlan>  $plansFromLocation
     */
    protected function checkRemainingEquipment(
        EloquentCollection $plansToLocation,
        EloquentCollection $plansFromLocation,
        Carbon $now
    ): bool {
        if ($plansToLocation->isEmpty()) {
            return false;
        }

        // 搬出元としての計画がすべて完了していれば残存なし
        if ($plansFromLocation->isNotEmpty()) {
            $allDispatched = $plansFromLocation->every(fn (EquipmentLogisticsPlan $p) => $p->status === 'completed');
            if ($allDispatched) {
                return false;
            }
        }

        // 搬入計画のうち、中止（cancelled）以外の計画が存在する場合は資材ありとみなす
        return $plansToLocation->contains(fn (EquipmentLogisticsPlan $p) => $p->status !== 'cancelled');
    }

    /**
     * 保管機材品目数をカウント
     *
     * @param  EloquentCollection<int, EquipmentLogisticsPlan>  $plansToLocation
     */
    protected function countStoredEquipments(EloquentCollection $plansToLocation): int
    {
        return $plansToLocation->pluck('equipment_id')->unique()->count();
    }

    /**
     * 分数を「○時間○分」または「○分」形式にフォーマット
     */
    protected function formatDuration(int $minutes): string
    {
        $minutes = abs($minutes);
        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;

        if ($hours > 0 && $rem > 0) {
            return "{$hours}時間{$rem}分";
        }
        if ($hours > 0) {
            return "{$hours}時間";
        }

        return "{$rem}分";
    }
}
