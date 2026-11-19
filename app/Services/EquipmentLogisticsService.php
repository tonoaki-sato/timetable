<?php

namespace App\Services;

use App\DTOs\ApplyEquipmentPatternDTO;
use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentPlanStatus;
use App\Enums\EquipmentType;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPatternStep;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use App\Repositories\Contracts\EquipmentLogisticsPlanRepositoryInterface;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class EquipmentLogisticsService
{
    protected EquipmentRepositoryInterface $equipmentRepository;

    protected LocationRepositoryInterface $locationRepository;

    protected TimetableRepositoryInterface $timetableRepository;

    protected StaffRepositoryInterface $staffRepository;

    public function __construct(
        protected EquipmentLogisticsPlanRepositoryInterface $planRepository,
        protected EquipmentLogisticsPatternRepositoryInterface $patternRepository,
        ?EquipmentRepositoryInterface $equipmentRepository = null,
        ?LocationRepositoryInterface $locationRepository = null,
        ?TimetableRepositoryInterface $timetableRepository = null,
        ?StaffRepositoryInterface $staffRepository = null,
    ) {
        $this->equipmentRepository = $equipmentRepository ?? app(EquipmentRepositoryInterface::class);
        $this->locationRepository = $locationRepository ?? app(LocationRepositoryInterface::class);
        $this->timetableRepository = $timetableRepository ?? app(TimetableRepositoryInterface::class);
        $this->staffRepository = $staffRepository ?? app(StaffRepositoryInterface::class);
    }

    /**
     * まつりの代表日程（事前準備、前日、1日目、2日目、翌日）を取得
     *
     * @return array{other: ?string, pre_day: ?string, day1: ?string, day2: ?string, post_day: ?string}
     */
    public function resolveFestivalDates(): array
    {
        $timetables = $this->timetableRepository->getAll();
        $day1 = $timetables->first()?->target_date?->format('Y-m-d') ?? '2026-10-11';
        $day2 = $timetables->skip(1)->first()?->target_date?->format('Y-m-d') ?? '2026-10-12';

        $preDay = Carbon::parse($day1)->subDay()->format('Y-m-d');
        $otherDay = Carbon::parse($day1)->subDays(2)->format('Y-m-d');
        $postDay = Carbon::parse($day2)->addDay()->format('Y-m-d');

        return [
            'other' => $otherDay,
            'pre_day' => $preDay,
            'day1' => $day1,
            'day2' => $day2,
            'post_day' => $postDay,
        ];
    }

    /**
     * カスタム搬出入パターン（EquipmentLogisticsPattern）を対象資材に適用してステップを一括生成
     *
     * @param  ApplyEquipmentPatternDTO|array<string, mixed>  $options
     */
    public function applyPattern(Equipment $equipment, EquipmentLogisticsPattern $pattern, ApplyEquipmentPatternDTO|array $options = [], ?Timetable $timetable = null): int
    {
        $dto = $options instanceof ApplyEquipmentPatternDTO
            ? $options
            : ApplyEquipmentPatternDTO::fromArray(array_merge(['pattern_id' => $pattern->id], $options));
        $override = $dto->overrideExisting;

        $targetTimetable = $timetable ?? $this->timetableRepository->findFirstActiveOrEarliest();
        if (! $targetTimetable) {
            return 0;
        }

        if ($override) {
            $this->planRepository->clearForEquipment($equipment, $targetTimetable);
        }

        $dates = $this->resolveFestivalDates();
        $targetDate = $targetTimetable->target_date?->format('Y-m-d') ?? $dates['day1'];

        $stagingLocation = $dto->stagingLocationId
            ? $this->locationRepository->findById($dto->stagingLocationId)
            : $this->locationRepository->findFirstByTimetableAndNameLike($targetTimetable->id, '税務署');

        $storageLocationName = $equipment->storage_location
            ?: ($equipment->isPersonal() ? '所有者（個人持ち込み・持ち帰り）' : '番所地下倉庫');

        $witnessStaffId = $dto->witnessStaffId;
        $qty = (int) $equipment->quantity;

        $pattern->loadMissing(['steps.fromLocationModel', 'steps.toLocationModel']);

        $createdCount = 0;
        $locationIdByName = null;

        foreach ($pattern->steps as $step) {
            $fromLocId = $step->from_location_id;
            $fromLocText = $step->from_location ?: ($step->fromLocationModel?->name ?? $storageLocationName);
            $toLocId = $step->to_location_id;
            $toLocText = $step->to_location ?: ($step->toLocationModel?->name ?? ($stagingLocation?->name ?? '（未定）'));

            // 拠点が別運行日マスタの場合、同一名拠点を対象運行日から解決
            if ($fromLocId && $step->fromLocationModel?->timetable_id !== $targetTimetable->id && $step->fromLocationModel?->name) {
                $locationIdByName ??= $this->locationRepository->getNameIdMapForTimetable($targetTimetable->id);
                if (isset($locationIdByName[$step->fromLocationModel->name])) {
                    $fromLocId = $locationIdByName[$step->fromLocationModel->name];
                }
            }
            if ($toLocId && $step->toLocationModel?->timetable_id !== $targetTimetable->id && $step->toLocationModel?->name) {
                $locationIdByName ??= $this->locationRepository->getNameIdMapForTimetable($targetTimetable->id);
                if (isset($locationIdByName[$step->toLocationModel->name])) {
                    $toLocId = $locationIdByName[$step->toLocationModel->name];
                }
            }

            $stepDate = match ($step->day_phase) {
                'other' => $dates['other'] ?? $targetDate,
                'pre_day' => $dates['pre_day'] ?? $targetDate,
                'day1_morning', 'day1_evening' => $dates['day1'] ?? $targetDate,
                'day2_morning', 'day2_evening' => $dates['day2'] ?? $targetDate,
                'post_day' => $dates['post_day'] ?? $targetDate,
                default => $targetDate,
            };

            $title = str_replace(
                ['{from_location}', '{to_location}', '{storage_location}'],
                [$fromLocText, $toLocText, $storageLocationName],
                $step->title
            );

            $this->planRepository->create([
                'timetable_id' => $targetTimetable->id,
                'equipment_id' => $equipment->id,
                'target_date' => $stepDate,
                'day_phase' => $step->day_phase,
                'action_type' => $step->action_type,
                'title' => $title,
                'quantity' => $qty,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => $fromLocText,
                'to_location' => $toLocText,
                'from_location_id' => $fromLocId,
                'to_location_id' => $toLocId,
                'operator_type' => $step->operator_type,
                'witness_required' => $step->witness_required,
                'witness_staff_id' => $step->witness_required ? $witnessStaffId : null,
                'status' => 'planned',
                'notes' => $step->notes,
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    /**
     * パターンIDからカスタムパターンを取得して対象資材へ適用
     *
     * @return array{pattern: EquipmentLogisticsPattern, count: int, equipment: Equipment}
     */
    public function applyPatternById(Equipment $equipment, ApplyEquipmentPatternDTO $dto, Timetable $timetable): array
    {
        $pattern = $this->patternRepository->findWithSteps($dto->patternId);
        if (! $pattern) {
            throw new DomainException('指定された搬出入パターンが見つかりません。');
        }

        $count = $this->applyPattern($equipment, $pattern, $dto, $timetable);
        $this->equipmentRepository->loadPlansForTimetable($equipment, $timetable);

        return [
            'pattern' => $pattern,
            'count' => $count,
            'equipment' => $equipment,
        ];
    }

    /**
     * 搬出入計画ステップを作成し関連リレーションをロードして返却
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createPlan(Timetable $timetable, array $attributes): EquipmentLogisticsPlan
    {
        $attributes['timetable_id'] = $timetable->id;
        $plan = $this->planRepository->create($attributes);

        return $plan->load(['fromLocationModel', 'toLocationModel', 'witnessStaff']);
    }

    /**
     * 搬出入計画ステップを更新し関連リレーションをロードして返却
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updatePlan(EquipmentLogisticsPlan $plan, array $attributes): EquipmentLogisticsPlan
    {
        $this->planRepository->update($plan, $attributes);

        return $plan->load(['fromLocationModel', 'toLocationModel', 'witnessStaff']);
    }

    /**
     * 搬出入計画ステップを削除
     */
    public function deletePlan(EquipmentLogisticsPlan $plan): bool
    {
        return $this->planRepository->delete($plan);
    }

    /**
     * 搬出入パターンを新規作成
     *
     * @param  array{name: string, target_type: string, description?: ?string, display_order?: ?int}  $attributes
     * @param  array<int, array<string, mixed>>  $stepsData
     */
    public function createPatternWithSteps(array $attributes, array $stepsData, ?int $userId = null): EquipmentLogisticsPattern
    {
        return $this->patternRepository->createWithSteps($attributes, $stepsData, $userId);
    }

    /**
     * 搬出入パターンを更新
     *
     * @param  array{name: string, target_type: string, description?: ?string, display_order?: ?int}  $attributes
     * @param  array<int, array<string, mixed>>  $stepsData
     */
    public function updatePatternWithSteps(EquipmentLogisticsPattern $pattern, array $attributes, array $stepsData): EquipmentLogisticsPattern
    {
        $this->patternRepository->updateWithSteps($pattern, $attributes, $stepsData);

        return $pattern->load('steps');
    }

    /**
     * 搬出入パターンを削除
     */
    public function deletePattern(EquipmentLogisticsPattern $pattern): bool
    {
        return $this->patternRepository->delete($pattern);
    }

    /**
     * 搬出入パターンにステップを単体追加
     *
     * @param  array<string, mixed>  $data
     */
    public function addPatternStep(EquipmentLogisticsPattern $pattern, array $data): EquipmentLogisticsPatternStep
    {
        return $this->patternRepository->createStep($pattern, $data);
    }

    /**
     * 搬出入パターンのステップを単体更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePatternStep(EquipmentLogisticsPatternStep $step, array $data): bool
    {
        return $this->patternRepository->updateStep($step, $data);
    }

    /**
     * 搬出入パターンのステップを単体削除
     */
    public function deletePatternStep(EquipmentLogisticsPatternStep $step): bool
    {
        return $this->patternRepository->deleteStep($step);
    }

    /**
     * パターン内ステップの表示順を一括更新
     *
     * @param  array<int>  $stepIds
     */
    public function reorderPatternSteps(EquipmentLogisticsPattern $pattern, array $stepIds): void
    {
        $this->patternRepository->reorderSteps($pattern, $stepIds);
    }

    /**
     * パターン一覧画面用のデータを取得
     *
     * @return array{patterns: Collection<int, EquipmentLogisticsPattern>, locations: Collection<int, Location>}
     */
    public function getPatternIndexViewData(): array
    {
        return [
            'patterns' => $this->patternRepository->getAllWithSteps(),
            'locations' => $this->locationRepository->getUniqueNamesOrdered(),
        ];
    }

    /**
     * リクエスト入力の equipment_ids を正の整数配列へ正規化（未指定時は null）
     *
     * @return array<int, int>|null
     */
    public function parseEquipmentIds(mixed $input): ?array
    {
        if ($input === null || $input === '' || $input === []) {
            return null;
        }

        $rawIds = is_array($input) ? $input : explode(',', (string) $input);

        return array_values(array_unique(array_filter(
            array_map('intval', $rawIds),
            fn (int $id): bool => $id > 0
        )));
    }

    /**
     * 指定タイムテーブルの搬出入計画一覧画面用データを取得
     *
     * @param  array<int, int>|null  $selectedEquipmentIds
     * @return array<string, mixed>
     */
    public function getLogisticsIndexViewData(Timetable $timetable, ?array $selectedEquipmentIds = null): array
    {
        $equipments = $this->getEquipmentsFlowData(null, $timetable);
        $visibleEquipmentCount = $selectedEquipmentIds === null
            ? $equipments->count()
            : $equipments->whereIn('id', $selectedEquipmentIds)->count();

        return [
            'timetable' => $timetable,
            'equipments' => $equipments,
            'selectedEquipmentIds' => $selectedEquipmentIds,
            'visibleEquipmentCount' => $visibleEquipmentCount,
            'patterns' => $this->patternRepository->getAllWithSteps(),
            'locations' => $this->locationRepository->getActiveOrderedForTimetable($timetable),
            'staffs' => $this->staffRepository->getActiveStaffList(),
        ];
    }

    /**
     * 資材別フロー一覧用のデータ取得
     *
     * @param  array<int, int>|null  $equipmentIds
     * @return Collection<int, Equipment>
     */
    public function getEquipmentsFlowData(?string $type = null, ?Timetable $timetable = null, ?array $equipmentIds = null): Collection
    {
        return $this->planRepository->getEquipmentsFlowData($type, $timetable, $equipmentIds);
    }

    /**
     * 搬出入タイムライン画面用の全表示データを構築
     *
     * @return array<string, mixed>
     */
    public function getLogisticsTimelineViewData(Timetable $timetable): array
    {
        $equipments = $this->getEquipmentsFlowData(null, $timetable);

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
     * @param  \Illuminate\Support\Collection<int, EquipmentLogisticsPlan>  $datePlans
     * @return array<int, array{time: string}>
     */
    private function generateTimelineSlotsForDate(\Illuminate\Support\Collection $datePlans, Timetable $timetable, bool $isTimetableDate): array
    {
        $minMinutes = null;
        $maxMinutes = null;

        if ($isTimetableDate) {
            if (! empty($timetable->start_time)) {
                $minMinutes = $this->timeStringToMinutes(substr($timetable->start_time, 0, 5));
            }
            if (! empty($timetable->end_time)) {
                $maxMinutes = $this->timeStringToMinutes(substr($timetable->end_time, 0, 5));
            }
        }

        foreach ($datePlans as $plan) {
            if (! blank($plan->start_time)) {
                $startMins = $this->timeStringToMinutes(substr((string) $plan->start_time, 0, 5));
                $minMinutes = $minMinutes === null ? $startMins : min($minMinutes, $startMins);

                $endMins = ! blank($plan->end_time)
                    ? $this->timeStringToMinutes(substr((string) $plan->end_time, 0, 5))
                    : $startMins + 30;
                $maxMinutes = $maxMinutes === null ? $endMins : max($maxMinutes, $endMins);
            }
        }

        $minMinutes ??= $this->timeStringToMinutes('08:00');
        $maxMinutes ??= $this->timeStringToMinutes('18:00');

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
     * HH:MM 形式の時刻を分単位の整数へ変換
     */
    private function timeStringToMinutes(string $timeStr): int
    {
        $parts = explode(':', $timeStr);
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
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

                $slotStartMins = $this->timeStringToMinutes($slot['time']);
                $slotEndMins = $slotStartMins + 30;

                $match = $timedPlans->first(function (EquipmentLogisticsPlan $plan) use ($slotStartMins, $slotEndMins): bool {
                    $pStart = $this->timeStringToMinutes(substr((string) $plan->start_time, 0, 5));

                    return $pStart >= $slotStartMins && $pStart < $slotEndMins;
                });

                if (! $match) {
                    $match = $timedPlans->first(function (EquipmentLogisticsPlan $plan) use ($slotStartMins): bool {
                        $pStart = $this->timeStringToMinutes(substr((string) $plan->start_time, 0, 5));
                        $pEnd = ! blank($plan->end_time)
                            ? $this->timeStringToMinutes(substr((string) $plan->end_time, 0, 5))
                            : $pStart + 30;

                        return $pStart <= $slotStartMins && $pEnd > $slotStartMins;
                    });
                }

                if ($match) {
                    $pStart = $this->timeStringToMinutes(substr((string) $match->start_time, 0, 5));
                    $pEnd = ! blank($match->end_time)
                        ? $this->timeStringToMinutes(substr((string) $match->end_time, 0, 5))
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
     * @return array<int, array<string, mixed>>
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
