<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LogisticsLocation;
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

    private LogisticsLocation $locationBansho;

    private LogisticsLocation $locationBanshoSpace;

    private LogisticsLocation $locationTaxOffice;

    private LogisticsLocation $locationKitagawa;

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

        $this->locationBansho = LogisticsLocation::factory()->allDay()->create(['name' => '番所地下倉庫']);
        $this->locationBanshoSpace = LogisticsLocation::factory()->allDay()->create(['name' => '番所空きスペース']);
        $this->locationTaxOffice = LogisticsLocation::factory()->create([
            'name' => '税務署前駐車場',
            'available_from' => '2026-10-10 12:00:00',
            'available_to' => '2026-10-12 21:00:00',
        ]);
        $this->locationKitagawa = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
        ]);

        $this->staff = Staff::factory()->create(['name' => '立会担当者']);
        $this->service = app(EquipmentLogisticsService::class);
    }

    /**
     * パターンA（自前機材: カラーコーン型）テンプレート生成の検証
     * - 立会不要
     * - 2日目夜に自前保管場所へ格納
     * - 1日目夜は指定された複数近隣拠点へ分散保管
     */
    public function test_pattern_a_template_generation(): void
    {
        $equipment = Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（50本）',
            'quantity' => 50,
            'storage_location' => '番所地下倉庫',
        ]);

        $count = $this->service->applyTemplate($equipment, 'pattern_a', [
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'night_location_2_id' => $this->locationKitagawa->id,
        ]);

        $this->assertEquals(6, $count);

        $plans = $equipment->plans()->get();

        // すべて立会不要であること
        $this->assertEquals(0, $plans->where('witness_required', true)->count());

        // すべて作業主体は委員会スタッフであること
        $this->assertEquals(6, $plans->where('operator_type', 'committee')->count());

        // 1日目夜に2拠点（税務署前Pと北川P）へ分散保管されていること
        $nightPlans = $plans->where('day_phase', 'day1_evening');
        $this->assertEquals(2, $nightPlans->count());
        $this->assertTrue($nightPlans->contains('to_location_id', $this->locationTaxOffice->id));
        $this->assertTrue($nightPlans->contains('to_location_id', $this->locationKitagawa->id));

        // 2日目夜に番所地下倉庫へ格納されること
        $lastPlan = $plans->where('day_phase', 'day2_evening')->first();
        $this->assertNotNull($lastPlan);
        $this->assertEquals('return_storage', $lastPlan->action_type);
        $this->assertEquals('番所地下倉庫', $lastPlan->to_location);
    }

    /**
     * パターンB（イベント会社受取・自前設置: 給水設備型）テンプレート生成の検証
     * - 前日受取と翌日回収のみ立会必須
     * - 2日目夜に番所空きスペースへ集約
     * - 翌日に業者回収
     */
    public function test_pattern_b_template_generation(): void
    {
        $equipment = Equipment::factory()->rental()->create([
            'name' => '足踏み式給水設備',
            'quantity' => 4,
            'event_company_name' => '東京イベントサプライ',
            'event_company_contact' => '03-1111-2222',
        ]);

        $count = $this->service->applyTemplate($equipment, 'pattern_b', [
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'night_location_2_id' => $this->locationKitagawa->id,
            'witness_staff_id' => $this->staff->id,
        ]);

        $this->assertEquals(8, $count);

        $plans = $equipment->plans()->get();

        // 前日受取と翌日回収の2件のみ立会必須であること
        $witnessPlans = $plans->where('witness_required', true);
        $this->assertEquals(2, $witnessPlans->count());
        $this->assertTrue($witnessPlans->contains('action_type', 'vendor_receive'));
        $this->assertTrue($witnessPlans->contains('action_type', 'vendor_pickup'));
        $this->assertEquals($this->staff->id, $witnessPlans->first()->witness_staff_id);

        // 2日目夜に番所空きスペースへ回収待ち集約されること
        $stagingReturnPlan = $plans->where('action_type', 'collection_staging')->first();
        $this->assertNotNull($stagingReturnPlan);
        $this->assertEquals('番所空きスペース', $stagingReturnPlan->to_location);

        // 翌日に番所空きスペースから回収されること
        $pickupPlan = $plans->where('day_phase', 'post_day')->first();
        $this->assertNotNull($pickupPlan);
        $this->assertEquals('番所空きスペース', $pickupPlan->from_location);
    }

    /**
     * パターンC（イベント会社設営・解体型: パイプテント等）テンプレート生成の検証
     * - イベント会社に一任（立会不要）
     * - 作業主体がイベント会社であること
     */
    public function test_pattern_c_template_generation(): void
    {
        $equipment = Equipment::factory()->rental()->create([
            'name' => 'パイプテント一式',
            'quantity' => 12,
        ]);

        $count = $this->service->applyTemplate($equipment, 'pattern_c');
        $this->assertEquals(2, $count);

        $plans = $equipment->plans()->get();

        // 立会不要であること
        $this->assertEquals(0, $plans->where('witness_required', true)->count());

        // 作業主体がイベント会社であること
        $this->assertEquals(2, $plans->where('operator_type', 'event_company')->count());
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

        $response = $this->patch(route('equipment-plans.update-status', $plan), [
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('equipment_logistics_plans', [
            'id' => $plan->id,
            'status' => 'completed',
        ]);
    }

    /**
     * 貸切期間外の移動予定に対する警告判定の検証
     */
    public function test_plan_location_availability_warning(): void
    {
        $equipment = Equipment::factory()->create();

        // 貸切開始（10/10 12:00）より前（10/10 09:00）に移動予定を設定
        $planEarly = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'target_date' => '2026-10-10',
            'start_time' => '09:00',
            'to_location_id' => $this->locationTaxOffice->id,
        ]);

        $warning = $planEarly->checkToLocationAvailability();
        $this->assertNotNull($warning);
        $this->assertEquals('warning', $warning['level']);
        $this->assertStringContainsString('貸切開始', $warning['message']);

        // 貸切期間内（10/10 14:00）は警告なし
        $planValid = EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'target_date' => '2026-10-10',
            'start_time' => '14:00',
            'to_location_id' => $this->locationTaxOffice->id,
        ]);
        $this->assertNull($planValid->checkToLocationAvailability());
    }
}
