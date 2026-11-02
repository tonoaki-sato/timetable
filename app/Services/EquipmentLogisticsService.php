<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EquipmentLogisticsService
{
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
     * @param  array{staging_location_id?: ?int, night_location_1_id?: ?int, night_location_2_id?: ?int, witness_staff_id?: ?int, override_existing?: ?bool}  $options
     */
    public function applyPattern(Equipment $equipment, EquipmentLogisticsPattern $pattern, array $options = []): int
    {
        $override = array_key_exists('override_existing', $options)
            ? (bool) $options['override_existing']
            : true;

        if ($override) {
            $equipment->plans()->delete();
        }

        $dates = $this->resolveFestivalDates();

        $stagingLocation = ! empty($options['staging_location_id'])
            ? Location::find($options['staging_location_id'])
            : Location::where('name', 'like', '%税務署%')->first();

        $nightLocation1 = ! empty($options['night_location_1_id'])
            ? Location::find($options['night_location_1_id'])
            : $stagingLocation;

        $nightLocation2 = ! empty($options['night_location_2_id'])
            ? Location::find($options['night_location_2_id'])
            : null;

        $hasSplitNight = $nightLocation2 !== null && $nightLocation2->id !== $nightLocation1?->id;

        $storageLocationName = $equipment->isPersonal()
            ? ($equipment->storage_detail ?: '所有者（個人持ち込み・持ち帰り）')
            : ($equipment->storage_location ?: '番所地下倉庫');

        $storageLocationModel = $equipment->isPersonal()
            ? null
            : (Location::where('name', $storageLocationName)->first()
                ?? Location::where('name', 'like', '%番所地下%')->first());

        $banshoSpace = Location::where('name', '番所前スペース')->first()
            ?? Location::where('name', 'like', '%番所%スペース%')->first()
            ?? Location::where('name', 'like', '%番所前%')->first();

        $witnessStaffId = $options['witness_staff_id'] ?? null;

        $qty = (int) $equipment->quantity;
        $qty1 = (int) ceil($qty / 2);
        $qty2 = max(0, $qty - $qty1);

        $pattern->loadMissing(['steps.fromLocationModel', 'steps.toLocationModel']);

        $createdCount = 0;
        $order = 10;

        foreach ($pattern->steps as $step) {
            // 夜間保管②のロールを持つステップで、分散先②が未指定の場合はスキップ
            if (($step->to_location_role === 'night_parking_2' || $step->from_location_role === 'night_parking_2') && ! $hasSplitNight) {
                continue;
            }

            [$fromLocId, $fromLocText] = $this->resolveLocationRole(
                $step->from_location_role,
                $step->from_location_id,
                $step->from_location,
                $storageLocationModel,
                $storageLocationName,
                $banshoSpace,
                $stagingLocation,
                $nightLocation1,
                $nightLocation2
            );

            [$toLocId, $toLocText] = $this->resolveLocationRole(
                $step->to_location_role,
                $step->to_location_id,
                $step->to_location,
                $storageLocationModel,
                $storageLocationName,
                $banshoSpace,
                $stagingLocation,
                $nightLocation1,
                $nightLocation2
            );

            $stepQuantity = match ($step->quantity_rule) {
                'all' => $qty,
                'half_1' => $hasSplitNight ? $qty1 : $qty,
                'half_2' => $hasSplitNight ? $qty2 : null,
                'custom' => null,
                default => $qty,
            };

            $targetDate = match ($step->day_phase) {
                'other' => $dates['other'],
                'pre_day' => $dates['pre_day'],
                'day1_morning', 'day1_evening' => $dates['day1'],
                'day2_morning', 'day2_evening' => $dates['day2'],
                'post_day' => $dates['post_day'],
                default => $dates['day1'],
            };

            $title = str_replace(
                ['{from_location}', '{to_location}', '{storage_location}'],
                [$fromLocText ?? '', $toLocText ?? '', $storageLocationName],
                $step->title
            );

            $equipment->plans()->create([
                'target_date' => $targetDate,
                'day_phase' => $step->day_phase,
                'action_type' => $step->action_type,
                'title' => $title,
                'quantity' => $stepQuantity,
                'quantity_note' => $step->quantity_note,
                'start_time' => $step->start_time,
                'end_time' => $step->end_time,
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
            $nightStorageIndex = 0;
            $order = 10;

            foreach ($plans as $plan) {
                if ($plan->action_type === 'night_storage' && $plan->to_location_id) {
                    $nightStorageIndex++;
                }

                [$fromRole, $fromId, $fromText] = $this->inferRoleFromPlanLocation(
                    $plan->from_location_id,
                    $plan->from_location,
                    $equipment,
                    $plan->action_type,
                    'from',
                    $nightStorageIndex
                );

                [$toRole, $toId, $toText] = $this->inferRoleFromPlanLocation(
                    $plan->to_location_id,
                    $plan->to_location,
                    $equipment,
                    $plan->action_type,
                    'to',
                    $nightStorageIndex
                );

                $quantityRule = 'all';
                if ($plan->quantity === null) {
                    $quantityRule = 'custom';
                } elseif ($plan->action_type === 'night_storage' && $plan->quantity < $equipment->quantity) {
                    $quantityRule = $nightStorageIndex <= 1 ? 'half_1' : 'half_2';
                }

                $pattern->steps()->create([
                    'day_phase' => $plan->day_phase,
                    'action_type' => $plan->action_type,
                    'title' => $plan->title,
                    'quantity_rule' => $quantityRule,
                    'quantity_note' => $plan->quantity_note,
                    'start_time' => $plan->start_time,
                    'end_time' => $plan->end_time,
                    'from_location_role' => $fromRole,
                    'to_location_role' => $toRole,
                    'from_location_id' => $fromId,
                    'to_location_id' => $toId,
                    'from_location' => $fromText,
                    'to_location' => $toText,
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
     * パターンステップのロール定義から拠点IDと表示テキストを解決
     *
     * @return array{0: ?int, 1: ?string}
     */
    protected function resolveLocationRole(
        string $role,
        ?int $fixedLocationId,
        ?string $fallbackText,
        ?Location $storageLocationModel,
        string $storageLocationName,
        ?Location $banshoSpace,
        ?Location $stagingLocation,
        ?Location $nightLocation1,
        ?Location $nightLocation2
    ): array {
        return match ($role) {
            'equipment_storage' => [$storageLocationModel?->id, $storageLocationModel?->name ?? $storageLocationName],
            'bansho_space' => [$banshoSpace?->id, $banshoSpace?->name ?? '番所前スペース'],
            'staging_parking' => [$stagingLocation?->id, $stagingLocation?->name ?? ($fallbackText ?: '貸切駐車場')],
            'night_parking_1' => [$nightLocation1?->id, $nightLocation1?->name ?? ($fallbackText ?: '夜間保管場所①')],
            'night_parking_2' => [$nightLocation2?->id, $nightLocation2?->name ?? ($fallbackText ?: '夜間保管場所②')],
            'fixed' => [
                $fixedLocationId,
                ($fixedLocationId ? Location::find($fixedLocationId)?->name : null) ?? ($fallbackText ?: '未定'),
            ],
            default => [null, $fallbackText],
        };
    }

    /**
     * 既存ステップの移動元・移動先からパターン用のロールを推論
     *
     * @return array{0: string, 1: ?int, 2: ?string}
     */
    protected function inferRoleFromPlanLocation(
        ?int $locationId,
        ?string $locationText,
        Equipment $equipment,
        string $actionType,
        string $direction,
        int $nightStorageIndex
    ): array {
        $text = (string) $locationText;

        if ($equipment->storage_location && $text === $equipment->storage_location) {
            return ['equipment_storage', null, $text];
        }

        if (str_contains($text, '番所前') || str_contains($text, '番所空き')) {
            return ['bansho_space', null, '番所前スペース'];
        }

        if ($direction === 'to' && $actionType === 'night_storage' && $locationId) {
            return [$nightStorageIndex <= 1 ? 'night_parking_1' : 'night_parking_2', null, $text];
        }

        if ($locationId) {
            return ['fixed', $locationId, $text];
        }

        return ['text', null, $locationText];
    }

    /**
     * 資材別フロー一覧用のデータ取得
     *
     * @return Collection<int, Equipment>
     */
    public function getEquipmentsFlowData(?string $type = null, ?int $laneId = null): Collection
    {
        $query = Equipment::with([
            'lane',
            'plans' => fn ($q) => $q->with([
                'equipment',
                'fromLocationModel.schedules',
                'toLocationModel.schedules',
                'witnessStaff',
            ])->orderBy('display_order')->orderBy('id'),
        ])->ordered();

        if ($type) {
            $query->where('type', $type);
        }

        if ($laneId) {
            $query->where('lane_id', $laneId);
        }

        return $query->get();
    }

    /**
     * フェーズ別チェックリスト用のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getPhaseChecklistData(?string $dayPhase = null, ?int $laneId = null, ?string $type = null): Collection
    {
        $query = EquipmentLogisticsPlan::with([
            'equipment.lane',
            'fromLocationModel.schedules',
            'toLocationModel.schedules',
            'witnessStaff',
        ])
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc');

        if ($dayPhase) {
            $query->where('day_phase', $dayPhase);
        }

        if ($laneId) {
            $query->whereHas('equipment', fn ($q) => $q->where('lane_id', $laneId));
        }

        if ($type) {
            $query->whereHas('equipment', fn ($q) => $q->where('type', $type));
        }

        return $query->get();
    }

    /**
     * 拠点別 保管状況 & 貸切スケジュール一覧用のデータ取得
     *
     * @return array<int, array{location: Location, plans: Collection<int, EquipmentLogisticsPlan>, nightPlans: Collection<int, EquipmentLogisticsPlan>, collectionPlans: Collection<int, EquipmentLogisticsPlan>}>
     */
    public function getLocationsStatusData(): array
    {
        $locations = Location::active()->ordered()->with(['type', 'schedules'])->get();
        $result = [];

        foreach ($locations as $loc) {
            $plansToLocation = EquipmentLogisticsPlan::with(['equipment.lane'])
                ->where('to_location_id', $loc->id)
                ->orderBy('target_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->get();

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
    public function getOnsiteNightStoragePlans(): Collection
    {
        return EquipmentLogisticsPlan::with(['equipment.lane', 'witnessStaff'])
            ->where('action_type', 'night_storage')
            ->whereNull('to_location_id')
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc')
            ->get();
    }

    /**
     * イベント会社・立会スケジュール表用のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getEventCompanyScheduleData(): Collection
    {
        return EquipmentLogisticsPlan::with(['equipment.lane', 'witnessStaff', 'fromLocationModel', 'toLocationModel'])
            ->where(function ($q) {
                $q->where('operator_type', 'event_company')
                    ->orWhere('witness_required', true)
                    ->orWhereHas('equipment', fn ($sub) => $sub->where('type', 'rental'));
            })
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc')
            ->get();
    }

    /**
     * カスタム搬出入パターン一覧の取得
     *
     * @return Collection<int, EquipmentLogisticsPattern>
     */
    public function getPatternsData(): Collection
    {
        return EquipmentLogisticsPattern::with(['steps.fromLocationModel', 'steps.toLocationModel', 'creator'])
            ->ordered()
            ->get();
    }
}
