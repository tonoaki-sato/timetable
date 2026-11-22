<?php

namespace App\Services;

use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentPlanStatus;
use App\Enums\EquipmentType;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentLogisticsPlanRepositoryInterface;
use App\Support\TimeSlotHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class EquipmentLogisticsTimelineService
{
    public function __construct(
        protected EquipmentLogisticsPlanRepositoryInterface $planRepository,
    ) {}

    /**
     * 搬出入タイムライン画面用の全表示データを構築
     *
     * @return array<string, mixed>
     */
    public function getLogisticsTimelineViewData(Timetable $timetable): array
    {
        $equipments = $this->planRepository->getEquipmentsFlowData(null, $timetable);

        $phaseOrder = [];
        foreach (EquipmentDayPhase::cases() as $index => $phase) {
            $phaseOrder[$phase->value] = $index + 1;
        }

        $allPlans = $equipments->flatMap->plans->sort(function (EquipmentLogisticsPlan $a, EquipmentLogisticsPlan $b) use ($phaseOrder): int {
            $phaseA = $phaseOrder[$a->day_phase_enum->value] ?? 99;
            $phaseB = $phaseOrder[$b->day_phase_enum->value] ?? 99;
            if ($phaseA !== $phaseB) {
                return $phaseA <=> $phaseB;
            }

            $dateA = $a->target_date?->format('Y-m-d');
            $dateB = $b->target_date?->format('Y-m-d');
            if (($dateA === null) !== ($dateB === null)) {
                return ($dateA === null) <=> ($dateB === null);
            }
            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            $startA = $a->start_time;
            $startB = $b->start_time;
            if (($startA === null) !== ($startB === null)) {
                return ($startA === null) <=> ($startB === null);
            }
            if ($startA !== $startB) {
                return $startA <=> $startB;
            }

            $endA = $a->end_time;
            $endB = $b->end_time;
            if (($endA === null) !== ($endB === null)) {
                return ($endA === null) <=> ($endB === null);
            }
            if ($endA !== $endB) {
                return $endA <=> $endB;
            }

            return $a->id <=> $b->id;
        })->values();

        $timetableDateStr = $timetable->target_date?->format('Y-m-d');

        $dates = $allPlans
            ->pluck('target_date')
            ->filter()
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->unique()
            ->values();

        if ($timetableDateStr !== null && ! $dates->contains($timetableDateStr)) {
            $dates->push($timetableDateStr);
        }

        $dates = $dates->sort()->values();

        $dateBlocks = [];
        foreach ($dates as $dateStr) {
            $datePlans = $allPlans->filter(
                fn (EquipmentLogisticsPlan $plan): bool => $plan->target_date?->format('Y-m-d') === $dateStr
            );

            $isTimetableDate = $timetableDateStr !== null && $dateStr === $timetableDateStr;
            $timeSlots = $this->generateTimelineSlotsForDate($datePlans, $timetable, $isTimetableDate);
            $grid = $this->buildLogisticsTimelineGrid($timeSlots, $equipments, $dateStr);
            $unspecifiedTimePlans = $datePlans->filter(
                fn (EquipmentLogisticsPlan $plan): bool => blank($plan->start_time)
            )->values();

            $carbonDate = Carbon::parse($dateStr);
            $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$carbonDate->dayOfWeek];

            $dateBlocks[] = [
                'date' => $dateStr,
                'date_formatted' => $carbonDate->format('Y年m月d日')." ({$dayOfWeek})",
                'label' => $this->resolveTimelineDateLabel($dateStr, $timetableDateStr),
                'is_timetable_date' => $isTimetableDate,
                'plans_count' => $datePlans->count(),
                'time_slots' => $timeSlots,
                'grid' => $grid,
                'unspecified_time_plans' => $unspecifiedTimePlans,
            ];
        }

        $unspecifiedDatePlans = $allPlans->filter(
            fn (EquipmentLogisticsPlan $plan): bool => $plan->target_date === null
        )->values();

        $equipmentMeta = $this->buildEquipmentTimelineMeta($equipments);

        return [
            'timetable' => $timetable,
            'equipments' => $equipments,
            'dateBlocks' => $dateBlocks,
            'unspecifiedDatePlans' => $unspecifiedDatePlans,
            'equipmentMeta' => $equipmentMeta,
            'totalEquipmentCount' => $equipments->count(),
            'committeeCount' => $equipments->filter(fn (Equipment $e): bool => $e->type_enum === EquipmentType::Committee)->count(),
            'rentalCount' => $equipments->filter(fn (Equipment $e): bool => $e->type_enum === EquipmentType::Rental)->count(),
            'personalCount' => $equipments->filter(fn (Equipment $e): bool => $e->type_enum === EquipmentType::Personal)->count(),
            'plannedCount' => $allPlans->filter(fn (EquipmentLogisticsPlan $p): bool => $p->status_enum === EquipmentPlanStatus::Planned)->count(),
            'inProgressCount' => $allPlans->filter(fn (EquipmentLogisticsPlan $p): bool => $p->status_enum === EquipmentPlanStatus::InProgress)->count(),
            'completedCount' => $allPlans->filter(fn (EquipmentLogisticsPlan $p): bool => $p->status_enum === EquipmentPlanStatus::Completed)->count(),
        ];
    }

    /**
     * 運行日との位置関係から日付ブロック用のラベルを判定
     */
    private function resolveTimelineDateLabel(string $dateStr, ?string $timetableDateStr): string
    {
        if ($timetableDateStr === null) {
            return '搬出入実施日';
        }

        $diffDays = (int) Carbon::parse($timetableDateStr)->diffInDays(Carbon::parse($dateStr), false);

        return match (true) {
            $diffDays === 0 => '運行日 当日',
            $diffDays === -1 => '前日準備',
            $diffDays < -1 => '事前準備',
            $diffDays === 1 => '翌日撤収・回収',
            default => '後日回収',
        };
    }

    /**
     * 特定日付における30分刻みの時間スロット配列を生成
     *
     * @param  SupportCollection<int, EquipmentLogisticsPlan>  $datePlans
     * @return array<int, array{time: string}>
     */
    private function generateTimelineSlotsForDate(SupportCollection $datePlans, Timetable $timetable, bool $isTimetableDate): array
    {
        $minMinutes = null;
        $maxMinutes = null;

        if ($isTimetableDate) {
            if (! empty($timetable->start_time)) {
                $minMinutes = TimeSlotHelper::hhmmToMinutes(substr($timetable->start_time, 0, 5));
            }
            if (! empty($timetable->end_time)) {
                $maxMinutes = TimeSlotHelper::hhmmToMinutes(substr($timetable->end_time, 0, 5));
            }
        }

        foreach ($datePlans as $plan) {
            if (! blank($plan->start_time)) {
                $startMins = TimeSlotHelper::hhmmToMinutes(substr((string) $plan->start_time, 0, 5));
                $minMinutes = $minMinutes === null ? $startMins : min($minMinutes, $startMins);

                $endMins = ! blank($plan->end_time)
                    ? TimeSlotHelper::hhmmToMinutes(substr((string) $plan->end_time, 0, 5))
                    : $startMins + 30;
                $maxMinutes = $maxMinutes === null ? $endMins : max($maxMinutes, $endMins);
            }
        }

        $minMinutes ??= TimeSlotHelper::hhmmToMinutes('08:00');
        $maxMinutes ??= TimeSlotHelper::hhmmToMinutes('18:00');

        // 30分単位に丸め（開始は切り捨て、終了は切り上げ）
        $startSlotMins = (int) (floor($minMinutes / 30) * 30);
        $endSlotMins = (int) (ceil($maxMinutes / 30) * 30);

        if ($endSlotMins <= $startSlotMins) {
            $endSlotMins = $startSlotMins + 30;
        }

        $slots = [];
        for ($mins = $startSlotMins; $mins < $endSlotMins; $mins += 30) {
            $h = (int) floor($mins / 60);
            $m = $mins % 60;
            $slots[] = ['time' => sprintf('%02d:%02d', $h, $m)];
        }

        return $slots;
    }

    /**
     * 機材ごとのガントチャートグリッドを構築
     *
     * @param  array<int, array{time: string}>  $timeSlots
     * @param  Collection<int, Equipment>  $equipments
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function buildLogisticsTimelineGrid(array $timeSlots, Collection $equipments, string $dateStr): array
    {
        $grid = [];
        $totalSlots = count($timeSlots);

        foreach ($equipments as $equipment) {
            $rowGrid = [];
            $coveredUntil = -1;

            $timedPlans = $equipment->plans->filter(
                fn (EquipmentLogisticsPlan $plan): bool => $plan->target_date?->format('Y-m-d') === $dateStr
                    && ! blank($plan->start_time)
            );

            foreach ($timeSlots as $sIdx => $slot) {
                if ($coveredUntil >= $sIdx) {
                    $rowGrid[$sIdx] = ['type' => 'skip'];

                    continue;
                }

                $slotStartMins = TimeSlotHelper::hhmmToMinutes($slot['time']);
                $slotEndMins = $slotStartMins + 30;

                $match = $timedPlans->first(function (EquipmentLogisticsPlan $plan) use ($slotStartMins, $slotEndMins): bool {
                    $pStart = TimeSlotHelper::hhmmToMinutes(substr((string) $plan->start_time, 0, 5));

                    return $pStart >= $slotStartMins && $pStart < $slotEndMins;
                });

                if (! $match) {
                    $match = $timedPlans->first(function (EquipmentLogisticsPlan $plan) use ($slotStartMins): bool {
                        $pStart = TimeSlotHelper::hhmmToMinutes(substr((string) $plan->start_time, 0, 5));
                        $pEnd = ! blank($plan->end_time)
                            ? TimeSlotHelper::hhmmToMinutes(substr((string) $plan->end_time, 0, 5))
                            : $pStart + 30;

                        return $pStart <= $slotStartMins && $pEnd > $slotStartMins;
                    });
                }

                if ($match) {
                    $pStart = TimeSlotHelper::hhmmToMinutes(substr((string) $match->start_time, 0, 5));
                    $pEnd = ! blank($match->end_time)
                        ? TimeSlotHelper::hhmmToMinutes(substr((string) $match->end_time, 0, 5))
                        : $pStart + 30;

                    if ($pEnd <= $pStart) {
                        $pEnd = $pStart + 30;
                    }

                    $spanStartMins = max($slotStartMins, $pStart);
                    $durationMins = max(30, $pEnd - $spanStartMins);
                    $colspan = (int) ceil($durationMins / 30);
                    $colspan = max(1, min($colspan, $totalSlots - $sIdx));

                    $coveredUntil = $sIdx + $colspan - 1;

                    $rowGrid[$sIdx] = [
                        'type' => 'plan',
                        'colspan' => $colspan,
                        'plan' => $match,
                        'badge_bg' => $this->resolveActionBadgeClass($match),
                    ];
                } else {
                    $rowGrid[$sIdx] = ['type' => 'empty'];
                }
            }

            $grid[$equipment->id] = $rowGrid;
        }

        return $grid;
    }

    /**
     * アクション種別・ステータスに応じたガントブロック配色クラスを取得
     */
    private function resolveActionBadgeClass(EquipmentLogisticsPlan $plan): string
    {
        if ($plan->isCancelled()) {
            return 'bg-slate-100 border-slate-300 text-slate-500 opacity-75 line-through';
        }

        return match ($plan->action_type_enum) {
            EquipmentActionType::StorageOut => 'bg-sky-50 border-sky-300 text-sky-900',
            EquipmentActionType::VendorReceive => 'bg-purple-50 border-purple-300 text-purple-900',
            EquipmentActionType::StagingMove => 'bg-indigo-50 border-indigo-300 text-indigo-900',
            EquipmentActionType::Setup => 'bg-emerald-50 border-emerald-300 text-emerald-900',
            EquipmentActionType::NightStorage => 'bg-amber-50 border-amber-300 text-amber-900',
            EquipmentActionType::ReturnStorage => 'bg-teal-50 border-teal-300 text-teal-900',
            EquipmentActionType::CollectionStaging => 'bg-orange-50 border-orange-300 text-orange-900',
            EquipmentActionType::VendorSetup => 'bg-rose-50 border-rose-300 text-rose-900',
            EquipmentActionType::VendorPickup => 'bg-slate-100 border-slate-300 text-slate-800',
            EquipmentActionType::Other => 'bg-slate-50 border-slate-300 text-slate-800',
        };
    }

    /**
     * 資材ごとの絞り込み用メタデータを構築
     *
     * @param  Collection<int, Equipment>  $equipments
     * @return array<int, array{plans_count: int, has_planned: bool, has_in_progress: bool, has_completed: bool, unspecified_time_count: int}>
     */
    private function buildEquipmentTimelineMeta(Collection $equipments): array
    {
        $meta = [];

        foreach ($equipments as $equipment) {
            $plans = $equipment->plans;
            $meta[$equipment->id] = [
                'plans_count' => $plans->count(),
                'has_planned' => $plans->contains(fn (EquipmentLogisticsPlan $p): bool => $p->status_enum === EquipmentPlanStatus::Planned),
                'has_in_progress' => $plans->contains(fn (EquipmentLogisticsPlan $p): bool => $p->status_enum === EquipmentPlanStatus::InProgress),
                'has_completed' => $plans->contains(fn (EquipmentLogisticsPlan $p): bool => $p->status_enum === EquipmentPlanStatus::Completed),
                'unspecified_time_count' => $plans->filter(fn (EquipmentLogisticsPlan $p): bool => $p->isTimeUnspecified())->count(),
            ];
        }

        return $meta;
    }
}
