<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
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
use App\Models\Schedule;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Staff;
use App\Models\StaffCheckIn;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableCloneTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);
    }

    /**
     * 複製元データを一式生成するヘルパー
     *
     * @return array<string, mixed>
     */
    private function createSourceTimetableDataset(): array
    {
        $sourceTimetable = Timetable::factory()->create([
            'name' => '2025年本祭 1日目',
            'target_date' => '2025-10-18',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'status' => 'completed',
            'description' => '前年運行の注意事項',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '17:00', 'label' => '歩行者天国', 'status' => 'closed'],
            ],
            'festival_periods' => [
                ['start_time' => '11:00', 'end_time' => '16:30', 'label' => '本祭開催', 'status' => 'active'],
            ],
            'created_by' => $this->operator->id,
        ]);

        $leadStaff = Staff::factory()->create(['name' => '山田 班長', 'is_active' => true]);
        $fixedStaff = Staff::factory()->create(['name' => '佐藤 固定', 'is_active' => true]);
        $assignStaff = Staff::factory()->create(['name' => '鈴木 配置', 'is_active' => true]);
        $absentStaff = Staff::factory()->create(['name' => '高橋 欠席', 'is_active' => true]);

        $masterLane = Lane::factory()->create([
            'name' => 'カラーコーン班',
            'icon' => '🔺',
            'color' => 'amber',
        ]);
        $lane = TimetableLane::factory()->create([
            'timetable_id' => $sourceTimetable->id,
            'lane_id' => $masterLane->id,
            'lead_staff_id' => $leadStaff->id,
            'display_order' => 10,
        ]);
        $lane->fixedStaff()->sync([$fixedStaff->id]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $sourceTimetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => 'コーン設置',
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'required_people_count' => 3,
            'status' => 'completed',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $assignStaff->id,
            'role_description' => 'リーダー補助',
            'attendance_status' => 'completed',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $absentStaff->id,
            'attendance_status' => 'absent',
        ]);

        $locationType = LocationType::factory()->create();
        $locationA = Location::factory()->create([
            'location_type_id' => $locationType->id,
            'name' => '番所地下倉庫',
            'display_order' => 10,
        ]);
        $locationB = Location::factory()->create([
            'location_type_id' => $locationType->id,
            'name' => 'メイン通り本部',
            'display_order' => 20,
        ]);

        LocationSchedule::create([
            'location_id' => $locationA->id,
            'start_at' => '2025-10-18 07:00:00',
            'end_at' => '2025-10-18 19:00:00',
            'availability_status' => 'available',
        ]);

        $equipment = Equipment::factory()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::create([
            'timetable_id' => $sourceTimetable->id,
            'equipment_id' => $equipment->id,
            'target_date' => '2025-10-17',
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '前日倉庫出し',
            'quantity' => 20,
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'from_location' => '番所地下倉庫',
            'to_location' => 'メイン通り本部',
            'from_location_id' => $locationA->id,
            'to_location_id' => $locationB->id,
            'operator_type' => 'committee',
            'witness_required' => false,
            'status' => 'completed',
        ]);

        $vendor = Vendor::create(['name' => '商店街カフェ']);
        $layoutMap = LayoutMap::create([
            'timetable_id' => $sourceTimetable->id,
            'name' => 'メイン通り配置図',
            'version_tag' => '確定版',
            'created_by' => $this->operator->id,
        ]);
        $layoutArea = LayoutArea::create([
            'location_id' => $locationB->id,
            'name' => 'メイン通りA列',
            'display_order' => 10,
            'is_active' => true,
        ]);
        $layoutItem = LayoutItem::create([
            'layout_map_id' => $layoutMap->id,
            'layout_area_id' => $layoutArea->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'A-01',
            'name' => 'カフェブース',
            'status' => LayoutItemStatus::Confirmed,
            'category' => BoothCategory::GozaGeneralGoods->value,
            'booth_type' => BoothType::Tent->value,
            'vendor_id' => $vendor->id,
            'display_order' => 1,
        ]);

        $siteBaseMap = SiteBaseMap::create([
            'name' => '平面図 1/400 A3',
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
        $sourceTimetable->update(['site_base_map_id' => $siteBaseMap->id]);
        SiteMapPlacement::create([
            'timetable_id' => $sourceTimetable->id,
            'site_base_map_id' => $siteBaseMap->id,
            'layout_item_id' => $layoutItem->id,
            'shape_type' => 'booth_tent',
            'display_text' => 'A-01 カフェ',
            'color_theme' => 'indigo',
            'pos_x' => 120.50,
            'pos_y' => 240.25,
            'width' => 30.00,
            'height' => 20.00,
            'rotation' => 0,
            'z_index' => 10,
        ]);

        // 当日運用ログ（複製対象外）
        StaffCheckIn::create([
            'timetable_id' => $sourceTimetable->id,
            'staff_id' => $leadStaff->id,
            'checked_in_at' => '2025-10-18 07:45:00',
        ]);
        LaneRequest::create([
            'timetable_id' => $sourceTimetable->id,
            'timetable_lane_id' => $lane->id,
            'timetable_task_id' => $task->id,
            'staff_id' => $leadStaff->id,
            'request_type' => 'staffing',
            'requested_count' => 2,
            'status' => 'pending',
            'reason' => '前年応援要請',
        ]);

        return compact('sourceTimetable', 'leadStaff', 'fixedStaff', 'assignStaff', 'absentStaff', 'vendor', 'locationA', 'locationB', 'layoutItem');
    }

    /**
     * 一覧画面および詳細画面に複製導線が表示され、新規登録画面で複製元プリセットとVite JSがレンダリングされること
     */
    public function test_clone_entry_links_and_preset_create_form_render_cleanly(): void
    {
        $dataset = $this->createSourceTimetableDataset();
        $source = $dataset['sourceTimetable'];

        // 一覧画面に複製ボタンおよびモーダルが表示される
        $indexResponse = $this->actingAs($this->operator)->get(route('timetables.index'));
        $indexResponse->assertOk()
            ->assertSee('btn-clone-timetable')
            ->assertSee('data-source-id="' . $source->id . '"', false)
            ->assertSee('modal-timetable-form')
            ->assertSee('id="modal_source_timetable_id"', false)
            ->assertSee('id="modal_copy_lanes"', false)
            ->assertSee('id="modal_copy_site_layouts"', false)
            ->assertDontSee('btn-reset-to-blank')
            ->assertDontSee('白紙作成に戻す');

        $this->assertTrue(
            str_contains($indexResponse->getContent(), 'timetables.js') || str_contains($indexResponse->getContent(), 'timetables-'),
            'timetables script asset is loaded'
        );

        // 詳細画面には「この運行から新規作成」が表示されないこと（不要化）
        $showResponse = $this->actingAs($this->operator)->get(route('timetables.show', $source));
        $showResponse->assertOk()
            ->assertDontSee('この運行から新規作成');

        // CSP準拠検証（インラインスクリプト・インラインイベント・インラインスタイルなし）
        $content = $indexResponse->getContent();
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
    }

    /**
     * 過去の運行情報をもとに全データをディープコピーし、IDリマップ・日付シフト・ステータス初期化が行われること
     */
    public function test_can_clone_timetable_with_all_related_data_remapped_and_reset(): void
    {
        $dataset = $this->createSourceTimetableDataset();
        $source = $dataset['sourceTimetable'];

        // 2025-10-18 から 2026-10-18（+365日）へ複製
        $response = $this->actingAs($this->operator)->post(route('timetables.store'), [
            'source_timetable_id' => $source->id,
            'target_date' => '2026-10-18',
            'status' => 'draft',
            'name' => '2026年本祭 1日目',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'description' => '2026年版の注意事項',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '17:00', 'label' => '歩行者天国', 'status' => 'closed'],
            ],
            'festival_periods' => [
                ['start_time' => '11:00', 'end_time' => '16:30', 'label' => '本祭開催', 'status' => 'active'],
            ],
            'copy_lanes' => '1',
            'copy_lane_staff' => '1',
            'copy_tasks' => '1',
            'copy_task_assignments' => '1',
            'copy_logistics_plans' => '1',
            'copy_site_layouts' => '1',
        ]);

        $newTimetable = Timetable::where('target_date', '2026-10-18')->first();
        $this->assertNotNull($newTimetable);
        $response->assertRedirect(route('timetables.show', $newTimetable));
        $response->assertSessionHas('success');

        // 1. Timetable本体とSchedule同期
        $this->assertSame('2026年本祭 1日目', $newTimetable->name);
        $this->assertSame('draft', $newTimetable->status);
        $this->assertNotNull(Schedule::where('timetable_id', $newTimetable->id)->first());

        // 2. TimetableLane & 固定スタッフ（マスター担当班は重複作成されないこと）
        $this->assertSame(1, Lane::count());
        $newLane = TimetableLane::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newLane);
        $this->assertSame('カラーコーン班', $newLane->name);
        $this->assertSame($dataset['leadStaff']->id, $newLane->lead_staff_id);
        $this->assertTrue($newLane->fixedStaff()->where('staff_id', $dataset['fixedStaff']->id)->exists());

        // 3. TimetableTask (timetable_lane_id リマップ & status=not_started) & Assignment (absent除外 & assigned初期化)
        $newTask = TimetableTask::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newTask);
        $this->assertSame($newLane->id, $newTask->timetable_lane_id);
        $this->assertSame('not_started', $newTask->status);
        $this->assertSame(1, $newTask->assignments()->count());
        $newAssignment = $newTask->assignments()->first();
        $this->assertSame($dataset['assignStaff']->id, $newAssignment->staff_id);
        $this->assertSame('assigned', $newAssignment->attendance_status);

        // 4. Location はマスター管理のため、運行情報複製時に重複作成されないこと
        $this->assertSame(2, Location::count());

        // 5. EquipmentLogisticsPlan (マスター拠点IDをそのまま保持・日付+365日シフト・plannedリセット)
        $newPlan = EquipmentLogisticsPlan::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newPlan);
        $this->assertSame('2026-10-17', $newPlan->target_date->format('Y-m-d'));
        $this->assertSame($dataset['locationA']->id, $newPlan->from_location_id);
        $this->assertSame($dataset['locationB']->id, $newPlan->to_location_id);
        $this->assertSame('planned', $newPlan->status);

        // 6. LayoutMap / LayoutItem (マスターLayoutArea参照・vendor_id維持・tentativeリセット) & SiteMapPlacement (layout_item_id リマップ)
        $newLayoutMap = LayoutMap::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newLayoutMap);
        $this->assertSame(1, LayoutArea::count());
        $newItem = LayoutItem::where('layout_map_id', $newLayoutMap->id)->first();
        $this->assertNotNull($newItem);
        $this->assertSame($dataset['layoutItem']->layout_area_id, $newItem->layout_area_id);
        $this->assertSame($dataset['vendor']->id, $newItem->vendor_id);
        $this->assertSame(LayoutItemStatus::Tentative, $newItem->status);

        // 白地図マスターは重複コピーされず、参照IDと配置オブジェクトのみ複製されること
        $this->assertSame(1, SiteBaseMap::count());
        $this->assertSame($source->site_base_map_id, $newTimetable->site_base_map_id);
        $newPlacement = SiteMapPlacement::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newPlacement);
        $this->assertSame($source->site_base_map_id, $newPlacement->site_base_map_id);
        $this->assertSame($newItem->id, $newPlacement->layout_item_id);

        // 7. 当日運用ログ (StaffCheckIn / LaneRequest) は複製されないこと
        $this->assertSame(0, StaffCheckIn::where('timetable_id', $newTimetable->id)->count());
        $this->assertSame(0, LaneRequest::where('timetable_id', $newTimetable->id)->count());
    }

    /**
     * 個別引き継ぎオプションをOFFにした場合は対象データが複製されないこと
     */
    public function test_clone_respects_disabled_options(): void
    {
        $dataset = $this->createSourceTimetableDataset();
        $source = $dataset['sourceTimetable'];

        $this->actingAs($this->operator)->post(route('timetables.store'), [
            'source_timetable_id' => $source->id,
            'target_date' => '2026-10-19',
            'status' => 'draft',
            'name' => '2026年 最小複製',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'copy_lanes' => '1',
            'copy_lane_staff' => '0',
            'copy_tasks' => '1',
            'copy_task_assignments' => '0',
            'copy_logistics_plans' => '0',
            'copy_site_layouts' => '0',
        ])->assertRedirect();

        $newTimetable = Timetable::where('target_date', '2026-10-19')->firstOrFail();

        // 担当班とタスクはコピーされるが、班長・固定スタッフ・タスクアサインはコピーされない（マスターは重複作成されない）
        $this->assertSame(1, Lane::count());
        $newLane = TimetableLane::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newLane);
        $this->assertNull($newLane->lead_staff_id);
        $this->assertSame(0, $newLane->fixedStaff()->count());

        $newTask = TimetableTask::where('timetable_id', $newTimetable->id)->first();
        $this->assertNotNull($newTask);
        $this->assertSame(0, $newTask->assignments()->count());

        // 搬出入・配置計画・白地図配置はコピーされず、既存の白地図マスターも維持されること
        $this->assertSame(0, EquipmentLogisticsPlan::where('timetable_id', $newTimetable->id)->count());
        $this->assertSame(0, LayoutMap::where('timetable_id', $newTimetable->id)->count());
        $this->assertSame(1, SiteBaseMap::count());
        $this->assertNull($newTimetable->site_base_map_id);
        $this->assertSame(0, SiteMapPlacement::where('timetable_id', $newTimetable->id)->count());
    }

    /**
     * 存在しない複製元IDや重複開催日ではバリデーションエラーとなり、権限のない閲覧ユーザーは403となること
     */
    public function test_clone_validation_and_authorization(): void
    {
        $dataset = $this->createSourceTimetableDataset();
        $source = $dataset['sourceTimetable'];

        // 閲覧専用ユーザーは403
        $this->actingAs($this->viewer)
            ->post(route('timetables.store'), [
                'source_timetable_id' => $source->id,
                'target_date' => '2026-10-20',
                'status' => 'draft',
                'name' => '権限なしテスト',
                'start_time' => '08:00',
                'end_time' => '18:00',
            ])
            ->assertForbidden();

        // 存在しない複製元ID・同一開催日でバリデーションエラー
        $this->actingAs($this->operator)
            ->post(route('timetables.store'), [
                'source_timetable_id' => 999999,
                'target_date' => '2025-10-18',
                'status' => 'draft',
                'name' => 'エラーテスト',
                'start_time' => '08:00',
                'end_time' => '18:00',
            ])
            ->assertSessionHasErrors(['source_timetable_id', 'target_date']);
    }
}
