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
use App\Repositories\Contracts\LaneRepositoryInterface;
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

    protected LaneRepositoryInterface $laneRepository;

    protected StaffRepositoryInterface $staffRepository;

    public function __construct(
        protected EquipmentLogisticsPlanRepositoryInterface $planRepository,
        protected EquipmentLogisticsPatternRepositoryInterface $patternRepository,
        ?EquipmentRepositoryInterface $equipmentRepository = null,
        ?LocationRepositoryInterface $locationRepository = null,
        ?TimetableRepositoryInterface $timetableRepository = null,
        ?LaneRepositoryInterface $laneRepository = null,
        ?StaffRepositoryInterface $staffRepository = null,
    ) {
        $this->equipmentRepository = $equipmentRepository ?? app(EquipmentRepositoryInterface::class);
        $this->locationRepository = $locationRepository ?? app(LocationRepositoryInterface::class);
        $this->timetableRepository = $timetableRepository ?? app(TimetableRepositoryInterface::class);
        $this->laneRepository = $laneRepository ?? app(LaneRepositoryInterface::class);
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
        $order = 10;

        foreach ($pattern->steps as $step) {
            $fromLocId = $step->from_location_id;
            $fromLocText = $step->fromLocationModel?->name ?? $storageLocationName;
            $toLocId = $step->to_location_id;
            $toLocText = $step->toLocationModel?->name ?? ($stagingLocation?->name ?? '（未定）');

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
                'display_order' => $step->display_order ?: $order,
            ]);

            $createdCount++;
            $order += 10;
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
        $this->equipmentRepository->loadLogisticsShowDetails($equipment, $timetable);

        return [
            'pattern' => $pattern,
            'count' => $count,
            'equipment' => $equipment,
        ];
    }

    /**
     * 資材に登録済みの計画ステップ群を新しい搬出入パターンとして保存
     *
     * @param  array{name: string, target_type?: ?string, description?: ?string}  $data
     */
    public function saveEquipmentPlansAsPattern(Equipment $equipment, array $data, ?int $userId = null): EquipmentLogisticsPattern
    {
        $plans = $this->equipmentRepository->getPlansOrdered($equipment);
        $stepsData = [];
        $order = 10;

        foreach ($plans as $plan) {
            $stepsData[] = [
                'day_phase' => $plan->day_phase,
                'action_type' => $plan->action_type,
                'title' => $plan->title,
                'from_location_id' => $plan->from_location_id,
                'to_location_id' => $plan->to_location_id,
                'operator_type' => $plan->operator_type,
                'witness_required' => $plan->witness_required,
                'notes' => $plan->notes,
                'display_order' => $order,
            ];
            $order += 10;
        }

        return $this->patternRepository->createWithSteps([
            'name' => $data['name'],
            'target_type' => $data['target_type'] ?: $equipment->type,
            'description' => $data['description'] ?? "{$equipment->name} の搬出入ステップから作成されたパターン",
        ], $stepsData, $userId);
    }

    /**
     * 資材に紐づく搬出入ステップ件数を取得
     */
    public function countEquipmentPlans(Equipment $equipment): int
    {
        return $this->equipmentRepository->countPlans($equipment);
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
     * 特定資材・指定運行日の搬出入計画ステップを一括クリア
     */
    public function clearPlansForEquipment(Equipment $equipment, Timetable $timetable): int
    {
        return $this->planRepository->clearForEquipment($equipment, $timetable);
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
     * 指定タイムテーブルの搬出入計画一覧画面用データを取得
     *
     * @return array<string, mixed>
     */
    public function getLogisticsIndexViewData(Timetable $timetable, ?string $typeFilter = null, ?int $laneFilter = null): array
    {
        $locations = $this->locationRepository->getActiveOrderedForTimetable($timetable);
        $lanes = $this->laneRepository->getOrderedWithLeadForTimetable($timetable);
        $equipments = $this->getEquipmentsFlowData($typeFilter, $laneFilter, $timetable);

        $allPlans = $this->planRepository->getForTimetable($timetable);
        $totalSteps = $allPlans->count();
        $completedSteps = $allPlans->where('status', 'completed')->count();
        $stats = [
            'total_equipments' => $this->equipmentRepository->getTotalCount(),
            'planned_equipments' => $allPlans->pluck('equipment_id')->unique()->count(),
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'witness_required_steps' => $allPlans->where('witness_required', true)->count(),
            'progress_percent' => $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0,
        ];

        return [
            'timetable' => $timetable,
            'locations' => $locations,
            'lanes' => $lanes,
            'equipments' => $equipments,
            'stats' => $stats,
            'typeFilter' => $typeFilter,
            'laneFilter' => $laneFilter,
        ];
    }

    /**
     * 搬出入計画の印刷プレビュー画面用データを取得
     *
     * @return array<string, mixed>
     */
    public function getLogisticsPrintPreviewData(
        Timetable $timetable,
        string $format = 'all',
        ?string $typeFilter = null,
        ?int $laneFilter = null,
        ?string $phaseFilter = null
    ): array {
        return [
            'timetable' => $timetable,
            'format' => $format,
            'typeFilter' => $typeFilter,
            'laneFilter' => $laneFilter,
            'phaseFilter' => $phaseFilter,
            'equipments' => $this->getEquipmentsFlowData($typeFilter, $laneFilter, $timetable),
            'checklistPlans' => $this->getPhaseChecklistData($phaseFilter, $laneFilter, $typeFilter, $timetable),
            'locationsData' => $this->getLocationsStatusData($timetable),
            'onsiteNightPlans' => $this->getOnsiteNightStoragePlans($timetable),
            'eventCompanyPlans' => $this->getEventCompanyScheduleData($timetable),
            'festivalDates' => $this->resolveFestivalDates(),
            'lanes' => $this->laneRepository->getOrderedWithLeadForTimetable($timetable),
        ];
    }

    /**
     * 個別資材の搬出入計画詳細画面用データを取得
     *
     * @return array<string, mixed>
     */
    public function getLogisticsShowViewData(Timetable $timetable, Equipment $equipment): array
    {
        $this->equipmentRepository->loadLogisticsShowDetails($equipment, $timetable);

        return [
            'timetable' => $timetable,
            'equipment' => $equipment,
            'locations' => $this->locationRepository->getActiveOrderedForTimetable($timetable),
            'staffs' => $this->staffRepository->getActiveStaffList(),
            'patterns' => $this->patternRepository->getAllWithSteps(),
            'clearAction' => $equipment->clearPlansUrl($timetable),
            'storePlanUrl' => route('timetables.logistics.plans.store', $timetable),
            'applyTemplateUrl' => $equipment->applyTemplateUrl($timetable),
            'backLogisticsUrl' => route('timetables.logistics.index', $timetable),
        ];
    }

    /**
     * 資材別フロー一覧用のデータ取得
     *
     * @return Collection<int, Equipment>
     */
    public function getEquipmentsFlowData(?string $type = null, ?int $laneId = null, ?Timetable $timetable = null): Collection
    {
        return $this->planRepository->getEquipmentsFlowData($type, $laneId, $timetable);
    }

    /**
     * フェーズ別チェックリスト用のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getPhaseChecklistData(?string $dayPhase = null, ?int $laneId = null, ?string $type = null, ?Timetable $timetable = null): Collection
    {
        return $this->planRepository->getPhaseChecklistData($dayPhase, $laneId, $type, $timetable);
    }

    /**
     * 拠点別 保管状況 & 貸切スケジュール一覧用のデータ取得
     *
     * @return array<int, array{location: Location, plans: Collection<int, EquipmentLogisticsPlan>, nightPlans: Collection<int, EquipmentLogisticsPlan>, collectionPlans: Collection<int, EquipmentLogisticsPlan>}>
     */
    public function getLocationsStatusData(?Timetable $timetable = null): array
    {
        $locations = $this->locationRepository->getActiveWithTypeAndSchedules($timetable);
        $locationIds = $locations->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $allPlans = $this->planRepository->getByToLocationIdsWithEquipment($locationIds, $timetable)
            ->groupBy('to_location_id');

        $result = [];

        foreach ($locations as $loc) {
            /** @var Collection<int, EquipmentLogisticsPlan> $plansToLocation */
            $plansToLocation = $allPlans->get($loc->id, new Collection);

            $nightPlans = $plansToLocation->where('action_type', 'night_storage');
            $collectionPlans = $plansToLocation->where('action_type', 'collection_staging');

            $result[] = [
                'location' => $loc,
                'plans' => $plansToLocation,
                'nightPlans' => $nightPlans,
                'collectionPlans' => $collectionPlans,
            ];
        }

        return $result;
    }

    /**
     * 会場内現地据置・テント内夜間保管（action_type = 'night_storage' かつ to_location_id = null）のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getOnsiteNightStoragePlans(?Timetable $timetable = null): Collection
    {
        return $this->planRepository->getOnsiteNightStoragePlans($timetable);
    }

    /**
     * イベント会社・立会スケジュール表用のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getEventCompanyScheduleData(?Timetable $timetable = null): Collection
    {
        return $this->planRepository->getEventCompanyScheduleData($timetable);
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
