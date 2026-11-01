<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Lane;
use App\Models\Location;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentLogisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Location $locationTaxOffice;

    private Location $locationKitagawa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目']);
        Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '2日目']);

        $this->locationTaxOffice = Location::factory()->create(['name' => '税務署前駐車場']);
        $this->locationKitagawa = Location::factory()->create(['name' => '北川駐車場']);
    }

    /**
     * 搬出入計画ダッシュボード画面が表示されること
     */
    public function test_logistics_dashboard_displays_correctly(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create(['name' => 'カラーコーン']);
        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'title' => '税務署前へ中継移動',
            'day_phase' => 'pre_day',
        ]);

        $response = $this->get(route('logistics.index'));
        $response->assertStatus(200);
        $response->assertSee('資材・機材 搬出入計画');
        $response->assertSee('カラーコーン');
        $response->assertSee('税務署前へ中継移動');
    }

    /**
     * 搬出入計画の各タブ（フロー・チェックリスト・拠点保管・イベント会社）が表示されること
     */
    public function test_logistics_tabs_display_correctly(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->rental()->create([
            'name' => '足踏み式給水設備',
            'event_company_name' => '東京イベントサプライ株式会社',
        ]);

        EquipmentLogisticsPlan::factory()->create([
            'equipment_id' => $equipment->id,
            'title' => '給水設備 業者受取立会',
            'day_phase' => 'pre_day',
            'witness_required' => true,
        ]);

        // 1. 資材別フロー
        $flowRes = $this->get(route('logistics.index', ['tab' => 'flow']));
        $flowRes->assertStatus(200);
        $flowRes->assertSee('足踏み式給水設備');

        // 2. フェーズ別チェックリスト
        $chkRes = $this->get(route('logistics.index', ['tab' => 'checklist', 'phase' => 'pre_day']));
        $chkRes->assertStatus(200);
        $chkRes->assertSee('給水設備 業者受取立会');

        // 3. 拠点別保管
        $locRes = $this->get(route('logistics.index', ['tab' => 'locations']));
        $locRes->assertStatus(200);
        $locRes->assertSee('税務署前駐車場');

        // 4. イベント会社スケジュール
        $evtRes = $this->get(route('logistics.index', ['tab' => 'events']));
        $evtRes->assertStatus(200);
        $evtRes->assertSee('東京イベントサプライ株式会社');
    }

    /**
     * テンプレート適用でステップが一括自動生成されること
     */
    public function test_apply_template_creates_plans_correctly(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（テンプレートテスト）',
            'quantity' => 50,
            'storage_location' => '番所地下倉庫',
        ]);

        $response = $this->post(route('logistics.apply-template', $equipment), [
            'template_type' => 'pattern_a',
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'night_location_2_id' => $this->locationKitagawa->id,
        ]);

        $response->assertRedirect();
        $this->assertGreaterThanOrEqual(5, $equipment->plans()->count());
    }

    /**
     * テンプレート再適用時に既存ステップを安全に上書き（重複増殖防止）できること
     */
    public function test_apply_template_safely_overwrites_existing_plans(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（上書き検証）',
            'quantity' => 40,
        ]);

        // 1回目の適用
        $this->post(route('logistics.apply-template', $equipment), [
            'template_type' => 'pattern_a',
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
        ]);
        $firstCount = $equipment->plans()->count();
        $this->assertGreaterThan(0, $firstCount);

        // 2回目の適用（上書きデフォルト）
        $this->post(route('logistics.apply-template', $equipment), [
            'template_type' => 'pattern_a',
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'override_existing' => true,
        ]);

        // 重複して2倍にならず、同件数であること
        $this->assertSame($firstCount, $equipment->plans()->count());
    }

    /**
     * 資材に紐づく計画ステップを一括クリア（全削除）できること
     */
    public function test_clear_plans_removes_all_steps_of_equipment(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create(['name' => '一括クリア検証資材']);
        $equipment->plans()->create(['title' => 'ステップ1', 'day_phase' => 'pre_day']);
        $equipment->plans()->create(['title' => 'ステップ2', 'day_phase' => 'day1_morning']);

        $this->assertSame(2, $equipment->plans()->count());

        $response = $this->delete(route('logistics.plans.clear', $equipment));
        $response->assertRedirect();

        // 資材自体は残るが、計画ステップのみ0件になること
        $this->assertDatabaseHas('equipments', ['id' => $equipment->id]);
        $this->assertSame(0, $equipment->plans()->count());
    }

    /**
     * 個別の搬出入ステップを削除できること
     */
    public function test_destroy_plan_removes_individual_step(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create();
        $plan = $equipment->plans()->create([
            'title' => '不要になった個別ステップ',
            'day_phase' => 'pre_day',
        ]);

        $response = $this->delete(route('logistics.plans.destroy', $plan));
        $response->assertRedirect();

        $this->assertDatabaseMissing('equipment_logistics_plans', ['id' => $plan->id]);
    }

    /**
     * A4横 搬出入計画書・チェックリスト印刷画面が表示されること
     */
    public function test_logistics_print_displays_correctly(): void
    {
        $this->actingAs($this->operator);

        Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（印刷検証）',
            'quantity' => 30,
        ]);

        $response = $this->get(route('logistics.print'));
        $response->assertStatus(200);
        $response->assertSee('資材・機材 搬出入・中継・夜間保管計画書');
        $response->assertSee('カラーコーン（印刷検証）');
        $response->assertSee('税務署前駐車場');
    }

    /**
     * 印刷画面で担当班やカテゴリによる絞り込みができること
     */
    public function test_logistics_print_supports_filtering(): void
    {
        $this->actingAs($this->operator);

        $coneLane = Lane::factory()->create(['name' => '特設コーン班']);
        $tentLane = Lane::factory()->create(['name' => '特設テント班']);

        Equipment::factory()->create([
            'name' => 'コーン班専用機材',
            'lane_id' => $coneLane->id,
            'category' => 'heavy_equipment',
        ]);

        Equipment::factory()->create([
            'name' => 'テント班専用台車',
            'lane_id' => $tentLane->id,
            'category' => 'carrier',
        ]);

        // 担当班で絞り込み
        $laneResponse = $this->get(route('logistics.print', ['lane_id' => $coneLane->id]));
        $laneResponse->assertStatus(200);
        $laneResponse->assertSee('コーン班専用機材');
        $laneResponse->assertDontSee('テント班専用台車');

        // カテゴリで絞り込み
        $catResponse = $this->get(route('logistics.print', ['category' => 'carrier']));
        $catResponse->assertStatus(200);
        $catResponse->assertSee('テント班専用台車');
        $catResponse->assertDontSee('コーン班専用機材');
    }
}
