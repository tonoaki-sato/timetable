<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Models\EquipmentLogisticsPattern;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\LayoutArea;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\OrganizationUnit;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Phase0SecurityAndBugFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_unit_all_children_loads_staff_without_exception(): void
    {
        $root = OrganizationUnit::factory()->create(['name' => '実行委員会']);
        $child = OrganizationUnit::factory()->create(['parent_id' => $root->id, 'name' => '総務部']);
        $grandChild = OrganizationUnit::factory()->create(['parent_id' => $child->id, 'name' => '広報班']);
        $staff = Staff::factory()->create(['name' => 'テストスタッフ']);
        $grandChild->staff()->attach($staff->id, ['role' => '担当', 'display_order' => 10]);

        $loaded = OrganizationUnit::with('allChildren')->findOrFail($root->id);

        $this->assertCount(1, $loaded->allChildren);
        $this->assertCount(1, $loaded->allChildren->first()->allChildren);
        $loadedGrandChild = $loaded->allChildren->first()->allChildren->first();
        $this->assertTrue($loadedGrandChild->relationLoaded('staff'));
        $this->assertSame('テストスタッフ', $loadedGrandChild->staff->first()->name);
    }

    public function test_location_schedules_for_form_preserves_unavailable_status(): void
    {
        $timetable = Timetable::factory()->create();
        $location = Location::factory()->create(['timetable_id' => $timetable->id]);

        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-18 09:00:00',
            'end_at' => '2026-10-18 12:00:00',
            'availability_status' => 'unavailable',
            'title' => '午前調整中',
            'note' => '折衝中',
        ]);
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-18 13:00:00',
            'end_at' => '2026-10-18 17:00:00',
            'availability_status' => 'available',
            'title' => '午後確保済',
        ]);

        $location->load('schedules');
        $rows = $location->schedulesForForm();

        $this->assertCount(2, $rows);
        $this->assertSame('unavailable', $rows[0]['status']);
        $this->assertSame('available', $rows[1]['status']);
    }

    public function test_staff_hides_access_token_from_array_and_json_serialization(): void
    {
        $staff = Staff::factory()->create([
            'access_token' => 'secret_token_should_never_leak_12',
        ]);

        $array = $staff->toArray();
        $json = $staff->toJson();

        $this->assertArrayNotHasKey('access_token', $array);
        $this->assertStringNotContainsString('secret_token_should_never_leak_12', $json);
        $this->assertSame('secret_token_should_never_leak_12', $staff->access_token);
        $this->assertStringContainsString('secret_token_should_never_leak_12', $staff->my_shift_url);
    }

    public function test_equipment_pattern_step_routes_enforce_scoped_bindings(): void
    {
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $patternA = EquipmentLogisticsPattern::create([
            'name' => 'パターンA',
            'target_type' => 'committee',
            'display_order' => 10,
        ]);
        $patternB = EquipmentLogisticsPattern::create([
            'name' => 'パターンB',
            'target_type' => 'committee',
            'display_order' => 20,
        ]);

        $stepOfB = $patternB->steps()->create([
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => 'パターンBのステップ',
            'operator_type' => 'committee',
            'display_order' => 10,
        ]);

        $this->actingAs($operator);

        $this->putJson(route('equipment-patterns.steps.update', [$patternA, $stepOfB]), [
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '改ざん',
            'operator_type' => 'committee',
        ])->assertStatus(404);

        $this->deleteJson(route('equipment-patterns.steps.destroy', [$patternA, $stepOfB]))
            ->assertStatus(404);

        $this->assertDatabaseHas('equipment_logistics_pattern_steps', [
            'id' => $stepOfB->id,
            'title' => 'パターンBのステップ',
        ]);
    }

    public function test_profile_and_legacy_vendor_redirect_routes_work_without_closures(): void
    {
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $timetable = Timetable::factory()->create();

        $this->actingAs($operator)
            ->get(route('profile'))
            ->assertRedirect(route('users.show', $operator));

        $this->actingAs($operator)
            ->get(route('timetables.vendors.index', $timetable))
            ->assertRedirect(route('vendors.index'));
    }

    public function test_viewer_cannot_mutate_vendors_site_layouts_site_maps_or_lane_requests_while_operator_can(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $timetable = Timetable::factory()->create();
        $map = LayoutMap::create([
            'timetable_id' => $timetable->id,
            'name' => 'テスト配置図',
            'version_tag' => 'v1',
        ]);
        $area = LayoutArea::create([
            'layout_map_id' => $map->id,
            'zone_code' => 'ZONE_A',
            'name' => 'Aエリア',
            'display_order' => 1,
        ]);
        $baseMap = SiteBaseMap::create([
            'timetable_id' => $timetable->id,
            'name' => '平面図',
            'cad_version' => '260927',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.8,
            'height_meters' => 168.0,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_active' => true,
        ]);
        $vendor = Vendor::create(['name' => '既存出店者']);
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $baseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'pos_x' => 100,
            'pos_y' => 100,
            'width' => 13.86,
            'height' => 13.86,
        ]);
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id]);
        $staff = Staff::factory()->create();
        $laneRequest = LaneRequest::create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'requested_by_staff_id' => $staff->id,
            'request_type' => 'staffing',
            'requested_count' => 1,
            'status' => 'pending',
        ]);

        // 閲覧権限のみ持つ viewer は一覧表示は可能、書き込み系はすべて 403
        $this->actingAs($viewer);
        $this->get(route('vendors.index'))->assertOk();
        $this->get(route('vendors.export-csv'))->assertOk();
        $this->get(route('timetables.layouts.index', $timetable))->assertOk();
        $this->get(route('timetables.site-maps.editor', $timetable))->assertOk();

        $this->postJson(route('vendors.store'), ['name' => '不正登録'])->assertStatus(403);
        $this->putJson(route('vendors.update', $vendor), ['name' => '不正更新'])->assertStatus(403);
        $this->deleteJson(route('vendors.destroy', $vendor))->assertStatus(403);
        $this->post(route('vendors.import-csv'), [
            'csv_file' => UploadedFile::fake()->create('vendors.csv', 10, 'text/csv'),
        ])->assertStatus(403);

        $this->post(route('timetables.layouts.booths.store', $timetable), [
            'item_type' => 'booth',
            'code' => 'Z99',
            'layout_area_id' => $area->id,
            'name' => '不正ブース',
            'category' => BoothCategory::GozaCookedFood->value,
            'booth_type' => BoothType::Tent->value,
            'status' => 'confirmed',
        ])->assertStatus(403);

        $this->postJson(route('timetables.site-maps.placements.store', $timetable), [
            'shape_type' => 'booth_standard',
            'pos_x' => 10,
            'pos_y' => 10,
        ])->assertStatus(403);
        $this->putJson(route('timetables.site-maps.placements.update', [$timetable, $placement]), [
            'pos_x' => 20,
        ])->assertStatus(403);
        $this->deleteJson(route('timetables.site-maps.placements.destroy', [$timetable, $placement]))
            ->assertStatus(403);
        $this->postJson(route('timetables.site-maps.clear-placements', $timetable))
            ->assertStatus(403);
        $this->patchJson(route('lane-requests.update-status', $laneRequest), [
            'status' => 'approved',
        ])->assertStatus(403);

        // operator は登録・更新が可能
        $this->actingAs($operator);
        $this->postJson(route('vendors.store'), ['name' => '正規登録'])->assertOk();
        $this->patchJson(route('lane-requests.update-status', $laneRequest), [
            'status' => 'approved',
        ])->assertOk();
    }
}
