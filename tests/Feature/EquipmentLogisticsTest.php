<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
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
}
