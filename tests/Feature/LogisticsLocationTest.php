<?php

namespace Tests\Feature;

use App\Models\LogisticsLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsLocationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * 拠点一覧が表示できること
     */
    public function test_index_displays_locations(): void
    {
        $this->actingAs($this->operator);

        $loc = LogisticsLocation::factory()->create(['name' => '税務署前駐車場']);

        $response = $this->get(route('equipments.index', ['tab' => 'locations']));
        $response->assertStatus(200);
        $response->assertSee('税務署前駐車場');
    }

    /**
     * 終日利用可能拠点が作成できること
     */
    public function test_store_creates_all_day_location(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('logistics-locations.store'), [
            'name' => '新・番所地下倉庫',
            'type' => 'facility',
            'is_all_day' => true,
            'description' => '機材の常時保管場所',
            'display_order' => 10,
        ]);

        $response->assertRedirect(route('equipments.index', ['tab' => 'locations']));

        $this->assertDatabaseHas('logistics_locations', [
            'name' => '新・番所地下倉庫',
            'type' => 'facility',
            'is_all_day' => 1,
        ]);
    }

    /**
     * 貸切期間付き駐車場拠点が作成・判定できること
     */
    public function test_location_rental_period_availability_check(): void
    {
        $this->actingAs($this->admin);

        $location = LogisticsLocation::factory()->create([
            'name' => '北川駐車場',
            'type' => 'parking',
            'is_all_day' => false,
            'available_from' => '2026-10-10 12:00:00',
            'available_to' => '2026-10-12 21:00:00',
        ]);

        // 貸切開始前: 利用不可
        $before = Carbon::parse('2026-10-10 11:30:00');
        $this->assertFalse($location->isAvailableAt($before));

        // 貸切期間中: 利用可能
        $during = Carbon::parse('2026-10-11 14:00:00');
        $this->assertTrue($location->isAvailableAt($during));

        // 貸切終了後: 利用不可
        $after = Carbon::parse('2026-10-12 21:30:00');
        $this->assertFalse($location->isAvailableAt($after));
    }

    /**
     * 拠点の更新と削除ができること
     */
    public function test_update_and_destroy_location(): void
    {
        $this->actingAs($this->operator);

        $loc = LogisticsLocation::factory()->create(['name' => '旧・駐車場']);

        $updateResponse = $this->put(route('logistics-locations.update', $loc), [
            'name' => '更新後・税務署前駐車場',
            'type' => 'parking',
            'is_all_day' => true,
            'display_order' => 5,
        ]);

        $updateResponse->assertRedirect(route('equipments.index', ['tab' => 'locations']));
        $this->assertDatabaseHas('logistics_locations', [
            'id' => $loc->id,
            'name' => '更新後・税務署前駐車場',
        ]);

        $deleteResponse = $this->delete(route('logistics-locations.destroy', $loc));
        $deleteResponse->assertRedirect(route('equipments.index', ['tab' => 'locations']));
        $this->assertDatabaseMissing('logistics_locations', ['id' => $loc->id]);
    }
}
