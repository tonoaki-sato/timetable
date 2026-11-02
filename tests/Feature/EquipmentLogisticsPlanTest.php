<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use App\Services\EquipmentLogisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentLogisticsPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Location $locationBansho;

    private Location $locationBanshoSpace;

    private Location $locationTaxOffice;

    private Location $locationKitagawa;

    private Staff $staff;

    private EquipmentLogisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '宿場まつり 1日目']);
        Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '宿場まつり 2日目']);

        $this->locationBansho = Location::factory()->allDay()->create(['name' => '番所地下倉庫']);

        // 番所前スペースは当日昼間が模擬店使用のため、前日枠と翌日枠に分割
        $this->locationBanshoSpace = Location::factory()->create([
            'name' => '番所前スペース',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $this->locationBanshoSpace->id,
            'start_at' => '2026-10-09 09:00:00',
            'end_at' => '2026-10-10 18:00:00',
            'availability_status' => 'available',
            'title' => '前日搬入受取枠',
        ]);
        LocationSchedule::create([
            'location_id' => $this->locationBanshoSpace->id,
            'start_at' => '2026-10-12 18:00:00',
            'end_at' => '2026-10-14 17:00:00',
            'availability_status' => 'available',
            'title' => '撤収後回収待ち枠',
        ]);

        $this->locationTaxOffice = Location::factory()->create([
            'name' => '税務署前駐車場',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $this->locationTaxOffice->id,
            'start_at' => '2026-10-10 12:00:00',
            'end_at' => '2026-10-12 21:00:00',
            'availability_status' => 'available',
            'title' => '貸切枠',
        ]);

        $this->locationKitagawa = Location::factory()->create([
            'name' => '長島駐車場（旧北川駐車場）',
            'is_all_day' => false,
        ]);
        LocationSchedule::create([
            'location_id' => $this->locationKitagawa->id,
            'start_at' => '2026-10-10 13:00:00',
            'end_at' => '2026-10-12 20:30:00',
            'availability_status' => 'available',
            'title' => '貸切枠',
        ]);

        $this->staff = Staff::factory()->create(['name' => '立会担当者']);
        $this->service = app(EquipmentLogisticsService::class);
    }

    /**
     * カスタムパターン（自前備品型）からの計画ステップ生成の検証
     */
    public function test_custom_pattern_committee_generation(): void
    {
        $pattern = EquipmentLogisticsPattern::create([
            'name' => 'パターンA: 自前備品標準',
            'target_type' => 'committee',
            'description' => '自前備品の標準移動パターン',
        ]);

        $pattern->steps()->createMany([
            [
                'display_order' => 10,
                'day_phase' => 'pre_day',
                'action_type' => 'staging_move',
                'title' => '前日倉庫出し・中継拠点へ移動',
                'from_location_id' => $this->locationBansho->id,
                'to_location_id' => $this->locationTaxOffice->id,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
            [
                'display_order' => 20,
                'day_phase' => 'day1_morning',
                'action_type' => 'setup',
                'title' => '1日目朝 現場設置',
                'from_location_id' => $this->locationTaxOffice->id,
                'to_location_id' => $this->locationKitagawa->id,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
            [
                'display_order' => 30,
                'day_phase' => 'day2_evening',
                'action_type' => 'return_storage',
                'title' => '2日目夜 撤収・元保管場所へ格納',
                'from_location_id' => $this->locationKitagawa->id,
                'to_location_id' => $this->locationBansho->id,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
        ]);

        $equipment = Equipment::factory()->committee()->create([
            'name' => '立て看板',
            'quantity' => 50,
            'unit' => '個',
            'storage_location' => '番所地下倉庫',
        ]);

        $count = $this->service->applyPattern($equipment, $pattern, [
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'night_location_2_id' => $this->locationKitagawa->id,
        ]);

        $this->assertEquals(3, $count);

        $plans = $equipment->plans()->get();
        $this->assertEquals(0, $plans->where('witness_required', true)->count());

        // 資材数量（50個）が各ステップに設定されること
        $firstPlan = $plans->where('day_phase', 'pre_day')->first();
        $this->assertNotNull($firstPlan);
        $this->assertEquals($this->locationTaxOffice->id, $firstPlan->to_location_id);
        $this->assertEquals(50, $firstPlan->quantity);

        // 2日目夜に番所地下倉庫へ格納されること
        $lastPlan = $plans->where('day_phase', 'day2_evening')->first();
        $this->assertNotNull($lastPlan);
        $this->assertEquals($this->locationBansho->id, $lastPlan->to_location_id);
    }

    /**
     * 資材の既存ステップ構成を新しいカスタムパターンとして保存し、別資材へ再利用できること
     */
    public function test_save_equipment_plans_as_pattern_and_reuse(): void
    {
        $sourceEquipment = Equipment::factory()->rental()->create([
            'name' => '足踏み式給水設備',
            'quantity' => 4,
            'unit' => '台',
        ]);

        $sourceEquipment->plans()->create([
            'day_phase' => 'pre_day',
            'action_type' => 'vendor_receive',
            'title' => '前日 イベント会社納品・受取立会',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'from_location' => 'イベント会社配送',
            'to_location' => '番所前スペース',
            'to_location_id' => $this->locationBanshoSpace->id,
            'quantity' => 4,
            'operator_type' => 'committee',
            'witness_required' => true,
            'display_order' => 1,
        ]);

        $sourceEquipment->plans()->create([
            'day_phase' => 'post_day',
            'action_type' => 'vendor_pickup',
            'title' => '翌日 イベント会社回収・引渡立会',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'from_location' => '番所前スペース',
            'from_location_id' => $this->locationBanshoSpace->id,
            'to_location' => 'イベント会社回収',
            'quantity' => 4,
            'operator_type' => 'committee',
            'witness_required' => true,
            'display_order' => 2,
        ]);

        $savedPattern = $this->service->saveEquipmentPlansAsPattern($sourceEquipment, [
            'name' => 'レンタル標準受取・回収パターン',
            'target_type' => 'rental',
            'description' => '番所前スペースでの受取・回収',
        ]);

        $this->assertCount(2, $savedPattern->steps);
        $this->assertEquals($this->locationBanshoSpace->id, $savedPattern->steps[0]->to_location_id);
        $this->assertEquals($this->locationBanshoSpace->id, $savedPattern->steps[1]->from_location_id);

        // 別資材に適用
        $targetEquipment = Equipment::factory()->rental()->create([
            'name' => '折りたたみ長机',
            'quantity' => 20,
            'unit' => '脚',
        ]);

        $appliedCount = $this->service->applyPattern($targetEquipment, $savedPattern, [
            'witness_staff_id' => $this->staff->id,
        ]);

        $this->assertEquals(2, $appliedCount);
        $targetPlans = $targetEquipment->plans()->get();
        $this->assertEquals(20, $targetPlans[0]->quantity);
        $this->assertEquals($this->locationBanshoSpace->id, $targetPlans[0]->to_location_id);
        $this->assertEquals($this->staff->id, $targetPlans[0]->witness_staff_id);
    }

    /**
     * 数量がnullでquantity_noteのみ設定されている場合のフォーマット表示検証
     */
    public function test_plan_formatted_quantity_with_note_only(): void
    {
        $equipment = Equipment::factory()->rental()->create([
            'name' => 'カラーコーン一式',
            'quantity' => 60,
            'is_approximate_quantity' => true,
            'unit' => '本',
        ]);

        $plan = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'quantity' => null,
            'quantity_note' => '通行止め用分',
        ]);

        $this->assertSame('通行止め用分', $plan->formatted_quantity);
    }

    /**
     * ステータス更新（計画中 ➔ 進行中 ➔ 完了）の動作検証
     */
    public function test_plan_status_update(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->create();
        $plan = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'status' => 'planned',
        ]);

        $response = $this->patch(route('logistics.plans.update-status', $plan), [
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('equipment_logistics_plans', [
            'id' => $plan->id,
            'status' => 'completed',
        ]);
    }

    /**
     * 個別スロット単位での利用可能期間外の警告判定（開始前・終了後・中抜け時間帯・移動元）の検証
     */
    public function test_plan_location_availability_warning_including_middle_gap(): void
    {
        $equipment = Equipment::factory()->create();

        // 1. 貸切開始（10/10 12:00）より前（10/10 09:00）に移動予定を設定
        $planEarly = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'target_date' => '2026-10-10',
            'start_time' => '09:00',
            'to_location_id' => $this->locationTaxOffice->id,
        ]);

        $warning = $planEarly->checkToLocationAvailability();
        $this->assertNotNull($warning);
        $this->assertEquals('warning', $warning['level']);
        $this->assertStringContainsString('利用開始', $warning['message']);

        // 2. 貸切期間内（10/10 14:00）は警告なし
        $planValid = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'target_date' => '2026-10-10',
            'start_time' => '14:00',
            'to_location_id' => $this->locationTaxOffice->id,
        ]);
        $this->assertNull($planValid->checkToLocationAvailability());

        // 3. 番所前スペースの中抜け時間帯（10/11 12:00：模擬店使用中）に移動予定を設定すると警告が出ること
        $planMiddleGap = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'target_date' => '2026-10-11',
            'start_time' => '12:00',
            'to_location_id' => $this->locationBanshoSpace->id,
        ]);
        $gapWarning = $planMiddleGap->checkToLocationAvailability();
        $this->assertNotNull($gapWarning);
        $this->assertStringContainsString('利用可能時間外（またはイベントブース使用時間帯）', $gapWarning['message']);
    }
}
