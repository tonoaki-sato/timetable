<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Lane;
use App\Models\LogisticsLocation;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private LogisticsLocation $locationTaxOffice;

    private LogisticsLocation $locationKitagawa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目']);
        Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '2日目']);

        $this->locationTaxOffice = LogisticsLocation::factory()->create(['name' => '税務署前駐車場']);
        $this->locationKitagawa = LogisticsLocation::factory()->create(['name' => '北川駐車場']);
    }

    /**
     * 資材一覧画面が表示され、区分でフィルタできること
     */
    public function test_index_displays_and_filters_equipments(): void
    {
        $this->actingAs($this->operator);

        $committeeItem = Equipment::factory()->committee()->create(['name' => 'カラーコーン']);
        $rentalItem = Equipment::factory()->rental()->create(['name' => 'パイプテント']);

        $response = $this->get(route('equipments.index', ['tab' => 'flow']));
        $response->assertStatus(200);
        $response->assertSee('カラーコーン');
        $response->assertSee('パイプテント');

        // 委員会管理のみフィルタ
        $committeeResponse = $this->get(route('equipments.index', ['tab' => 'flow', 'type' => 'committee']));
        $committeeResponse->assertSee('カラーコーン');
        $committeeResponse->assertDontSee('パイプテント');
    }

    /**
     * 委員会管理機材の新規登録ができること
     */
    public function test_store_creates_committee_equipment(): void
    {
        $this->actingAs($this->operator);
        $lane = Lane::factory()->create(['name' => 'カラーコーン']);

        $response = $this->post(route('equipments.store'), [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 50,
            'unit' => '個',
            'storage_location' => '番所地下倉庫',
            'lane_id' => $lane->id,
            'description' => '路上規制用コーン',
            'template_type' => 'none',
        ]);

        $response->assertRedirect(route('equipments.index'));

        $this->assertDatabaseHas('equipments', [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 50,
            'storage_location' => '番所地下倉庫',
        ]);
    }

    /**
     * イベント会社レンタル機材の新規登録ができること
     */
    public function test_store_creates_rental_equipment(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('equipments.store'), [
            'name' => 'パイプテント 2K×3K',
            'type' => 'rental',
            'quantity' => 10,
            'unit' => '張',
            'event_company_name' => '○○イベント企画',
            'event_company_contact' => '03-9999-8888',
            'template_type' => 'none',
        ]);

        $response->assertRedirect(route('equipments.index'));

        $this->assertDatabaseHas('equipments', [
            'name' => 'パイプテント 2K×3K',
            'type' => 'rental',
            'quantity' => 10,
            'event_company_name' => '○○イベント企画',
        ]);
    }

    /**
     * 新規登録時にテンプレート（パターンA）を適用してステップが自動生成されること
     */
    public function test_store_with_template_creates_plans_automatically(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('equipments.store'), [
            'name' => 'カラーコーン（一括テンプレート）',
            'type' => 'committee',
            'quantity' => 40,
            'unit' => '個',
            'storage_location' => '番所地下倉庫',
            'template_type' => 'pattern_a',
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'night_location_2_id' => $this->locationKitagawa->id,
        ]);

        $response->assertRedirect(route('equipments.index'));

        $equipment = Equipment::where('name', 'カラーコーン（一括テンプレート）')->first();
        $this->assertNotNull($equipment);
        $this->assertGreaterThanOrEqual(5, $equipment->plans()->count());
    }

    /**
     * 資材の更新と削除（関連ステップの連鎖削除）ができること
     */
    public function test_update_and_destroy_equipment(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => '旧・機材名',
            'quantity' => 10,
        ]);

        $updateResponse = $this->put(route('equipments.update', $equipment), [
            'name' => '新・機材名',
            'type' => 'committee',
            'quantity' => 15,
            'unit' => '個',
            'storage_location' => 'コミュニティルーム',
        ]);

        $updateResponse->assertRedirect(route('equipments.show', $equipment));
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'name' => '新・機材名',
            'quantity' => 15,
            'storage_location' => 'コミュニティルーム',
        ]);

        // 削除テスト
        $deleteResponse = $this->delete(route('equipments.destroy', $equipment));
        $deleteResponse->assertRedirect(route('equipments.index'));
        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
    }

    /**
     * 資材一覧の events タブおよび関連画面が CSP に準拠し、インラインスクリプトやインラインイベントハンドラを含まないこと
     */
    public function test_events_tab_and_equipments_views_comply_with_csp_without_inline_scripts_or_handlers(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->rental()->create([
            'name' => 'パイプテント 2K×3K',
            'event_company_name' => 'テストイベント企画',
        ]);

        $urls = [
            route('equipments.index', ['tab' => 'events']),
            route('equipments.index', ['tab' => 'flow']),
            route('equipments.create'),
            route('equipments.show', $equipment),
            route('equipments.edit', $equipment),
            route('equipments.print', ['format' => 'events']),
        ];

        foreach ($urls as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);

            $csp = $response->headers->get('Content-Security-Policy');
            $this->assertNotNull($csp, "CSP header must be present on {$url}");
            $this->assertStringContainsString("script-src 'self'", $csp);
            $this->assertStringNotContainsString('unsafe-inline', $csp);

            $content = $response->getContent();

            // インライン <script>（src属性を持たないタグ）が存在しないこと
            $this->assertDoesNotMatchRegularExpression(
                '/<script(?![^>]*\bsrc=)[^>]*>/i',
                $content,
                "Inline <script> tag found on {$url}"
            );

            // インラインイベントハンドラ属性（onclick, onchange, onsubmit 等）が存在しないこと
            $this->assertDoesNotMatchRegularExpression(
                '/\s(on[a-z]+)\s*=/i',
                $content,
                "Inline event handler attribute found on {$url}"
            );

            // インライン <style> タグが存在しないこと
            $this->assertDoesNotMatchRegularExpression(
                '/<style\b[^>]*>/i',
                $content,
                "Inline <style> tag found on {$url}"
            );
        }
    }
}
