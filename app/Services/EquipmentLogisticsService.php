<?php

namespace App\Services;

use App\DTOs\ApplyEquipmentPatternDTO;
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

        foreach ($pattern->steps as $step) {
            $fromLocId = $step->from_location_id;
            $fromLocText = $step->from_location ?: ($step->fromLocationModel?->name ?? $storageLocationName);
            $toLocId = $step->to_location_id;
            $toLocText = $step->to_location ?: ($step->toLocationModel?->name ?? ($stagingLocation?->name ?? '（未定）'));

            // 拠点が別運行日マスタの場合、同一名拠点を対象運行日から解決
            if ($fromLocId && $step->fromLocationModel?->timetable_id !== $targetTimetable->id && $step->fromLocationModel?->name) {
                $matched = $this->locationRepository->findFirstByTimetableAndExactName($targetTimetable->id, $step->fromLocationModel->name);
                if ($matched) {
                    $fromLocId = $matched->id;
                }
            }
            if ($toLocId && $step->toLocationModel?->timetable_id !== $targetTimetable->id && $step->toLocationModel?->name) {
                $matched = $this->locationRepository->findFirstByTimetableAndExactName($targetTimetable->id, $step->toLocationModel->name);
                if ($matched) {
                    $toLocId = $matched->id;
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
     * タイムテーブル選択画面用のタイムテーブル一覧を取得
     *
     * @return Collection<int, Timetable>
     */
    public function getTimetablesForSelect(): Collection
    {
        return $this->timetableRepository->getAllWithLogisticsPlansCount();
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
     * カスタム搬出入パターン一覧の取得
     *
     * @return Collection<int, EquipmentLogisticsPattern>
     */
    public function getPatternsData(): Collection
    {
        return $this->patternRepository->getAllWithSteps();
    }
}
