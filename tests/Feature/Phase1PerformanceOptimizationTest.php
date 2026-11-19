<?php

namespace Tests\Feature;

use App\Actions\Staff\DownloadStaffCardsPdfAction;
use App\Actions\Timetable\CloneTimetableAction;
use App\DTOs\Timetable\CloneTimetableOptionsDTO;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\FeatureSetting;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\Schedule;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Services\FeatureSettingService;
use App\Services\GlobalDashboardService;
use App\Services\SiteMapPdfExporterService;
use App\Services\StaffAllocationService;
use App\Services\TimetableService;
use App\View\Components\TimetableContextBar;
use Database\Seeders\SiteMapIconDefinitionSeeder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase1PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_allocation_and_conflict_queries_do_not_trigger_n_plus_one(): void
    {
        $timetable = Timetable::factory()->create(['status' => 'active']);
        $targetLane = Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => '対象班']);
        $otherLane = Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => '他班']);

        $targetTask = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $targetLane->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'required_people_count' => 2,
        ]);

        // 他班の定員超過タスク（必要1名に対して複数名アサイン）
        $surplusTask = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $otherLane->id,
            'start_time' => '10:30:00',
            'end_time' => '11:30:00',
            'required_people_count' => 1,
            'is_at_least' => false,
        ]);

        $staffList = Staff::factory()->count(10)->create(['is_active' => true]);
        foreach ($staffList as $staff) {
            TaskStaffAssignment::factory()->create([
                'timetable_task_id' => $surplusTask->id,
                'staff_id' => $staff->id,
                'attendance_status' => 'assigned',
            ]);
        }

        // getConflictingTasksForStaff が laneRelation を事前ロードしており $task->lane 参照で N+1 が起きないこと
        $taskRepository = app(TimetableTaskRepositoryInterface::class);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $conflicts = $taskRepository->getConflictingTasksForStaff($targetTask, $staffList->first()->id);
        foreach ($conflicts as $c) {
            $this->assertSame('他班', $c->lane);
        }
        $conflictQueries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(4, count($conflictQueries));

        // getPrioritizedStaffForTask がスタッフ人数に比例しない定数クエリ数(O(1))で実行され、余剰判定が正確であること
        $allocationService = app(StaffAllocationService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $prioritized2 = $allocationService->getPrioritizedStaffForTask($targetTask, new EloquentCollection($staffList->take(2)->all()));
        $queriesFor2 = DB::getQueryLog();
        DB::disableQueryLog();

        $freshTask = TimetableTask::findOrFail($targetTask->id);
        $freshStaff10 = Staff::whereIn('id', $staffList->pluck('id'))->get();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $prioritized = $allocationService->getPrioritizedStaffForTask($freshTask, $freshStaff10);
        $allocationQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(2, $prioritized2);
        $this->assertCount(10, $prioritized);
        $this->assertTrue($prioritized->every(fn (Staff $s): bool => (bool) $s->is_surplus_fixed && $s->surplus_type === 'over_capacity'));
        $this->assertLessThanOrEqual(10, count($allocationQueries));
        $this->assertSame(count($queriesFor2), count($allocationQueries));
    }

    public function test_timetable_context_bar_uses_counts_without_lazy_loading_full_collections(): void
    {
        $timetable = Timetable::factory()->create(['status' => 'active']);
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id]);
        TimetableTask::factory()->create(['timetable_id' => $timetable->id, 'lane_id' => $lane->id]);
        $locationType = LocationType::create([
            'name' => 'test_type',
            'label' => 'テスト拠点種別',
            'color' => 'indigo',
            'display_order' => 1,
            'is_active' => true,
        ]);
        Location::factory()->create(['timetable_id' => $timetable->id, 'location_type_id' => $locationType->id]);
        $equipment = Equipment::factory()->create();
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $timetable->id,
            'equipment_id' => $equipment->id,
        ]);

        $freshTimetable = Timetable::findOrFail($timetable->id);
        $component = new TimetableContextBar(app(TimetableService::class), $freshTimetable, 'show');
        $html = view('components.timetable-context-bar', [
            'timetable' => $component->timetable,
            'timetablesList' => $component->timetablesList,
            'active' => $component->active,
            'targetRouteFor' => fn (Timetable $tt): string => $component->targetRouteFor($tt),
        ])->render();

        $this->assertStringContainsString('(1)', $html);
        $this->assertFalse($freshTimetable->relationLoaded('lanes'));
        $this->assertFalse($freshTimetable->relationLoaded('tasks'));
        $this->assertFalse($freshTimetable->relationLoaded('locations'));
        $this->assertFalse($freshTimetable->relationLoaded('logisticsPlans'));
    }

    public function test_dashboard_realtime_runs_lightweight_queries_and_matches_overview(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        Timetable::factory()->create(['target_date' => now()->addDays(5)->format('Y-m-d'), 'status' => 'active']);
        Schedule::factory()->count(2)->create(['status' => 'completed']);
        Schedule::factory()->count(2)->create(['status' => 'planned']);

        $overview = app(GlobalDashboardService::class)->getOverview();

        $this->actingAs($admin);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->getJson(route('dashboard.realtime'));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'days_until_main_event' => $overview->daysUntilMainEvent,
                'schedule_completion_rate' => $overview->scheduleCompletionRate,
            ]);
        $this->assertLessThanOrEqual(4, count($queries));
    }

    public function test_navigation_cache_caches_reads_and_auto_invalidates_on_mutations(): void
    {
        Cache::flush();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['name' => '第1運行日', 'status' => 'active']);
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id]);
        $staff = Staff::factory()->create();

        // 1. ナビゲーション・機能設定のキャッシュヒット＆自動無効化確認
        $timetableRepo = app(TimetableRepositoryInterface::class);
        $featureService = app(FeatureSettingService::class);

        $firstNav = $timetableRepo->getForNavigation();
        $this->assertCount(1, $firstNav);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $secondNav = $timetableRepo->getForNavigation();
        $featureService->getFeatureStatus(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);
        $featureService->getFeatureStatus(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION);
        $cachedQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $secondNav);
        $this->assertLessThanOrEqual(1, count($cachedQueries));

        // 運行日の追加で自動無効化されること
        Timetable::factory()->create(['name' => '第2運行日', 'status' => 'draft']);
        $afterAdd = $timetableRepo->getForNavigation();
        $this->assertCount(2, $afterAdd);

        // 機能設定の更新で自動無効化されること
        $featureService->updateFeature(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION, ['is_enabled' => false]);
        $this->assertFalse($featureService->isFeatureOpen(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION));

        // クエリビルダー経由の一括更新でも即座に無効化されること
        FeatureSetting::where('feature_key', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION)
            ->update(['is_enabled' => true]);
        $this->assertTrue($featureService->isFeatureOpen(FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION));

        // 2. ヘッダー通知ベル要請キャッシュ＆TimetableRepository::delete() での一括削除無効化
        LaneRequest::create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'requested_by_staff_id' => $staff->id,
            'request_type' => 'staffing',
            'requested_count' => 1,
            'reason' => '通知テスト',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertSee('通知テスト');

        $timetableRepo->delete($timetable);

        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee('通知テスト');

        // クエリビルダー経由の運行日程全削除でも nav.timetables が即座に無効化されること
        $this->assertCount(1, $timetableRepo->getForNavigation());
        Timetable::query()->delete();
        $this->assertCount(0, $timetableRepo->getForNavigation());
    }

    public function test_single_query_reorder_and_bulk_clone_work_accurately(): void
    {
        $eq1 = Equipment::factory()->create(['display_order' => 10]);
        $eq2 = Equipment::factory()->create(['display_order' => 20]);
        $eq3 = Equipment::factory()->create(['display_order' => 30]);

        $eqRepo = app(EquipmentRepositoryInterface::class);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $eqRepo->reorder([$eq3->id, $eq1->id, $eq2->id]);
        $reorderQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $updateCount = collect($reorderQueries)
            ->filter(fn (array $q): bool => str_starts_with(strtoupper(trim($q['query'])), 'UPDATE'))
            ->count();
        $this->assertSame(1, $updateCount);
        $this->assertSame(10, $eq3->fresh()->display_order);
        $this->assertSame(20, $eq1->fresh()->display_order);
        $this->assertSame(30, $eq2->fresh()->display_order);

        // LaneRepository::reorder も単一UPDATEで動作すること
        $timetable = Timetable::factory()->create(['target_date' => '2026-10-17']);
        $lane1 = Lane::factory()->create(['timetable_id' => $timetable->id, 'display_order' => 10]);
        $lane2 = Lane::factory()->create(['timetable_id' => $timetable->id, 'display_order' => 20]);
        $laneRepo = app(LaneRepositoryInterface::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $laneRepo->reorder($timetable, [$lane2->id, $lane1->id]);
        $laneQueries = DB::getQueryLog();
        DB::disableQueryLog();
        $laneUpdateCount = collect($laneQueries)
            ->filter(fn (array $q): bool => str_starts_with(strtoupper(trim($q['query'])), 'UPDATE'))
            ->count();
        $this->assertSame(1, $laneUpdateCount);
        $this->assertSame(10, $lane2->fresh()->display_order);

        // CloneTimetableAction のバルクインサートがJSONメタデータ含め正確に複製すること
        $user = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $staff = Staff::factory()->create();
        $task = TimetableTask::factory()->create(['timetable_id' => $timetable->id, 'lane_id' => $lane1->id]);
        TaskStaffAssignment::factory()->create(['timetable_task_id' => $task->id, 'staff_id' => $staff->id]);
        $locType = LocationType::create([
            'name' => 'clone_loc',
            'label' => '複製拠点',
            'color' => 'sky',
            'display_order' => 1,
            'is_active' => true,
        ]);
        $loc = Location::factory()->create(['timetable_id' => $timetable->id, 'location_type_id' => $locType->id]);
        LocationSchedule::create([
            'location_id' => $loc->id,
            'start_at' => '2026-10-17 09:00:00',
            'end_at' => '2026-10-17 17:00:00',
            'availability_status' => 'available',
        ]);
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $timetable->id,
            'equipment_id' => $eq1->id,
            'from_location_id' => $loc->id,
            'target_date' => '2026-10-17',
        ]);
        $layoutMap = LayoutMap::create(['timetable_id' => $timetable->id, 'name' => '配置図']);
        $area = LayoutArea::create([
            'layout_map_id' => $layoutMap->id,
            'location_id' => $loc->id,
            'zone_code' => 'Z1',
            'name' => 'エリア1',
            'display_order' => 1,
        ]);
        $layoutItem = LayoutItem::create([
            'layout_map_id' => $layoutMap->id,
            'layout_area_id' => $area->id,
            'item_type' => 'booth',
            'code' => 'A01',
            'status' => 'confirmed',
        ]);
        $baseMap = SiteBaseMap::create([
            'timetable_id' => $timetable->id,
            'name' => '白地図',
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
        SiteMapPlacement::create([
            'site_base_map_id' => $baseMap->id,
            'layout_item_id' => $layoutItem->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'meta_data' => ['note' => '角地', 'power' => true],
        ]);

        $result = app(CloneTimetableAction::class)->execute(
            [
                'target_date' => '2026-10-18',
                'name' => '2日目 本祭',
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'status' => 'draft',
            ],
            new CloneTimetableOptionsDTO(
                sourceTimetableId: $timetable->id,
                copyLanes: true,
                copyLaneStaff: true,
                copyTasks: true,
                copyTaskAssignments: true,
                copyLocations: true,
                copyLogisticsPlans: true,
                copySiteLayouts: true,
            ),
            $user
        );

        $clonedPlacement = SiteMapPlacement::whereHas('siteBaseMap', fn ($q) => $q->where('timetable_id', $result['timetable']->id))->firstOrFail();
        $this->assertSame(['note' => '角地', 'power' => true], $clonedPlacement->meta_data);
        $this->assertSame(1, $result['counts']['assignments']);
        $this->assertSame(1, $result['counts']['logistics_plans']);
        $this->assertSame(1, $result['counts']['placements']);
    }

    public function test_staff_cards_and_site_map_pdf_exporters_work_with_optimizations(): void
    {
        $staff = Staff::factory()->create(['is_active' => true, 'name' => '最適化 太郎']);
        $cardsAction = app(DownloadStaffCardsPdfAction::class);

        $cardsPngOnly = $cardsAction->buildCardsData(collect([$staff]), 100, includeSvg: false, includePng: true);
        $this->assertSame('', $cardsPngOnly->first()['qr_svg']);
        $this->assertStringStartsWith('data:image/png;base64,', $cardsPngOnly->first()['qr_data_uri']);

        $pdfResponse = $cardsAction->execute(new Request);
        $this->assertSame('application/pdf', $pdfResponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', (string) $pdfResponse->getContent());

        // 白地図ベクターPDFを2回連続生成し、静的パースキャッシュが有効でも正常にPDFが生成されること
        $this->seed(SiteMapIconDefinitionSeeder::class);
        $timetable = Timetable::factory()->create();
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
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $baseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => "A01\n焼鳥",
            'color_theme' => 'red',
            'pos_x' => 725.58,
            'pos_y' => 1017.60,
            'width' => 13.86,
            'height' => 13.86,
        ]);

        $exporter = app(SiteMapPdfExporterService::class);
        $pdf1 = $exporter->exportA3VectorPdf($baseMap, new EloquentCollection([$placement]));
        $pdf2 = $exporter->exportA3VectorPdf($baseMap, new EloquentCollection([$placement]));

        $this->assertStringStartsWith('%PDF-', $pdf1);
        $this->assertStringStartsWith('%PDF-', $pdf2);
        $this->assertGreaterThan(20000, strlen($pdf2));
    }
}
