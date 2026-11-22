<?php

namespace Tests\Unit\Presenters;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\EquipmentActionType;
use App\Enums\EquipmentDayPhase;
use App\Enums\EquipmentOperatorType;
use App\Enums\EquipmentPlanStatus;
use App\Enums\FacilityType;
use App\Enums\LaneRequestStatus;
use App\Enums\LaneRequestType;
use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
use App\Enums\TimetableStatus;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
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
use App\Models\SiteBaseMap;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use App\Models\Vendor;
use App\Presenters\LanePresenter;
use App\Presenters\TimetablePresenter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelPresentersTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_unit_and_staff_presenters(): void
    {
        $unit = OrganizationUnit::factory()->create(['name' => '誘導班']);
        $staffA = Staff::factory()->executive()->create(['name' => '佐藤 太郎', 'access_token' => 'token_alpha_12345']);
        $staffB = Staff::factory()->executiveBoard()->create(['name' => '鈴木 花子']);
        $staffC = Staff::factory()->create(['name' => '高橋 次郎']);
        $staffD = Staff::factory()->external()->create(['name' => '田中 三郎']);

        $unit->staff()->attach($staffA->id, ['role' => '班長', 'display_order' => 10]);
        $unit->staff()->attach($staffB->id, ['role' => '副班長', 'display_order' => 20]);
        $unit->staff()->attach($staffC->id, ['role' => '班員', 'display_order' => 30]);
        $unit->staff()->attach($staffD->id, ['role' => '外部協力', 'display_order' => 40]);

        $loadedUnit = OrganizationUnit::with('staff')->findOrFail($unit->id);
        $this->assertSame('佐藤 太郎, 鈴木 花子, 高橋 次郎, 田中 三郎', $loadedUnit->presenter()->staffNamesDisplay());
        $this->assertSame('佐藤 太郎（幹事）, 鈴木 花子（幹事会）, 高橋 次郎, 田中 三郎（外部）', $loadedUnit->presenter()->staffNamesWithAttributeDisplay());
        $this->assertSame('★佐藤 太郎, ☆鈴木 花子, 高橋 次郎, 田中 三郎', $loadedUnit->presenter()->staffNamesForPdfDisplay());
        $this->assertSame([
            ['mark' => '★', 'name' => '佐藤 太郎'],
            ['mark' => '☆', 'name' => '鈴木 花子'],
            ['mark' => '', 'name' => '高橋 次郎'],
            ['mark' => '', 'name' => '田中 三郎'],
        ], $loadedUnit->presenter()->pdfStaffItems());

        $assignmentLoadedUnit = OrganizationUnit::with('staffAssignments.staff')->findOrFail($unit->id);
        $this->assertSame('佐藤 太郎, 鈴木 花子, 高橋 次郎, 田中 三郎', $assignmentLoadedUnit->presenter()->staffNamesDisplay());
        $this->assertSame('★佐藤 太郎, ☆鈴木 花子, 高橋 次郎, 田中 三郎', $assignmentLoadedUnit->presenter()->staffNamesForPdfDisplay());
        $this->assertSame([
            ['mark' => '★', 'name' => '佐藤 太郎'],
            ['mark' => '☆', 'name' => '鈴木 花子'],
            ['mark' => '', 'name' => '高橋 次郎'],
            ['mark' => '', 'name' => '田中 三郎'],
        ], $assignmentLoadedUnit->presenter()->pdfStaffItems());

        $this->assertSame('幹事', $staffA->presenter()->attributeLabel());
        $this->assertTrue($staffA->isExecutiveGroup());
        $this->assertTrue($staffA->presenter()->isExecutiveGroup());
        $this->assertSame('★', $staffA->presenter()->pdfMark());
        $this->assertSame('★佐藤 太郎', $staffA->presenter()->pdfDisplayName());
        $this->assertSame('佐藤 太郎（幹事）', $staffA->presenter()->nameWithAttribute());

        $this->assertSame('幹事会', $staffB->presenter()->attributeLabel());
        $this->assertTrue($staffB->isExecutiveGroup());
        $this->assertSame('☆', $staffB->presenter()->pdfMark());
        $this->assertSame('☆鈴木 花子', $staffB->presenter()->pdfDisplayName());
        $this->assertSame('鈴木 花子（幹事会）', $staffB->presenter()->nameWithAttribute());

        $this->assertSame('一般', $staffC->presenter()->attributeLabel());
        $this->assertFalse($staffC->isExecutiveGroup());
        $this->assertSame('', $staffC->presenter()->pdfMark());
        $this->assertSame('高橋 次郎', $staffC->presenter()->pdfDisplayName());
        $this->assertSame('高橋 次郎', $staffC->presenter()->nameWithAttribute());

        $this->assertSame('外部', $staffD->presenter()->attributeLabel());
        $this->assertFalse($staffD->isExecutiveGroup());
        $this->assertSame('', $staffD->presenter()->pdfMark());
        $this->assertSame('田中 三郎', $staffD->presenter()->pdfDisplayName());
        $this->assertSame('田中 三郎（外部）', $staffD->presenter()->nameWithAttribute());

        $this->assertSame(
            route('staff.myshift', ['token' => 'token_alpha_12345']),
            $staffA->presenter()->myShiftUrl(),
        );
    }

    public function test_location_and_location_schedule_presenters(): void
    {
        $type = LocationType::factory()->create(['name' => 'warehouse', 'label' => '倉庫']);
        $location = Location::factory()->create(['location_type_id' => $type->id, 'name' => '番所倉庫']);
        $location->load('type');

        $this->assertSame('倉庫', $location->presenter()->typeLabel());

        $available = LocationSchedule::factory()->create([
            'location_id' => $location->id,
            'availability_status' => 'available',
        ]);
        $unavailable = LocationSchedule::factory()->create([
            'location_id' => $location->id,
            'availability_status' => 'unavailable',
        ]);

        $this->assertSame('利用可（確保済）', $available->presenter()->availabilityStatusLabel());
        $this->assertStringContainsString('emerald', $available->presenter()->availabilityStatusBadgeClass());
        $this->assertSame('未確保（調整中）', $unavailable->presenter()->availabilityStatusLabel());
        $this->assertStringContainsString('amber', $unavailable->presenter()->availabilityStatusBadgeClass());
    }

    public function test_equipment_and_logistics_presenters(): void
    {
        $location = Location::factory()->create(['name' => '番所地下倉庫']);
        $committeeEq = Equipment::factory()->committee()->create([
            'name' => 'テント',
            'quantity' => 5,
            'is_approximate_quantity' => false,
            'unit' => '張',
            'storage_location_id' => $location->id,
        ]);
        $committeeEq->load('storageLocation');

        $this->assertSame('5 張', $committeeEq->presenter()->formattedQuantity());
        $this->assertSame('番所地下倉庫', $committeeEq->presenter()->storageLocationName());
        $this->assertSame('番所地下倉庫', $committeeEq->presenter()->resolvedStorageLocationLabel());
        $eqPayload = $committeeEq->presenter()->toModalPayload();
        $this->assertSame(route('equipments.update', $committeeEq), $eqPayload['update_url']);
        $this->assertSame('5 張', $eqPayload['formatted_quantity']);

        $pattern = EquipmentLogisticsPattern::factory()->create(['name' => '標準パターン', 'target_type' => 'committee']);
        $step = $pattern->steps()->create([
            'display_order' => 10,
            'day_phase' => EquipmentDayPhase::PreDay->value,
            'action_type' => EquipmentActionType::StorageOut->value,
            'title' => '倉庫出し',
            'from_location_id' => $location->id,
            'to_location' => '現地本部',
            'operator_type' => EquipmentOperatorType::Committee->value,
        ]);
        $step->load('fromLocationModel');
        $pattern->load('steps.fromLocationModel');

        $this->assertSame('実行委員会管理（自前）', $pattern->presenter()->targetTypeLabel());
        $this->assertSame('番所地下倉庫', $step->presenter()->fromLocationDisplay());
        $this->assertSame('現地本部', $step->presenter()->toLocationDisplay());
        $this->assertSame(route('equipment-patterns.update', $pattern), $pattern->presenter()->toModalPayload()['update_url']);
        $this->assertSame(route('equipment-patterns.steps.update', [$pattern, $step]), $step->presenter()->toModalPayload($pattern)['update_url']);

        $timetable = Timetable::factory()->create(['target_date' => '2026-10-17']);
        $plan = EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $timetable->id,
            'equipment_id' => $committeeEq->id,
            'day_phase' => EquipmentDayPhase::PreDay->value,
            'action_type' => EquipmentActionType::StagingMove->value,
            'status' => EquipmentPlanStatus::InProgress->value,
            'operator_type' => EquipmentOperatorType::Committee->value,
            'target_date' => '2026-10-16',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'from_location_id' => $location->id,
            'to_location_id' => null,
            'to_location' => '北川交差点',
            'quantity' => 3,
            'quantity_note' => '本部用',
        ]);
        $plan->load(['equipment', 'fromLocationModel']);

        $this->assertSame('09:00〜10:30', $plan->presenter()->formattedTimeRange());
        $this->assertSame('3張（本部用）', $plan->presenter()->formattedQuantity());
        $this->assertSame('番所地下倉庫', $plan->presenter()->resolvedFromLocation());
        $this->assertSame('北川交差点', $plan->presenter()->resolvedToLocation());
        $this->assertStringContainsString('purple', $plan->presenter()->phaseBadgeClass());
        $this->assertStringContainsString('indigo', $plan->presenter()->ganttBadgeClass());
        $this->assertStringContainsString('bg-sky-50/25', $plan->presenter()->rowHighlightClass());
        $this->assertSame(
            route('timetables.logistics.plans.update', [$timetable, $plan]),
            $plan->presenter()->toModalPayload($timetable)['update_url'],
        );
    }

    public function test_site_layout_and_vendor_presenters(): void
    {
        $baseMap = SiteBaseMap::factory()->create([
            'name' => 'テスト白地図',
            'svg_path' => 'maps/custom/sample.svg',
            'pdf_path' => 'maps/custom/sample.pdf',
            'is_default' => false,
            'is_active' => true,
        ]);
        $baseMapPayload = $baseMap->presenter()->toModalPayload();
        $this->assertFalse($baseMapPayload['is_preset_asset']);
        $this->assertSame(asset('maps/custom/sample.svg'), $baseMapPayload['svg_url']);
        $this->assertSame(route('site-base-maps.update', $baseMap), $baseMapPayload['update_url']);

        $location = Location::factory()->create(['name' => '北川駐車場']);
        $area = LayoutArea::factory()->create(['name' => '北川エリア', 'location_id' => $location->id]);
        $area->load('location.type');
        $areaPayload = $area->presenter()->toModalPayload();
        $this->assertSame('北川駐車場', $areaPayload['location_name']);
        $this->assertSame(route('layout-areas.update', $area), $areaPayload['update_url']);

        $user = User::factory()->create();
        $timetable = Timetable::factory()->create();
        $map = LayoutMap::create([
            'timetable_id' => $timetable->id,
            'name' => 'テスト配置図',
            'version_tag' => 'v1',
            'created_by' => $user->id,
        ]);
        $vendor = Vendor::create(['name' => '宿場商店', 'contact_person' => '田中', 'contact' => '090-0000-0000']);

        $boothCategory = BoothCategory::cases()[0];
        $boothType = BoothType::cases()[0];
        $facilityType = FacilityType::cases()[0];
        $status = LayoutItemStatus::cases()[0];

        $boothItem = LayoutItem::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $area->id,
            'item_type' => LayoutItemType::Booth->value,
            'code' => 'B-01',
            'name' => null,
            'status' => $status->value,
            'category' => $boothCategory->value,
            'booth_type' => $boothType->value,
            'vendor_id' => $vendor->id,
        ]);
        $boothItem->load(['vendor', 'area']);

        $this->assertSame('宿場商店', $boothItem->presenter()->displayName());
        $this->assertSame($boothCategory->label(), $boothItem->presenter()->typeLabel());
        $this->assertSame($status->badgeClasses(), $boothItem->presenter()->statusBadgeClass());

        $facilityItem = LayoutItem::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $area->id,
            'item_type' => LayoutItemType::Facility->value,
            'code' => 'F-01',
            'name' => '給水所',
            'status' => $status->value,
            'category' => $facilityType->value,
        ]);
        $this->assertSame('給水所', $facilityItem->presenter()->displayName());
        $this->assertSame($facilityType->label(), $facilityItem->presenter()->typeLabel());

        $vendor->load('items.area', 'items.vendor');
        $boothsList = $vendor->presenter()->boothsDetailList();
        $this->assertCount(1, $boothsList);
        $this->assertSame('B-01', $boothsList[0]['code']);
        $this->assertSame('北川エリア', $boothsList[0]['area']);
    }

    public function test_timetable_lane_task_assignment_and_lane_request_presenters(): void
    {
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-17',
            'status' => TimetableStatus::Active->value,
        ]);
        $this->assertSame('土', $timetable->presenter()->weekdayLabel());
        $this->assertSame(TimetableStatus::Active->label(), $timetable->presenter()->statusLabel());
        $this->assertSame('開催日程準備中', TimetablePresenter::formatEventDateRange(new EloquentCollection));

        $leadStaff = Staff::factory()->create(['name' => '宮永 健一']);
        $fixedStaff = Staff::factory()->create(['name' => '佐藤 花子']);

        $lane = Lane::factory()->create(['name' => '誘導班', 'color' => 'sky', 'icon' => '🚩']);
        $tLane = TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'lead_staff_id' => $leadStaff->id,
        ]);
        $tLane->fixedStaff()->attach($fixedStaff->id);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $tLane->id,
            'title' => '会場警備',
            'start_time' => '09:00:00',
            'end_time' => '11:30:00',
            'required_people_count' => 2,
            'is_at_least' => true,
            'content' => 'ゲート前の混雑誘導',
        ]);

        $assignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $fixedStaff->id,
            'attendance_status' => 'assigned',
        ]);

        $requestType = LaneRequestType::cases()[0];
        $requestStatus = LaneRequestStatus::cases()[0];
        $laneRequest = LaneRequest::create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $tLane->id,
            'timetable_task_id' => $task->id,
            'requested_by_staff_id' => $leadStaff->id,
            'request_type' => $requestType->value,
            'requested_count' => 2,
            'reason' => '混雑対応',
            'status' => $requestStatus->value,
        ]);

        $lane->load('timetableLanes.timetable');
        $this->assertSame('🚩', $lane->presenter()->iconLabel());
        $this->assertSame('🚩', $lane->presenter()->matrixIconLabel());
        $this->assertSame('スカイ（空）', $lane->presenter()->toModalPayload()['color_label']);
        $this->assertStringContainsString('sky', LanePresenter::timelineBadgeClassFor('sky'));

        $tLane->load(['lane', 'leadStaff', 'fixedStaff', 'tasks', 'laneRequests.task', 'laneRequests.requestedByStaff']);
        $this->assertSame('宮永', $tLane->presenter()->leadShortName());
        $this->assertSame('（宮永さん）', $tLane->presenter()->pdfLeadSuffix());
        $this->assertSame('11:30', $tLane->presenter()->suggestedStartTime());
        $this->assertCount(1, $tLane->presenter()->toModalPayload()['tasks']);
        $this->assertCount(1, $tLane->presenter()->toModalPayload()['lane_requests']);

        $task->load(['timetableLane.lane', 'timetableLane.leadStaff', 'assignments']);
        $this->assertSame('09:00〜11:30', $task->presenter()->timeRange());
        $this->assertSame('2名以上', $task->presenter()->requiredPeopleLabel());
        $this->assertSame('【2名以上】', $task->presenter()->matrixPeopleLabel());
        $this->assertSame('誘導班', $task->presenter()->laneNameLabel());
        $this->assertSame('🚩', $task->presenter()->laneIconLabel());
        $this->assertSame(route('timetable-tasks.update', $task), $task->presenter()->toModalPayload()['update_url']);

        $assignment->load(['task.timetable', 'task.timetableLane.leadStaff', 'task.assignments']);
        $this->assertSame('2026年10月17日（土）', $assignment->presenter()->dateHeaderLabel());
        $this->assertTrue($assignment->presenter()->hasDetails());

        $this->assertSame($requestType->label(), $laneRequest->presenter()->typeLabel());
        $this->assertSame($requestStatus->label(), $laneRequest->presenter()->statusLabel());
    }
}
