<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LogisticsLocation;
use App\Models\Timetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EquipmentLogisticsService
{
    /**
     * まつりの代表日程（前日、1日目、2日目、翌日）を取得
     *
     * @return array{pre_day: ?string, day1: ?string, day2: ?string, post_day: ?string}
     */
    public function resolveFestivalDates(): array
    {
        $timetables = Timetable::orderBy('target_date', 'asc')->get();
        $day1 = $timetables->first()?->target_date?->format('Y-m-d') ?? '2026-10-11';
        $day2 = $timetables->skip(1)->first()?->target_date?->format('Y-m-d') ?? '2026-10-12';

        $preDay = Carbon::parse($day1)->subDay()->format('Y-m-d');
        $postDay = Carbon::parse($day2)->addDay()->format('Y-m-d');

        return [
            'pre_day' => $preDay,
            'day1' => $day1,
            'day2' => $day2,
            'post_day' => $postDay,
        ];
    }

    /**
     * 資材新規作成と同時にテンプレート適用
     */
    public function createEquipmentWithTemplate(array $data, ?User $user = null): Equipment
    {
        return DB::transaction(function () use ($data, $user) {
            $templateType = $data['template_type'] ?? 'none';
            $stagingLocationId = $data['staging_location_id'] ?? null;
            $nightLocation1Id = $data['night_location_1_id'] ?? null;
            $nightLocation2Id = $data['night_location_2_id'] ?? null;
            $witnessStaffId = $data['witness_staff_id'] ?? null;

            unset(
                $data['template_type'],
                $data['staging_location_id'],
                $data['night_location_1_id'],
                $data['night_location_2_id'],
                $data['witness_staff_id']
            );

            if ($user && empty($data['created_by'])) {
                $data['created_by'] = $user->id;
            }

            $equipment = Equipment::create($data);

            if ($templateType && $templateType !== 'none') {
                $this->applyTemplate($equipment, $templateType, [
                    'staging_location_id' => $stagingLocationId,
                    'night_location_1_id' => $nightLocation1Id,
                    'night_location_2_id' => $nightLocation2Id,
                    'witness_staff_id' => $witnessStaffId,
                ]);
            }

            return $equipment;
        });
    }

    /**
     * 3大パターンのテンプレートからステップを一括生成
     *
     * @param  array{staging_location_id?: ?int, night_location_1_id?: ?int, night_location_2_id?: ?int, witness_staff_id?: ?int}  $options
     */
    public function applyTemplate(Equipment $equipment, string $templateType, array $options = []): int
    {
        $dates = $this->resolveFestivalDates();

        // 拠点の取得
        $stagingLocation = ! empty($options['staging_location_id'])
            ? LogisticsLocation::find($options['staging_location_id'])
            : LogisticsLocation::where('name', 'like', '%税務署%')->first();

        $nightLocation1 = ! empty($options['night_location_1_id'])
            ? LogisticsLocation::find($options['night_location_1_id'])
            : $stagingLocation;

        $nightLocation2 = ! empty($options['night_location_2_id'])
            ? LogisticsLocation::find($options['night_location_2_id'])
            : LogisticsLocation::where('name', 'like', '%北川%')->first();

        $storageLocationName = $equipment->storage_location ?: '番所地下倉庫';
        $storageLocationModel = LogisticsLocation::where('name', $storageLocationName)->first()
            ?? LogisticsLocation::where('name', 'like', '%番所地下%')->first();

        $banshoSpace = LogisticsLocation::where('name', 'like', '%番所空き%')->first()
            ?? LogisticsLocation::where('name', 'like', '%番所%')->first();

        $witnessStaffId = $options['witness_staff_id'] ?? null;

        $plans = [];

        if ($templateType === 'pattern_a') {
            // =========================================================================
            // パターンA: 実行委員会管理機材（2日間設置サイクル・立会不要）
            // =========================================================================
            $qty = $equipment->quantity;
            $qty1 = (int) ceil($qty / 2);
            $qty2 = $qty - $qty1;

            $plans = [
                [
                    'target_date' => $dates['pre_day'],
                    'day_phase' => 'pre_day',
                    'action_type' => 'storage_out',
                    'title' => '前日倉庫出し・中継移動',
                    'quantity' => $qty,
                    'start_time' => '13:00',
                    'end_time' => '14:30',
                    'from_location' => $storageLocationName,
                    'to_location' => $stagingLocation?->name ?? '税務署前駐車場',
                    'from_location_id' => $storageLocationModel?->id,
                    'to_location_id' => $stagingLocation?->id,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '台車等で保管場所から中継拠点へ移動・集約。',
                    'display_order' => 10,
                ],
                [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_morning',
                    'action_type' => 'setup',
                    'title' => '1日目朝 会場各所へ設置',
                    'quantity' => $qty,
                    'start_time' => '07:30',
                    'end_time' => '09:00',
                    'from_location' => $stagingLocation?->name ?? '税務署前駐車場',
                    'to_location' => '会場設置',
                    'from_location_id' => $stagingLocation?->id,
                    'to_location_id' => null,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '中継場所から各担当箇所の設置位置へ運搬・配置。',
                    'display_order' => 20,
                ],
                [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_evening',
                    'action_type' => 'night_storage',
                    'title' => '1日目夜 分散夜間保管（'.$nightLocation1?->name.'）',
                    'quantity' => $nightLocation2 ? $qty1 : $qty,
                    'start_time' => '18:00',
                    'end_time' => '19:00',
                    'from_location' => '会場設置',
                    'to_location' => $nightLocation1?->name ?? '税務署前駐車場',
                    'from_location_id' => null,
                    'to_location_id' => $nightLocation1?->id,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '2日目設置位置に近い拠点へ移動・夜間保管。',
                    'display_order' => 30,
                ],
            ];

            if ($nightLocation2 && $nightLocation2->id !== $nightLocation1?->id) {
                $plans[] = [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_evening',
                    'action_type' => 'night_storage',
                    'title' => '1日目夜 分散夜間保管（'.$nightLocation2->name.'）',
                    'quantity' => $qty2,
                    'start_time' => '18:00',
                    'end_time' => '19:00',
                    'from_location' => '会場設置',
                    'to_location' => $nightLocation2->name,
                    'from_location_id' => null,
                    'to_location_id' => $nightLocation2->id,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '2日目設置位置に近い拠点へ移動・夜間保管。',
                    'display_order' => 40,
                ];
            }

            $plans[] = [
                'target_date' => $dates['day2'],
                'day_phase' => 'day2_morning',
                'action_type' => 'setup',
                'title' => '2日目朝 会場各所へ再設置',
                'quantity' => $qty,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'from_location' => '各夜間保管拠点',
                'to_location' => '会場設置',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '各夜間保管場所から各箇所へ再配置。',
                'display_order' => 50,
            ];

            $plans[] = [
                'target_date' => $dates['day2'],
                'day_phase' => 'day2_evening',
                'action_type' => 'return_storage',
                'title' => '2日目夜 撤収・'.$storageLocationName.'へ格納',
                'quantity' => $qty,
                'start_time' => '18:30',
                'end_time' => '20:00',
                'from_location' => '会場設置',
                'to_location' => $storageLocationName,
                'from_location_id' => null,
                'to_location_id' => $storageLocationModel?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '清掃・員数確認の上、元通り保管場所へ格納。',
                'display_order' => 60,
            ];
        } elseif ($templateType === 'pattern_b') {
            // =========================================================================
            // パターンB: イベント会社レンタル・自前設置（受取・回収のみ立会要）
            // =========================================================================
            $qty = $equipment->quantity;
            $qty1 = (int) ceil($qty / 2);
            $qty2 = $qty - $qty1;

            $plans = [
                [
                    'target_date' => $dates['pre_day'],
                    'day_phase' => 'pre_day',
                    'action_type' => 'vendor_receive',
                    'title' => '前日午前 業者受取・立会',
                    'quantity' => $qty,
                    'start_time' => '10:30',
                    'end_time' => '11:15',
                    'from_location' => 'イベント会社車両',
                    'to_location' => $banshoSpace?->name ?? '番所空きスペース',
                    'from_location_id' => null,
                    'to_location_id' => $banshoSpace?->id,
                    'operator_type' => 'committee',
                    'witness_required' => true,
                    'witness_staff_id' => $witnessStaffId,
                    'contact_info' => $equipment->event_company_contact,
                    'status' => 'planned',
                    'notes' => '外観・動作・付属品員数の確認の上、受取サイン。',
                    'display_order' => 10,
                ],
                [
                    'target_date' => $dates['pre_day'],
                    'day_phase' => 'pre_day',
                    'action_type' => 'staging_move',
                    'title' => '前日午後 中継拠点貸切後に移動',
                    'quantity' => $qty,
                    'start_time' => '13:00',
                    'end_time' => '13:45',
                    'from_location' => $banshoSpace?->name ?? '番所空きスペース',
                    'to_location' => $stagingLocation?->name ?? '税務署前駐車場',
                    'from_location_id' => $banshoSpace?->id,
                    'to_location_id' => $stagingLocation?->id,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => ($stagingLocation?->name ?? '中継拠点').'の貸切開始（使用可能状態）を確認後に移動。',
                    'display_order' => 20,
                ],
                [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_morning',
                    'action_type' => 'setup',
                    'title' => '1日目朝 会場各所へ設置',
                    'quantity' => $qty,
                    'start_time' => '08:00',
                    'end_time' => '09:30',
                    'from_location' => $stagingLocation?->name ?? '税務署前駐車場',
                    'to_location' => '会場設置',
                    'from_location_id' => $stagingLocation?->id,
                    'to_location_id' => null,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '会場内の各指定ポイントへ配置。',
                    'display_order' => 30,
                ],
                [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_evening',
                    'action_type' => 'night_storage',
                    'title' => '1日目夜 分散夜間保管（'.$nightLocation1?->name.'）',
                    'quantity' => $nightLocation2 ? $qty1 : $qty,
                    'start_time' => '17:30',
                    'end_time' => '18:15',
                    'from_location' => '会場設置',
                    'to_location' => $nightLocation1?->name ?? '税務署前駐車場',
                    'from_location_id' => null,
                    'to_location_id' => $nightLocation1?->id,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '設置位置の近い機材を移動して夜間保管。',
                    'display_order' => 40,
                ],
            ];

            if ($nightLocation2 && $nightLocation2->id !== $nightLocation1?->id) {
                $plans[] = [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_evening',
                    'action_type' => 'night_storage',
                    'title' => '1日目夜 分散夜間保管（'.$nightLocation2->name.'）',
                    'quantity' => $qty2,
                    'start_time' => '17:30',
                    'end_time' => '18:15',
                    'from_location' => '会場設置',
                    'to_location' => $nightLocation2->name,
                    'from_location_id' => null,
                    'to_location_id' => $nightLocation2->id,
                    'operator_type' => 'committee',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => null,
                    'status' => 'planned',
                    'notes' => '設置位置の近い機材を移動して夜間保管。',
                    'display_order' => 50,
                ];
            }

            $plans[] = [
                'target_date' => $dates['day2'],
                'day_phase' => 'day2_morning',
                'action_type' => 'setup',
                'title' => '2日目朝 会場各所へ再設置',
                'quantity' => $qty,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'from_location' => '各夜間保管拠点',
                'to_location' => '会場設置',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '各ポイントへ再配置。',
                'display_order' => 60,
            ];

            $plans[] = [
                'target_date' => $dates['day2'],
                'day_phase' => 'day2_evening',
                'action_type' => 'collection_staging',
                'title' => '2日目夜 番所空きスペースへ集約・保管',
                'quantity' => $qty,
                'start_time' => '18:00',
                'end_time' => '19:00',
                'from_location' => '会場設置',
                'to_location' => $banshoSpace?->name ?? '番所空きスペース',
                'from_location_id' => null,
                'to_location_id' => $banshoSpace?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '乾燥・清掃の上、番所空きスペースへ施錠保管。翌日の業者回収待ち。',
                'display_order' => 70,
            ];

            $plans[] = [
                'target_date' => $dates['post_day'],
                'day_phase' => 'post_day',
                'action_type' => 'vendor_pickup',
                'title' => '翌日午前 業者回収・引渡立会',
                'quantity' => $qty,
                'start_time' => '10:00',
                'end_time' => '10:45',
                'from_location' => $banshoSpace?->name ?? '番所空きスペース',
                'to_location' => 'イベント会社車両',
                'from_location_id' => $banshoSpace?->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => true,
                'witness_staff_id' => $witnessStaffId,
                'contact_info' => $equipment->event_company_contact,
                'status' => 'planned',
                'notes' => '業者トラック積み込み立ち会い、返却サイン。',
                'display_order' => 80,
            ];
        } elseif ($templateType === 'pattern_c') {
            // =========================================================================
            // パターンC: イベント会社設営・解体型（立会不要・幹事時刻把握用）
            // =========================================================================
            $qty = $equipment->quantity;

            $plans = [
                [
                    'target_date' => $dates['day1'],
                    'day_phase' => 'day1_morning',
                    'action_type' => 'vendor_setup',
                    'title' => 'イベント会社 搬入・設営',
                    'quantity' => $qty,
                    'start_time' => '06:30',
                    'end_time' => '08:30',
                    'from_location' => 'イベント会社車両',
                    'to_location' => '会場各ブース',
                    'from_location_id' => null,
                    'to_location_id' => null,
                    'operator_type' => 'event_company',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => $equipment->event_company_contact,
                    'status' => 'planned',
                    'notes' => '設営はイベント会社が実施。幹事は開始・完了状況を把握。',
                    'display_order' => 10,
                ],
                [
                    'target_date' => $dates['day2'],
                    'day_phase' => 'day2_evening',
                    'action_type' => 'vendor_pickup',
                    'title' => 'イベント会社 解体・搬出',
                    'quantity' => $qty,
                    'start_time' => '18:00',
                    'end_time' => '20:30',
                    'from_location' => '会場各ブース',
                    'to_location' => 'イベント会社車両',
                    'from_location_id' => null,
                    'to_location_id' => null,
                    'operator_type' => 'event_company',
                    'witness_required' => false,
                    'witness_staff_id' => null,
                    'contact_info' => $equipment->event_company_contact,
                    'status' => 'planned',
                    'notes' => '解体・搬出はイベント会社が実施。歩行者規制解除までにトラック完全退場。',
                    'display_order' => 20,
                ],
            ];
        }

        $createdCount = 0;
        foreach ($plans as $planData) {
            $equipment->plans()->create($planData);
            $createdCount++;
        }

        return $createdCount;
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
            'plans' => fn ($q) => $q->with(['fromLocationModel', 'toLocationModel', 'witnessStaff'])->orderBy('display_order')->orderBy('id'),
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
    public function getPhaseChecklistData(?string $dayPhase = null): Collection
    {
        $query = EquipmentLogisticsPlan::with([
            'equipment.lane',
            'fromLocationModel',
            'toLocationModel',
            'witnessStaff',
        ])
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc');

        if ($dayPhase) {
            $query->where('day_phase', $dayPhase);
        }

        return $query->get();
    }

    /**
     * 拠点別 保管状況 & 貸切スケジュール一覧用のデータ取得
     *
     * @return array<int, array{location: LogisticsLocation, nightPlans: Collection<int, EquipmentLogisticsPlan>, currentPlans: Collection<int, EquipmentLogisticsPlan>}>
     */
    public function getLocationsStatusData(): array
    {
        $locations = LogisticsLocation::active()->ordered()->get();
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
     * イベント会社スケジュール表用のデータ取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getEventCompanyScheduleData(): Collection
    {
        return EquipmentLogisticsPlan::with(['equipment.lane', 'witnessStaff', 'toLocationModel'])
            ->where(function ($q) {
                $q->where('operator_type', 'event_company')
                    ->orWhere('witness_required', true)
                    ->orWhereHas('equipment', fn ($sub) => $sub->where('type', 'rental'));
            })
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }
}
