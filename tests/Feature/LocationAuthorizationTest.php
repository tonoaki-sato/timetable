<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはログイン画面にリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $timetable = Timetable::factory()->create();

        $response = $this->get(route('timetables.locations.index', $timetable));
        $response->assertRedirect(route('login'));

        $responseCreate = $this->get(route('timetables.locations.create', $timetable));
        $responseCreate->assertRedirect(route('login'));
    }

    /**
     * 停止中ユーザー（is_active = false）はアクセスを拒否されること（403）
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $timetable = Timetable::factory()->create();

        $inactiveAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveAdmin);
        $response = $this->get(route('timetables.locations.index', $timetable));
        $response->assertStatus(403);
    }

    /**
     * admin は拠点、拠点スケジュール、拠点タイプのCRUD権限を持つこと
     */
    public function test_admin_is_authorized_for_location_crud(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $type = LocationType::create([
            'name' => 'parking_test',
            'label' => '駐車場テスト',
            'color' => 'indigo',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['name' => '1日目', 'target_date' => '2026-10-11']);

        $this->actingAs($admin);

        // 一覧
        $this->get(route('timetables.locations.index', $timetable))->assertOk();

        // 新規登録
        $storeRes = $this->post(route('timetables.locations.store', $timetable), [
            'name' => 'テスト拠点A',
            'location_type_id' => $type->id,
            'is_active' => true,
        ]);
        $storeRes->assertRedirect();
        $this->assertDatabaseHas('locations', ['name' => 'テスト拠点A', 'timetable_id' => $timetable->id]);

        $location = Location::where('name', 'テスト拠点A')->where('timetable_id', $timetable->id)->first();

        // 詳細・編集・更新
        $this->get(route('timetables.locations.show', [$timetable, $location]))->assertOk();
        $this->get(route('timetables.locations.edit', [$timetable, $location]))->assertOk();
        $this->put(route('timetables.locations.update', [$timetable, $location]), [
            'name' => 'テスト拠点A更新',
            'location_type_id' => $type->id,
            'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('locations', ['name' => 'テスト拠点A更新']);

        // スケジュール登録
        $schedRes = $this->post(route('timetables.locations.schedules.store', [$timetable, $location]), [
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at' => now()->addHours(2)->toDateTimeString(),
            'availability_status' => 'available',
        ]);
        $schedRes->assertRedirect();

        $schedule = LocationSchedule::first();

        // スケジュールステータス更新
        $this->patch(route('timetables.locations.schedules.update-status', [$timetable, $location, $schedule]), [
            'availability_status' => 'unavailable',
        ])->assertRedirect();
        $this->assertDatabaseHas('location_schedules', ['id' => $schedule->id, 'availability_status' => 'unavailable']);

        // スケジュール削除
        $this->delete(route('timetables.locations.schedules.destroy', [$timetable, $location, $schedule]))->assertRedirect();
        $this->assertDatabaseMissing('location_schedules', ['id' => $schedule->id]);

        // 拠点削除
        $this->delete(route('timetables.locations.destroy', [$timetable, $location]))->assertRedirect();
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }
}
