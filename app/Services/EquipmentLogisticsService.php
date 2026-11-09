<?php

namespace App\Services;

use App\DTOs\ApplyEquipmentPatternDTO;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentLogisticsPatternRepositoryInterface;
use App\Repositories\Contracts\EquipmentLogisticsPlanRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EquipmentLogisticsService
{
    public function __construct(
        protected EquipmentLogisticsPlanRepositoryInterface $planRepository,
        protected EquipmentLogisticsPatternRepositoryInterface $patternRepository
    ) {}

    /**
     * まつりの代表日程（事前準備、前日、1日目、2日目、翌日）を取得
     *
     * @return array{other: ?string, pre_day: ?string, day1: ?string, day2: ?string, post_day: ?string}
     */
    public function resolveFestivalDates(): array
    {
        $timetables = Timetable::orderBy('target_date', 'asc')->get();
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

        $targetTimetable = $timetable ?? Timetable::orderBy('target_date', 'asc')->first();
        if (! $targetTimetable) {
            return 0;
        }

        if ($override) {
            $this->planRepository->clearForEquipment($equipment, $targetTimetable);
        }

        $dates = $this->resolveFestivalDates();
        $targetDate = $targetTimetable->target_date?->format('Y-m-d') ?? $dates['day1'];

        $stagingLocation = $dto->stagingLocationId
            ? Location::find($dto->stagingLocationId)
            : Location::where('timetable_id', $targetTimetable->id)->where('name', 'like', '%税務署%')->first();

        $nightLocation1 = $dto->nightLocation1Id
            ? Location::find($dto->nightLocation1Id)
            : $stagingLocation;

        $nightLocation2 = $dto->nightLocation2Id
            ? Location::find($dto->nightLocation2Id)
            : null;

        $storageLocationName = $equipment->isPersonal()
            ? ($equipment->storage_detail ?: '所有者（個人持ち込み・持ち帰り）')
            : ($equipment->storage_location ?: '番所地下倉庫');

        $witnessStaffId = $dto->witnessStaffId;
        $qty = (int) $equipment->quantity;

        $pattern->loadMissing(['steps.fromLocationModel', 'steps.toLocationModel']);

        $createdCount = 0;
        $order = 10;

        foreach ($pattern->steps as $step) {
            $fromLocId = $step->from_location_id;
            $fromLocText = $step->fromLocationModel?->name ?? $storageLocationName;
            $toLocId = $step->to_location_id;
            $toLocText = $step->toLocationModel?->name ?? '（未定）';

            // 拠点が別運行日マスタの場合、同一名拠点を対象運行日から解決
            if ($fromLocId && $step->fromLocationModel?->timetable_id !== $targetTimetable->id) {
                $matched = Location::where('timetable_id', $targetTimetable->id)->where('name', $step->fromLocationModel?->name)->first();
                if ($matched) {
                    $fromLocId = $matched->id;
                }
            }
            if ($toLocId && $step->toLocationModel?->timetable_id !== $targetTimetable->id) {
                $matched = Location::where('timetable_id', $targetTimetable->id)->where('name', $step->toLocationModel?->name)->first();
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

            $equipment->plans()->create([
                'timetable_id' => $targetTimetable->id,
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
     * 資材に登録済みの計画ステップ群を新しい搬出入パターンとして保存
     *
     * @param  array{name: string, target_type?: ?string, description?: ?string}  $data
     */
    public function saveEquipmentPlansAsPattern(Equipment $equipment, array $data, ?int $userId = null): EquipmentLogisticsPattern
    {
        return DB::transaction(function () use ($equipment, $data, $userId) {
            $pattern = EquipmentLogisticsPattern::create([
                'name' => $data['name'],
                'target_type' => $data['target_type'] ?: $equipment->type,
                'description' => $data['description'] ?? "{$equipment->name} の搬出入ステップから作成されたパターン",
                'display_order' => (int) EquipmentLogisticsPattern::max('display_order') + 10,
                'created_by' => $userId,
            ]);

            $plans = $equipment->plans()->orderBy('display_order', 'asc')->orderBy('id', 'asc')->get();
            $order = 10;

            foreach ($plans as $plan) {
                $pattern->steps()->create([
                    'day_phase' => $plan->day_phase,
                    'action_type' => $plan->action_type,
                    'title' => $plan->title,
                    'from_location_id' => $plan->from_location_id,
                    'to_location_id' => $plan->to_location_id,
                    'operator_type' => $plan->operator_type,
                    'witness_required' => $plan->witness_required,
                    'notes' => $plan->notes,
                    'display_order' => $order,
                ]);

                $order += 10;
            }

            return $pattern->load('steps');
        });
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
        $locationsQuery = Location::active()->ordered()->with(['type', 'schedules']);
        if ($timetable) {
            $locationsQuery->where('timetable_id', $timetable->id);
        }
        $locations = $locationsQuery->get();
        $locationIds = $locations->pluck('id')->all();

        $plansQuery = EquipmentLogisticsPlan::with(['equipment.lane'])
            ->whereIn('to_location_id', $locationIds)
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($timetable) {
            $plansQuery->where('timetable_id', $timetable->id);
        }

        $allPlans = $plansQuery->get()->groupBy('to_location_id');

        $result = [];

        foreach ($locations as $loc) {
            $plansToLocation = $allPlans->get($loc->id, collect());

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
        $query = EquipmentLogisticsPlan::with(['equipment.lane', 'witnessStaff'])
            ->where('action_type', 'night_storage')
            ->whereNull('to_location_id')
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc');

        if ($timetable) {
            $query->where('timetable_id', $timetable->id);
        }

        return $query->get();
    }

    /**
     * イベント会社・立会スケジュール表用のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getEventCompanyScheduleData(?Timetable $timetable = null): Collection
    {
        $query = EquipmentLogisticsPlan::with(['equipment.lane', 'witnessStaff', 'fromLocationModel', 'toLocationModel'])
            ->where(function ($q) {
                $q->where('operator_type', 'event_company')
                    ->orWhere('witness_required', true)
                    ->orWhereHas('equipment', fn ($sub) => $sub->where('type', 'rental'));
            })
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc');

        if ($timetable) {
            $query->where('timetable_id', $timetable->id);
        }

        return $query->get();
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
