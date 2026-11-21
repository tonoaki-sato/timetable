<?php

namespace Tests\Feature;

use App\Models\Location;
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
        $response = $this->get(route('locations.index'));
        $response->assertRedirect(route('login'));

        $storeRes = $this->post(route('locations.store'), ['name' => '未認証']);
        $storeRes->assertRedirect(route('login'));
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
     * viewer ユーザーは閲覧のみ可能で、登録・更新・削除・並び替えは拒否されること（403）
     */
    public function test_viewer_cannot_mutate_locations(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);
        $type = LocationType::create([
            'name' => 'parking_viewer_test',
            'label' => '駐車場',
            'color' => 'indigo',
            'display_order' => 1,
            'is_active' => true,
        ]);
        $location = Location::factory()->create([
            'location_type_id' => $type->id,
        ]);

        $this->actingAs($viewer);
        $this->get(route('locations.index'))->assertOk();

        $this->post(route('locations.store'), [
            'name' => '権限なし拠点',
            'location_type_id' => $type->id,
        ])->assertForbidden();

        $this->put(route('locations.update', $location), [
            'name' => '権限なし更新',
            'location_type_id' => $type->id,
        ])->assertForbidden();

        $this->postJson(route('locations.reorder'), [
            'location_ids' => [$location->id],
        ])->assertForbidden();

        $this->delete(route('locations.destroy', $location))->assertForbidden();
    }

    /**
     * 旧ルート（timetables/{timetable}/locations）は拠点マスターへリダイレクトされること
     */
    public function test_legacy_route_redirects_to_locations_index(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $timetable = Timetable::factory()->create();

        $this->actingAs($admin);
        $response = $this->get(route('timetables.locations.index', $timetable));
        $response->assertRedirect(route('locations.index'));
    }

    /**
     * admin は拠点および利用枠一括同期のCRUD・並び替え権限を持つこと
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

        // 新規登録（利用枠込み）
        $storeRes = $this->post(route('locations.store'), [
            'name' => 'テスト拠点A',
            'location_type_id' => $type->id,
            'is_active' => true,
            'schedules' => [
                [
                    'start_at' => now()->addHour()->toDateTimeString(),
                    'end_at' => now()->addHours(2)->toDateTimeString(),
                    'availability_status' => 'available',
                    'title' => '初期枠',
                ],
            ],
        ]);
        $storeRes->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', ['name' => 'テスト拠点A']);
        $this->assertDatabaseHas('location_schedules', ['title' => '初期枠']);

        $location = Location::where('name', 'テスト拠点A')->first();
        $this->assertNotNull($location);

        // 更新（利用枠のステータス変更込み）
        $schedule = $location->schedules()->firstOrFail();
        $this->put(route('locations.update', $location), [
            'name' => 'テスト拠点A更新',
            'location_type_id' => $type->id,
            'is_active' => true,
            'schedules' => [
                [
                    'id' => $schedule->id,
                    'start_at' => now()->addHour()->toDateTimeString(),
                    'end_at' => now()->addHours(3)->toDateTimeString(),
                    'availability_status' => 'unavailable',
                    'title' => '更新枠',
                ],
            ],
        ])->assertRedirect(route('locations.index'));
        $this->assertDatabaseHas('locations', ['name' => 'テスト拠点A更新']);
        $this->assertDatabaseHas('location_schedules', [
            'id' => $schedule->id,
            'availability_status' => 'unavailable',
            'title' => '更新枠',
        ]);

        // 並び替え
        $this->postJson(route('locations.reorder'), [
            'location_ids' => [$location->id],
        ])->assertOk();

        // 拠点削除
        $this->delete(route('locations.destroy', $location))->assertRedirect(route('locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('location_schedules', ['id' => $schedule->id]);
    }
}
