<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LogisticsLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingConstraintMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_parking_monitoring_card_container(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="dashboard-card-parking-monitoring"', false);
        $response->assertSee('駐車場利用時間制約監視');
        $response->assertSee('北川駐車場');
    }

    public function test_parking_monitoring_evaluates_in_use_normally(): void
    {
        // 貸切期間中（2026-10-11 14:00）で適正に利用中
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 14, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
            'is_active' => true,
        ]);

        $equipment = Equipment::factory()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '15:00',
            'action_type' => 'staging_move',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('北川駐車場');
        $response->assertSee('利用中 (適正)');
        $response->assertSee('保管機材:');
        $response->assertSee('1品目');
        $response->assertSee('✓ 違反なし');
    }

    public function test_parking_monitoring_evaluates_standby_before_contract(): void
    {
        // 貸切開始前（2026-10-10 10:00）で違反なし
        Carbon::setTestNow(Carbon::create(2026, 10, 10, 10, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('北川駐車場');
        $response->assertSee('貸切前 (待機)');
        $response->assertSee('利用開始まで 3時間');
    }

    public function test_parking_monitoring_evaluates_warning_near_deadline(): void
    {
        // 貸切終了が 2026-10-12 20:30 で、現在時刻が 2026-10-12 19:30（残り1時間）
        Carbon::setTestNow(Carbon::create(2026, 10, 12, 19, 30, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
            'is_active' => true,
        ]);

        $equipment = Equipment::factory()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '15:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('北川駐車場');
        $response->assertSee('本日20:30返却必須 (撤収未完了)');
        $response->assertSee('返却期限まであと 1時間');
        $response->assertSee('⚠️ 要注意 (1件)');
    }

    public function test_parking_monitoring_evaluates_violation_when_overdue_with_equipment(): void
    {
        // 貸切終了が 2026-10-12 20:30 で、現在時刻が 2026-10-12 21:00（期限超過後30分）
        Carbon::setTestNow(Carbon::create(2026, 10, 12, 21, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
            'is_active' => true,
        ]);

        $equipment = Equipment::factory()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '15:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('北川駐車場');
        $response->assertSee('返却期限超過・未撤収');
        $response->assertSee('返却期限超過 (+30分)');
        $response->assertSee('🚨 違反あり (1件)');
        $response->assertSee('至急撤収・原状復帰を確認してください');
    }

    public function test_parking_monitoring_evaluates_violation_when_schedule_conflicts_with_contract(): void
    {
        // 貸切開始前（10:00）に、開始前（09:00）の搬入スケジュールが登録されている
        Carbon::setTestNow(Carbon::create(2026, 10, 10, 8, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 13:00:00',
            'available_to' => '2026-10-12 20:30:00',
            'is_active' => true,
        ]);

        $equipment = Equipment::factory()->create(['name' => '給水タンク']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '09:00',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('北川駐車場');
        $response->assertSee('スケジュール制約違反あり');
        $response->assertSee('🚨 違反あり (1件)');
        $response->assertSee('給水タンク: 貸切開始（10/10 13:00）前の搬入計画');
    }

    public function test_realtime_api_returns_parking_monitoring_html(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 14, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $parking = LogisticsLocation::factory()->create([
            'name' => '税務署前駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 12:00:00',
            'available_to' => '2026-10-12 21:00:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.realtime'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_time_ms',
                'updated_at_label',
                'cards' => [
                    'traffic_regulation',
                    'now_tasks',
                    'next_tasks',
                    'parking_monitoring',
                ],
            ]);

        $this->assertStringContainsString('税務署前駐車場', $response->json('cards.parking_monitoring'));
        $this->assertStringContainsString('利用中 (適正)', $response->json('cards.parking_monitoring'));
    }
}
