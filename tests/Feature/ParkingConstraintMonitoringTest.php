<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Services\ParkingConstraintService;
use App\View\Components\Dashboard\ParkingMonitoringCard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ParkingConstraintMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createParkingLocation(array $attributes = [], ?string $startAt = '2026-10-10 13:00:00', ?string $endAt = '2026-10-12 20:30:00'): Location
    {
        $parkingType = LocationType::firstOrCreate(
            ['name' => 'parking'],
            ['label' => '駐車場', 'color' => 'amber', 'display_order' => 20, 'is_active' => true]
        );

        $location = Location::factory()->create(array_merge([
            'name' => '北川駐車場',
            'location_type_id' => $parkingType->id,
            'is_all_day' => false,
            'is_active' => true,
        ], $attributes));

        if ($startAt && $endAt) {
            LocationSchedule::create([
                'location_id' => $location->id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'availability_status' => 'available',
                'title' => '貸切枠',
            ]);
        }

        return $location;
    }

    private function renderMonitoringCard(): string
    {
        return Blade::renderComponent(new ParkingMonitoringCard(app(ParkingConstraintService::class)));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_parking_monitoring_card_renders_container(): void
    {
        $this->createParkingLocation(['name' => '北川駐車場']);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('拠点・駐車場 利用状況監視', $html);
        $this->assertStringContainsString('北川駐車場', $html);
    }

    public function test_parking_monitoring_evaluates_in_use_normally(): void
    {
        // 貸切期間中（2026-10-11 14:00）で適正に利用中
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 14, 0, 0, 'Asia/Tokyo'));

        $parking = $this->createParkingLocation(['name' => '北川駐車場']);

        $equipment = Equipment::factory()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '15:00',
            'action_type' => 'staging_move',
            'status' => 'completed',
        ]);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('北川駐車場', $html);
        $this->assertStringContainsString('利用中 (適正)', $html);
        $this->assertStringContainsString('保管機材:', $html);
        $this->assertStringContainsString('1品目', $html);
        $this->assertStringContainsString('✓ 違反なし', $html);
    }

    public function test_parking_monitoring_evaluates_standby_before_contract(): void
    {
        // 貸切開始前（2026-10-10 10:00）で違反なし
        Carbon::setTestNow(Carbon::create(2026, 10, 10, 10, 0, 0, 'Asia/Tokyo'));

        $this->createParkingLocation(['name' => '北川駐車場']);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('北川駐車場', $html);
        $this->assertStringContainsString('貸切前 (待機)', $html);
        $this->assertStringContainsString('利用開始まで 3時間', $html);
    }

    public function test_parking_monitoring_evaluates_warning_near_deadline(): void
    {
        // 貸切終了が 2026-10-12 20:30 で、現在時刻が 2026-10-12 19:30（残り1時間）
        Carbon::setTestNow(Carbon::create(2026, 10, 12, 19, 30, 0, 'Asia/Tokyo'));

        $parking = $this->createParkingLocation(['name' => '北川駐車場']);

        $equipment = Equipment::factory()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '15:00',
            'status' => 'completed',
        ]);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('北川駐車場', $html);
        $this->assertStringContainsString('本日20:30返却必須 (撤収未完了)', $html);
        $this->assertStringContainsString('返却期限まであと 1時間', $html);
    }

    public function test_parking_monitoring_evaluates_danger_overstay(): void
    {
        // 貸切終了が 2026-10-12 20:30 で、現在時刻が 2026-10-12 21:00（期限超過30分）
        Carbon::setTestNow(Carbon::create(2026, 10, 12, 21, 0, 0, 'Asia/Tokyo'));

        $parking = $this->createParkingLocation(['name' => '北川駐車場']);

        $equipment = Equipment::factory()->create(['name' => '大型テント']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '15:00',
            'status' => 'completed',
        ]);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('北川駐車場', $html);
        $this->assertStringContainsString('返却期限超過・未撤収', $html);
        $this->assertStringContainsString('返却期限超過 (+30分)', $html);
        $this->assertStringContainsString('🚨 違反あり (1件)', $html);
        $this->assertStringContainsString('貸切終了時刻（20:30）を超過していますが、資材が未撤収です。', $html);
    }

    public function test_parking_monitoring_evaluates_violation_for_early_ingress_plan(): void
    {
        // 貸切開始前（10:00）に、開始前（09:00）の搬入スケジュールが登録されている
        Carbon::setTestNow(Carbon::create(2026, 10, 10, 8, 0, 0, 'Asia/Tokyo'));

        $parking = $this->createParkingLocation(['name' => '北川駐車場']);

        $equipment = Equipment::factory()->create(['name' => '給水タンク']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $parking->id,
            'target_date' => '2026-10-10',
            'start_time' => '09:00',
            'status' => 'planned',
        ]);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('北川駐車場', $html);
        $this->assertStringContainsString('スケジュール制約違反あり', $html);
        $this->assertStringContainsString('🚨 違反あり (1件)', $html);
        $this->assertStringContainsString('給水タンク: 貸切開始（10/10 13:00）前の搬入計画', $html);
    }

    public function test_monitoring_includes_all_day_storage_locations(): void
    {
        $storageType = LocationType::firstOrCreate(
            ['name' => 'storage'],
            ['label' => '倉庫', 'color' => 'emerald', 'display_order' => 30, 'is_active' => true]
        );

        $storage = Location::factory()->allDay()->create([
            'name' => '番所地下倉庫',
            'location_type_id' => $storageType->id,
            'is_active' => true,
        ]);

        $equipment = Equipment::factory()->create(['name' => 'ござ']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'to_location_id' => $storage->id,
            'target_date' => '2026-10-10',
            'start_time' => '09:00',
            'status' => 'completed',
        ]);

        $html = $this->renderMonitoringCard();

        $this->assertStringContainsString('番所地下倉庫', $html);
        $this->assertStringContainsString('保管・稼働中', $html);
        $this->assertStringContainsString('1品目', $html);
    }
}
