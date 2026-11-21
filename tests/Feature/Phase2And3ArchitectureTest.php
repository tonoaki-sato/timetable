<?php

namespace Tests\Feature;

use App\Actions\Auth\AuthenticateUserAction;
use App\Actions\SiteMap\ApplyStandardSiteMapPresetAction;
use App\Actions\SiteMap\DownloadSiteMapPdfAction;
use App\Actions\Timetable\ResolveActiveTimetableRedirectAction;
use App\DTOs\LayoutItemFilterDTO;
use App\DTOs\ScheduleFilterDTO;
use App\DTOs\VendorFilterDTO;
use App\Enums\AttendanceStatus;
use App\Enums\LaneRequestStatus;
use App\Enums\LaneRequestType;
use App\Enums\ScheduleCategory;
use App\Enums\ScheduleStatus;
use App\Enums\TimetableStatus;
use App\Enums\TimetableTaskStatus;
use App\Enums\UserRole;
use App\Http\Requests\PrintTimetableMatrixRequest;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\OrganizationUnit;
use App\Models\Schedule;
use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Staff;
use App\Models\StaffCheckIn;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use App\Presenters\EquipmentLogisticsPlanPresenter;
use App\Presenters\LanePresenter;
use App\Presenters\TimetablePresenter;
use App\Presenters\TimetableTaskPresenter;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Eloquent\SiteMapRepository;
use App\Services\ScheduleService;
use DomainException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2And3ArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase2_composite_indexes_exist_in_schema(): void
    {
        $expectedIndexes = [
            'users' => [['role', 'is_active']],
            'jobs' => [['queue', 'reserved_at', 'available_at']],
            'staff' => [['is_active', 'kana', 'name']],
            'timetables' => [['status', 'target_date', 'start_time']],
            'timetable_tasks' => [
                ['timetable_id', 'start_time', 'end_time'],
                ['timetable_lane_id', 'status', 'start_time'],
            ],
            'lane_requests' => [
                ['status', 'id'],
                ['timetable_lane_id', 'status'],
                ['timetable_task_id', 'status'],
            ],
            'locations' => [
                ['is_active', 'display_order'],
                ['display_order'],
            ],
            'location_schedules' => [['location_id', 'start_at']],
            'layout_areas' => [
                ['is_active', 'display_order', 'id'],
                ['display_order', 'id'],
                ['location_id'],
            ],
            'layout_items' => [
                ['layout_map_id', 'layout_area_id', 'display_order', 'code'],
                ['vendor_id', 'item_type', 'code'],
            ],
            'site_map_placements' => [
                ['timetable_id', 'site_base_map_id', 'z_index', 'id'],
                ['timetable_id', 'site_base_map_id', 'shape_type'],
                ['shape_type'],
            ],
        ];

        foreach ($expectedIndexes as $table => $columnSets) {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            $actualColumnSets = [];

            foreach ($indexes as $index) {
                $cols = DB::select("PRAGMA index_info('{$index->name}')");
                usort($cols, static fn (object $a, object $b): int => $a->seqno <=> $b->seqno);
                $actualColumnSets[] = array_map(static fn (object $col): string => (string) $col->name, $cols);
            }

            foreach ($columnSets as $expectedColumns) {
                $this->assertContains(
                    $expectedColumns,
                    $actualColumnSets,
                    "テーブル {$table} に複合インデックス (".implode(', ', $expectedColumns).') が不足しています。'
                );
            }
        }
    }

    public function test_phase2_models_relations_fillable_attributes_and_cascade_delete(): void
    {
        $this->assertNotEmpty((new SiteBaseMap)->getFillable());
        $this->assertNotEmpty((new SiteMapIconDefinition)->getFillable());
        $this->assertNotEmpty((new SiteMapPlacement)->getFillable());
        $this->assertInstanceOf(SiteMapRepository::class, app(SiteMapRepositoryInterface::class));

        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $user->id]);
        $staff = Staff::factory()->create(['is_active' => true]);
        $unit = OrganizationUnit::factory()->create(['name' => '実行本部']);
        $unit->staff()->attach($staff->id, ['role' => '本部員', 'display_order' => 10]);

        $lane = TimetableLane::factory()->create(['timetable_id' => $timetable->id, 'lead_staff_id' => $staff->id]);
        $task = TimetableTask::factory()->create(['timetable_id' => $timetable->id, 'timetable_lane_id' => $lane->id]);
        TaskStaffAssignment::factory()->create(['timetable_task_id' => $task->id, 'staff_id' => $staff->id]);
        $laneRequest = LaneRequest::create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'timetable_task_id' => $task->id,
            'requested_by_staff_id' => $staff->id,
            'request_type' => 'staffing',
            'requested_count' => 2,
            'status' => 'approved',
            'resolved_by_user_id' => $user->id,
            'resolved_at' => now(),
        ]);

        $locType = LocationType::create([
            'name' => 'cascade_test_type',
            'label' => 'カスケード検証',
            'color' => 'indigo',
            'display_order' => 1,
            'is_active' => true,
        ]);
        $location = Location::factory()->create([
            'location_type_id' => $locType->id,
            'created_by' => $user->id,
        ]);
        LocationSchedule::create([
            'location_id' => $location->id,
            'start_at' => '2026-10-18 09:00:00',
            'end_at' => '2026-10-18 17:00:00',
            'availability_status' => 'available',
        ]);
        $equipment = Equipment::factory()->create(['created_by' => $user->id]);
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $timetable->id,
            'equipment_id' => $equipment->id,
            'from_location_id' => $location->id,
            'to_location_id' => $location->id,
            'witness_staff_id' => $staff->id,
        ]);

        $layoutMap = LayoutMap::create(['timetable_id' => $timetable->id, 'name' => '配置図']);
        $area = LayoutArea::create([
            'location_id' => $location->id,
            'name' => 'テストエリア',
            'display_order' => 1,
            'is_active' => true,
        ]);
        $layoutItem = LayoutItem::create([
            'layout_map_id' => $layoutMap->id,
            'layout_area_id' => $area->id,
            'item_type' => 'booth',
            'code' => 'T01',
            'status' => 'confirmed',
        ]);
        $baseMap = SiteBaseMap::create([
            'name' => '白地図',
            'cad_version' => '260927',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.8,
            'height_meters' => 168.0,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_default' => true,
            'is_active' => true,
        ]);
        $timetable->update(['site_base_map_id' => $baseMap->id]);
        $placement = SiteMapPlacement::create([
            'timetable_id' => $timetable->id,
            'site_base_map_id' => $baseMap->id,
            'layout_item_id' => $layoutItem->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'T01',
        ]);
        StaffCheckIn::create([
            'timetable_id' => $timetable->id,
            'staff_id' => $staff->id,
            'checked_in_at' => now(),
        ]);
        Schedule::factory()->create([
            'timetable_id' => $timetable->id,
            'created_by' => $user->id,
            'staff_id' => $staff->id,
        ]);

        // 追加リレーションの検証
        $this->assertSame($user->id, $laneRequest->resolvedByUser?->id);
        $this->assertCount(1, $staff->organizationUnits);
        $this->assertCount(1, $staff->organizationUnitAssignments);
        $this->assertCount(1, $staff->laneRequests);
        $this->assertCount(1, $staff->witnessedLogisticsPlans);
        $this->assertCount(1, $timetable->layoutMaps);
        $this->assertSame($layoutMap->id, $timetable->layoutMap?->id);
        $this->assertCount(1, $timetable->staffCheckIns);
        $this->assertCount(1, $timetable->laneRequests);
        $this->assertCount(1, $layoutItem->placements);
        $this->assertSame($placement->id, $layoutItem->placements->first()?->id);
        $this->assertCount(1, $location->layoutAreas);
        $this->assertCount(1, $user->timetables);
        $this->assertCount(1, $user->schedules);
        $this->assertCount(1, $user->resolvedLaneRequests);

        // TimetableRepository::delete() 実行後、全子テーブルに孤立レコードが残らないこと
        app(TimetableRepositoryInterface::class)->delete($timetable);

        $this->assertDatabaseCount('timetables', 0);
        $this->assertDatabaseCount('timetable_lanes', 0);
        $this->assertDatabaseCount('lanes', 1);
        $this->assertDatabaseCount('timetable_tasks', 0);
        $this->assertDatabaseCount('task_staff_assignments', 0);
        $this->assertDatabaseCount('lane_requests', 0);
        $this->assertDatabaseCount('locations', 1);
        $this->assertDatabaseCount('location_schedules', 1);
        $this->assertDatabaseCount('equipment_logistics_plans', 0);
        $this->assertDatabaseCount('layout_maps', 0);
        $this->assertDatabaseCount('layout_areas', 1);
        $this->assertDatabaseCount('layout_items', 0);
        $this->assertDatabaseCount('site_base_maps', 1);
        $this->assertDatabaseCount('site_map_placements', 0);
        $this->assertDatabaseCount('staff_check_ins', 0);
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_phase3_enums_dtos_and_presenters_work_consistently(): void
    {
        $enumClasses = [
            TimetableStatus::class,
            ScheduleCategory::class,
            ScheduleStatus::class,
            TimetableTaskStatus::class,
            AttendanceStatus::class,
            LaneRequestType::class,
            LaneRequestStatus::class,
            UserRole::class,
        ];

        foreach ($enumClasses as $enumClass) {
            $options = $enumClass::options();
            $values = $enumClass::values();
            $this->assertNotEmpty($options);
            $this->assertSame(array_keys($options), $values);
            foreach ($enumClass::cases() as $case) {
                $this->assertNotSame('', $case->label());
            }
        }

        // Memoized Schedule::presenter() と各 Presenter の検証
        $schedule = new Schedule(['category' => 'committee', 'status' => 'planned']);
        $this->assertSame($schedule->presenter(), $schedule->presenter());
        $this->assertSame('🤝', $schedule->category_icon);
        $this->assertStringContainsString('indigo', $schedule->category_badge_class);
        $this->assertStringContainsString('blue', $schedule->status_badge_class);

        $t1 = Timetable::factory()->make(['target_date' => '2026-10-17', 'status' => 'active']);
        $t2 = Timetable::factory()->make(['target_date' => '2026-10-18', 'status' => 'draft']);
        $this->assertSame('2026年10月17日(土) 〜 10月18日(日) 開催', TimetablePresenter::formatEventDateRange(new EloquentCollection([$t1, $t2])));
        $this->assertStringContainsString('emerald', (new TimetablePresenter($t1))->statusBadgeClass());

        $plan = EquipmentLogisticsPlan::factory()->make([
            'day_phase' => 'pre_day',
            'status' => 'planned',
            'target_date' => '2026-10-16',
            'start_time' => '09:00',
        ]);
        $planPresenter = new EquipmentLogisticsPlanPresenter($plan);
        $this->assertStringContainsString('purple', $planPresenter->phaseBadgeClass());

        $lane = new Lane(['color' => 'sky']);
        $lanePresenter = new LanePresenter($lane);
        $this->assertSame('bg-sky-50', $lanePresenter->colorClasses()['bg']);

        $task = new TimetableTask(['status' => 'in_progress', 'required_people_count' => 1]);
        $taskPresenter = new TimetableTaskPresenter($task);
        $this->assertStringContainsString('indigo', $taskPresenter->statusBadgeClass());

        // Filter DTO の検証
        $vendorFilter = VendorFilterDTO::fromArray(['search' => '屋台', 'sort' => 'name', 'per_page' => '30']);
        $this->assertSame('屋台', $vendorFilter->search);
        $this->assertSame(30, $vendorFilter->perPage);
        $this->assertSame(['search' => '屋台', 'sort' => 'name', 'direction' => null], $vendorFilter->toFilterArray());

        $layoutFilter = LayoutItemFilterDTO::fromArray(['item_type' => 'booth', 'area_id' => '5', 'per_page' => '25']);
        $this->assertSame('booth', $layoutFilter->itemType);
        $this->assertSame(5, $layoutFilter->areaId);
        $this->assertSame(25, $layoutFilter->perPage);

        $scheduleFilter = ScheduleFilterDTO::fromArray(['category' => 'committee', 'staff_id' => '12', 'tab' => 'past']);
        $this->assertSame(12, $scheduleFilter->staffId);
        $this->assertSame('past', $scheduleFilter->tab);
        $this->assertSame(100, $scheduleFilter->perPage);
    }

    public function test_phase3_actions_form_requests_and_service_domain_rules(): void
    {
        // 1. AuthenticateUserAction が last_login_at を更新すること
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('secret-password'),
            'last_login_at' => null,
        ]);
        $loggedInUser = app(AuthenticateUserAction::class)->execute(
            email: $admin->email,
            password: 'secret-password',
            remember: false,
            ipAddress: '127.0.0.1',
        );
        $this->assertSame($admin->id, $loggedInUser->id);
        $this->assertNotNull($admin->fresh()->last_login_at);

        // 2. ResolveActiveTimetableRedirectAction のフォールバックと解決動作
        $redirectAction = app(ResolveActiveTimetableRedirectAction::class);
        $this->assertSame(route('timetables.index'), $redirectAction->execute(Request::create('/timetable-lanes'), 'timetables.lanes.index')->getTargetUrl());
        $activeTimetable = Timetable::factory()->create(['status' => 'active']);
        $this->assertSame(
            route('timetables.lanes.index', $activeTimetable),
            $redirectAction->execute(Request::create('/timetable-lanes'), 'timetables.lanes.index')->getTargetUrl()
        );

        // 3. ApplyStandardSiteMapPresetAction & DownloadSiteMapPdfAction
        $baseMap = SiteBaseMap::create([
            'name' => '平面図',
            'cad_version' => '260927',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.8,
            'height_meters' => 168.0,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_default' => true,
            'is_active' => true,
        ]);
        $activeTimetable->update(['site_base_map_id' => $baseMap->id]);
        $insertedCount = app(ApplyStandardSiteMapPresetAction::class)->execute($activeTimetable, $baseMap, [
            [
                'shape_type' => 'booth_standard',
                'display_text' => 'A01',
                'color_theme' => 'red',
                'pos_x' => 120.0,
                'pos_y' => 220.0,
                'width' => 13.86,
                'height' => 13.86,
            ],
        ]);
        $this->assertSame(1, $insertedCount);

        $pdfResponse = app(DownloadSiteMapPdfAction::class)->execute($activeTimetable);
        $this->assertSame('application/pdf', $pdfResponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', (string) $pdfResponse->getContent());

        // 4. ReorderOrganizationUnitsRequest の検証（存在しないIDで422、正常IDで200）
        $u1 = OrganizationUnit::factory()->create(['display_order' => 10]);
        $u2 = OrganizationUnit::factory()->create(['display_order' => 20]);
        $this->actingAs($admin);
        $this->postJson(route('organizations.reorder'), ['ids' => [$u1->id, 999999]])->assertStatus(422);
        $this->postJson(route('organizations.reorder'), ['ids' => [$u2->id, $u1->id]])->assertOk();
        $this->assertSame(10, $u2->fresh()->display_order);

        // 5. PrintTimetableMatrixRequest の lane_ids パースと DestroySiteMapIconRequest の認可・検証
        $matrixRequest = PrintTimetableMatrixRequest::create('/print-matrix', 'GET', ['lane_ids' => '12,0,-1,34']);
        $this->assertSame([12, 34], $matrixRequest->laneIds());
        $lane = TimetableLane::factory()->create(['timetable_id' => $activeTimetable->id]);
        $this->get(route('timetables.print-matrix', [
            'timetable' => $activeTimetable,
            'lane_ids' => (string) $lane->id,
            'preview' => 1,
        ]))->assertOk();

        $customIcon = SiteMapIconDefinition::create([
            'icon_key' => 'custom_arch_test_icon',
            'name' => 'アーキテストアイコン',
            'category' => 'facility',
            'render_style' => 'cad_water',
            'default_width' => 14.0,
            'default_height' => 14.0,
            'is_preset' => false,
        ]);
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
        $this->actingAs($viewer)
            ->deleteJson(route('timetables.site-maps.icons.destroy', [$activeTimetable, $customIcon]), ['force' => true])
            ->assertStatus(403);
        $this->actingAs($admin)
            ->deleteJson(route('timetables.site-maps.icons.destroy', [$activeTimetable, $customIcon]), ['force' => 'invalid'])
            ->assertStatus(422);
        $this->actingAs($admin)
            ->deleteJson(route('timetables.site-maps.icons.destroy', [$activeTimetable, $customIcon]), ['force' => true])
            ->assertOk();

        // 6. ScheduleService::deleteSchedule() の DomainException 検証
        $linkedSchedule = Schedule::factory()->create(['timetable_id' => $activeTimetable->id]);
        $this->expectException(DomainException::class);
        app(ScheduleService::class)->deleteSchedule($linkedSchedule);
    }
}
