<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Location;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentLogisticsTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
            'target_date' => '2026-10-11',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
        ]);
    }

    public function test_guest_cannot_access_logistics_timeline(): void
    {
        $this->get(route('logistics-timeline.index'))
            ->assertRedirect(route('login'));

        $this->get(route('timetables.logistics-timeline', $this->timetable))
            ->assertRedirect(route('login'));
    }

    public function test_redirect_route_redirects_to_active_timetable_timeline(): void
    {
        $this->actingAs($this->operator)
            ->get(route('logistics-timeline.index'))
            ->assertRedirect(route('timetables.logistics-timeline', $this->timetable));
    }

    public function test_redirect_route_redirects_to_timetables_index_when_no_timetables(): void
    {
        Timetable::query()->delete();

        $this->actingAs($this->operator)
            ->get(route('logistics-timeline.index'))
            ->assertRedirect(route('timetables.index'));
    }

    public function test_logistics_timeline_displays_header_and_date_blocks_with_plans(): void
    {
        $banshoLocation = Location::factory()->create(['name' => '番所地下倉庫']);
        $honjinLocation = Location::factory()->create(['name' => '本陣']);

        $equipment1 = Equipment::factory()->create([
            'name' => 'パイプ椅子',
            'type' => 'committee',
            'quantity' => 60,
            'unit' => '脚',
            'storage_location_id' => $banshoLocation->id,
        ]);

        $equipment2 = Equipment::factory()->create([
            'name' => '大型発電機',
            'type' => 'rental',
            'quantity' => 2,
            'unit' => '台',
            'storage_location_id' => $honjinLocation->id,
        ]);

        // 前日準備の計画
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $this->timetable->id,
            'equipment_id' => $equipment1->id,
            'target_date' => '2026-10-10',
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '倉庫出し事前移動',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'from_location' => '番所地下倉庫',
            'to_location' => '本陣テント',
            'from_location_id' => null,
            'to_location_id' => null,
            'status' => 'planned',
        ]);

        // 当日の計画
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $this->timetable->id,
            'equipment_id' => $equipment2->id,
            'target_date' => '2026-10-11',
            'day_phase' => 'day1_morning',
            'action_type' => 'vendor_receive',
            'title' => '業者受取・設置',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'from_location' => '本陣',
            'to_location' => 'ステージ横',
            'from_location_id' => null,
            'to_location_id' => null,
            'status' => 'in_progress',
        ]);

        // 時間未指定の計画（当日）
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $this->timetable->id,
            'equipment_id' => $equipment1->id,
            'target_date' => '2026-10-11',
            'day_phase' => 'day1_evening',
            'action_type' => 'night_storage',
            'title' => '夜間片付け（時間未定）',
            'start_time' => null,
            'end_time' => null,
            'from_location' => '会場',
            'to_location' => '番所地下倉庫',
            'from_location_id' => null,
            'to_location_id' => null,
            'status' => 'planned',
        ]);

        // 日付未指定の計画
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $this->timetable->id,
            'equipment_id' => $equipment2->id,
            'target_date' => null,
            'day_phase' => 'post_day',
            'action_type' => 'vendor_pickup',
            'title' => '翌日回収（日付未定）',
            'start_time' => null,
            'end_time' => null,
            'from_location' => 'ステージ横',
            'to_location' => '業者トラック',
            'from_location_id' => null,
            'to_location_id' => null,
            'status' => 'planned',
        ]);

        // 別の運行情報（他日程）の計画：混入して表示されてはならない
        $otherTimetable = Timetable::factory()->create([
            'name' => '本祭2日目',
            'status' => 'upcoming',
            'target_date' => '2026-10-12',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
        ]);

        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $otherTimetable->id,
            'equipment_id' => $equipment1->id,
            'target_date' => '2026-10-12',
            'day_phase' => 'day2_morning',
            'action_type' => 'staging_move',
            'title' => '他運行日限定の資材移動作業',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'from_location' => '別会場',
            'to_location' => '本部テント',
            'from_location_id' => null,
            'to_location_id' => null,
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('timetables.logistics-timeline', $this->timetable));

        $response->assertStatus(200);

        // 指定されたタイトル・説明文が表示され、削除指示されたボタンやバッジがないこと
        $response->assertSee('本祭1日目 搬出入タイムライン');
        $response->assertSee('資材・機材の搬出入計画をガントチャートで俯瞰します。');
        $response->assertDontSee('Step 4 搬出入管理');
        $response->assertDontSee('搬出入指示書PDF');

        // 日付ブロックが分かれて表示されていること
        $response->assertSee('2026年10月10日 (土)');
        $response->assertSee('前日準備');
        $response->assertSee('2026年10月11日 (日)');
        $response->assertSee('運行日 当日');

        // 機材名と計画内容が表示されていること
        $response->assertSee('パイプ椅子');
        $response->assertSee('大型発電機');
        $response->assertSee('倉庫出し事前移動');
        $response->assertSee('番所地下倉庫 → 本陣テント');
        $response->assertSee('業者受取・設置');
        $response->assertSee('本陣 → ステージ横');

        // 時間未指定・日付未指定計画の補助表示
        $response->assertSee('夜間片付け（時間未定）');
        $response->assertSee('実施日未指定の搬出入計画');
        $response->assertSee('翌日回収（日付未定）');

        // 別運行日の計画が混入しないこと
        $response->assertDontSee('他運行日限定の資材移動作業');

        // 別運行日に切り替えた場合は、その運行日の計画のみが表示されること
        $otherResponse = $this->actingAs($this->operator)
            ->get(route('timetables.logistics-timeline', $otherTimetable));

        $otherResponse->assertStatus(200);
        $otherResponse->assertSee('本祭2日目 搬出入タイムライン');
        $otherResponse->assertSee('他運行日限定の資材移動作業');
        $otherResponse->assertDontSee('倉庫出し事前移動');
        $otherResponse->assertDontSee('業者受取・設置');

        // CSP準拠チェック（インラインスタイル・イベントハンドラの不在）
        $content = $response->getContent();
        $this->assertStringNotContainsString('style="', $content);
        $this->assertStringNotContainsString('onclick="', $content);
    }
}
