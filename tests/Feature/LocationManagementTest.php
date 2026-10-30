<?php

namespace Tests\Feature;

use App\Enums\LocationCurrentStatus;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private LocationType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->type = LocationType::create([
            'name' => 'community_space',
            'label' => 'コミュニティスペース',
            'description' => '地域交流・待機スペース',
            'color' => 'indigo',
            'display_order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_can_view_location_index_screen(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '番所',
            'notes' => '鍵は暗証番号4920で開錠。',
        ]);

        $response = $this->actingAs($this->admin)->get(route('locations.index'));

        $response->assertOk()
            ->assertSee('拠点管理')
            ->assertSee('番所')
            ->assertSee('コミュニティスペース');

        // メモは詳細画面で確認可能
        $showResponse = $this->actingAs($this->admin)->get(route('locations.show', $location));
        $showResponse->assertOk()
            ->assertSee('鍵は暗証番号4920で開錠。');
    }

    public function test_can_create_new_location(): void
    {
        $response = $this->actingAs($this->admin)->post(route('locations.store'), [
            'name' => '税務署前駐車場',
            'location_type_id' => $this->type->id,
            'notes' => "【連絡先】090-0000-0000\n【注意事項】夜間施錠必須",
            'is_all_day' => false,
            'display_order' => 20,
            'is_active' => true,
        ]);

        $location = Location::where('name', '税務署前駐車場')->first();
        $this->assertNotNull($location);
        $this->assertEquals($this->type->id, $location->location_type_id);
        $this->assertStringContainsString('090-0000-0000', $location->notes);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $location->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $location->updated_at);

        $response->assertRedirect(route('locations.show', $location));
    }

    public function test_can_update_location(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '旧拠点名',
            'notes' => '旧メモ',
        ]);

        $response = $this->actingAs($this->admin)->put(route('locations.update', $location), [
            'name' => '新拠点名',
            'location_type_id' => $this->type->id,
            'notes' => '更新されたメモ内容',
            'is_all_day' => true,
            'display_order' => 5,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('locations.index'));
        $location->refresh();
        $this->assertEquals('新拠点名', $location->name);
        $this->assertEquals('更新されたメモ内容', $location->notes);
        $this->assertTrue($location->is_all_day);
    }

    public function test_can_delete_location(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '削除対象拠点',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('locations.destroy', $location));

        $response->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }

    public function test_can_manage_location_types(): void
    {
        // 1. タイプ追加
        $response = $this->actingAs($this->admin)->post(route('location-types.store'), [
            'name' => 'parking',
            'label' => '駐車場',
            'description' => '搬出入・中継用駐車場',
            'color' => 'amber',
            'display_order' => 20,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('location-types.index'));
        $parkingType = LocationType::where('name', 'parking')->first();
        $this->assertNotNull($parkingType);
        $this->assertEquals('駐車場', $parkingType->label);

        // 2. タイプ更新
        $this->actingAs($this->admin)->put(route('location-types.update', $parkingType), [
            'name' => 'parking',
            'label' => '専用駐車場',
            'description' => '更新された説明',
            'color' => 'amber',
            'display_order' => 25,
            'is_active' => true,
        ]);
        $parkingType->refresh();
        $this->assertEquals('専用駐車場', $parkingType->label);

        // 3. タイプ削除
        $this->actingAs($this->admin)->delete(route('location-types.destroy', $parkingType));
        $this->assertDatabaseMissing('location_types', ['id' => $parkingType->id]);
    }

    public function test_cannot_delete_location_type_with_associated_locations(): void
    {
        Location::factory()->create([
            'location_type_id' => $this->type->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('location-types.destroy', $this->type));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('location_types', ['id' => $this->type->id]);
    }

    public function test_can_add_multiple_time_slots_on_same_day(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'name' => '番所',
        ]);

        // 午前スロット（承認済）
        $response1 = $this->actingAs($this->admin)->post(route('locations.schedules.store', $location), [
            'start_at' => '2026-10-10 09:00:00',
            'end_at' => '2026-10-10 12:00:00',
            'application_status' => 'approved',
            'title' => '午前準備枠',
            'note' => '区役所申請番号: 101',
        ]);
        $response1->assertRedirect(route('locations.show', $location));

        // 夜間スロット（未申請）
        $response2 = $this->actingAs($this->admin)->post(route('locations.schedules.store', $location), [
            'start_at' => '2026-10-10 18:00:00',
            'end_at' => '2026-10-10 21:00:00',
            'application_status' => 'unapplied',
            'title' => '夜間撤収枠',
            'note' => '未申請のため至急申請要',
        ]);
        $response2->assertRedirect(route('locations.show', $location));

        $this->assertCount(2, $location->schedules);
        $schedules = $location->schedules()->orderBy('start_at')->get();
        $this->assertEquals('approved', $schedules[0]->application_status);
        $this->assertEquals('unapplied', $schedules[1]->application_status);
    }

    public function test_can_update_schedule_application_status_quickly(): void
    {
        $location = Location::factory()->create(['location_type_id' => $this->type->id]);
        $schedule = LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-10 09:00:00',
            'end_at' => '2026-10-10 12:00:00',
            'application_status' => 'unapplied',
            'title' => '午前枠',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('locations.schedules.update-status', [$location, $schedule]), [
            'application_status' => 'approved',
        ]);

        $response->assertRedirect(route('locations.show', $location));
        $schedule->refresh();
        $this->assertEquals('approved', $schedule->application_status);
    }

    public function test_evaluate_status_at_specific_times(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'is_all_day' => false,
            'is_active' => true,
        ]);

        // 午前枠: 09:00 - 12:00 (承認済)
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => Carbon::parse('2026-10-10 09:00:00'),
            'end_at' => Carbon::parse('2026-10-10 12:00:00'),
            'application_status' => 'approved',
            'title' => '午前枠',
        ]);

        // 夜間枠: 18:00 - 21:00 (未申請)
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => Carbon::parse('2026-10-10 18:00:00'),
            'end_at' => Carbon::parse('2026-10-10 21:00:00'),
            'application_status' => 'unapplied',
            'title' => '夜間枠',
        ]);

        // 1. 午前枠内（10:00）: 承認済みのため利用可能
        $evalMorning = $location->evaluateStatusAt(Carbon::parse('2026-10-10 10:00:00'));
        $this->assertEquals(LocationCurrentStatus::Available, $evalMorning->status);

        // 2. 枠外の時間（14:00）: 利用時間外
        $evalAfternoon = $location->evaluateStatusAt(Carbon::parse('2026-10-10 14:00:00'));
        $this->assertEquals(LocationCurrentStatus::UnavailableOutOfHours, $evalAfternoon->status);

        // 3. 夜間枠内（19:00）: 未申請のため利用不可！
        $evalNight = $location->evaluateStatusAt(Carbon::parse('2026-10-10 19:00:00'));
        $this->assertEquals(LocationCurrentStatus::UnavailableUnapplied, $evalNight->status);
        $this->assertStringContainsString('未申請のため利用できません', $evalNight->detail);

        // 4. 全期間終了後（10/11 00:00）: 利用期間終了
        $evalEnded = $location->evaluateStatusAt(Carbon::parse('2026-10-11 00:00:00'));
        $this->assertEquals(LocationCurrentStatus::Completed, $evalEnded->status);
    }

    public function test_all_day_location_is_always_available(): void
    {
        $location = Location::factory()->create([
            'location_type_id' => $this->type->id,
            'is_all_day' => true,
            'is_active' => true,
        ]);

        $eval = $location->evaluateStatusAt(Carbon::parse('2026-10-10 14:00:00'));
        $this->assertEquals(LocationCurrentStatus::Available, $eval->status);
        $this->assertStringContainsString('終日利用可能', $eval->detail);
    }
}
