<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
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
        $response = $this->get(route('locations.index'));
        $response->assertRedirect(route('login'));

        $responseCreate = $this->get(route('locations.create'));
        $responseCreate->assertRedirect(route('login'));
    }

    /**
     * 停止中ユーザー（is_active = false）はアクセスを拒否されること（403）
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveAdmin);
        $response = $this->get(route('locations.index'));
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

        $this->actingAs($admin);

        // 一覧
        $this->get(route('locations.index'))->assertOk();

        // 新規登録
        $storeRes = $this->post(route('locations.store'), [
            'name' => 'テスト拠点A',
            'location_type_id' => $type->id,
            'is_active' => true,
        ]);
        $storeRes->assertRedirect();
        $this->assertDatabaseHas('locations', ['name' => 'テスト拠点A']);

        $location = Location::where('name', 'テスト拠点A')->first();

        // 詳細・編集・更新
        $this->get(route('locations.show', $location))->assertOk();
        $this->get(route('locations.edit', $location))->assertOk();
        $this->put(route('locations.update', $location), [
            'name' => 'テスト拠点A更新',
            'location_type_id' => $type->id,
            'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('locations', ['name' => 'テスト拠点A更新']);

        // スケジュール登録
        $schedRes = $this->post(route('locations.schedules.store', $location), [
            'start_at' => now()->addHour()->toDateTimeString(),
            'end_at' => now()->addHours(2)->toDateTimeString(),
            'application_status' => 'approved',
        ]);
        $schedRes->assertRedirect();

        $schedule = LocationSchedule::first();

        // スケジュールステータス更新
        $this->patch(route('locations.schedules.update-status', [$location, $schedule]), [
            'application_status' => 'rejected',
        ])->assertRedirect();
        $this->assertDatabaseHas('location_schedules', ['id' => $schedule->id, 'application_status' => 'rejected']);

        // スケジュール削除
        $this->delete(route('locations.schedules.destroy', [$location, $schedule]))->assertRedirect();
        $this->assertDatabaseMissing('location_schedules', ['id' => $schedule->id]);

        // 拠点削除
        $this->delete(route('locations.destroy', $location))->assertRedirect();
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }
}
