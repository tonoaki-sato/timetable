<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\Location;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentLogisticsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable1;

    private Timetable $timetable2;

    private Location $locationTaxOffice;

    private Location $locationKitagawa;

    private EquipmentLogisticsPattern $pattern;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable1 = Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目', 'status' => 'active']);
        $this->timetable2 = Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '2日目']);

        $this->locationTaxOffice = Location::factory()->create([
            'timetable_id' => $this->timetable1->id,
            'name' => '税務署前駐車場',
        ]);
        $this->locationKitagawa = Location::factory()->create([
            'timetable_id' => $this->timetable1->id,
            'name' => '長島駐車場（旧北川駐車場）',
        ]);

        $this->pattern = EquipmentLogisticsPattern::create([
            'name' => 'テスト標準パターンA',
            'target_type' => 'committee',
            'description' => 'テスト用の搬出入パターン',
        ]);

        $this->pattern->steps()->createMany([
            [
                'display_order' => 10,
                'day_phase' => 'pre_day',
                'action_type' => 'staging_move',
                'title' => '前日倉庫出し',
                'from_location_id' => null,
                'to_location_id' => $this->locationTaxOffice->id,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
            [
                'display_order' => 20,
                'day_phase' => 'day1_evening',
                'action_type' => 'night_storage',
                'title' => '1日目夜間保管',
                'from_location_id' => null,
                'to_location_id' => $this->locationTaxOffice->id,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
            [
                'display_order' => 30,
                'day_phase' => 'post_day',
                'action_type' => 'return_storage',
                'title' => '翌日倉庫返還',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
        ]);
    }

    /**
     * 搬出入計画一覧画面（デフォルトタブ）が表示されること
     */
    public function test_logistics_dashboard_displays_correctly(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => 'テストパイプ椅子',
            'storage_location' => '番所地下倉庫',
        ]);
        $equipment->plans()->create([
            'timetable_id' => $this->timetable1->id,
            'title' => '前日会場搬入',
            'day_phase' => 'pre_day',
            'action_type' => 'staging_move',
        ]);

        Equipment::factory()->rental()->create([
            'name' => 'ステップ未設定資材',
        ]);

        $response = $this->get(route('timetables.logistics.index', $this->timetable1));
        $response->assertStatus(200);
        $response->assertSee('搬出入計画一覧');
        $response->assertSee('搬出入指示書PDF');
        $response->assertSee('すべて折りたたむ');
        $response->assertSee('<details', false);
        $response->assertSee('<table', false);
        $response->assertSee('順序');
        $response->assertSee('フェーズ');
        $response->assertSee('ステップ表示名');
        $response->assertSee('移動元 ➔ 移動先');
        $response->assertSee('状態');
        $response->assertDontSee('配備資材数');
        $response->assertDontSee('計画ステップ総数');
        $response->assertDontSee('完了ステップ進捗');
        $response->assertDontSee('幹事立会必須</span>\n                <div', false);
        $response->assertSee('テストパイプ椅子');
        $response->assertSee('前日会場搬入');
        $response->assertSee('前日（準備日）');
        $response->assertSee('ステップ未設定資材');
        $response->assertSee('ステップ未設定');

        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
    }

    /**
     * 搬出入パターンマスター一覧画面が表示されること
     */
    public function test_equipment_patterns_master_index_displays_correctly(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('equipment-patterns.index'));
        $response->assertStatus(200);
        $response->assertSee('搬出入パターン一覧');
        $response->assertSee('テスト標準パターンA');
        $response->assertSee('税務署前駐車場');
        $response->assertSee('前日倉庫出し');
        $response->assertSee('すべて折りたたむ');
    }

    /**
     * 運行情報一覧および運行詳細に搬出入計画への導線が存在すること
     */
    public function test_timetable_pages_include_logistics_navigation(): void
    {
        $this->actingAs($this->operator);

        $this->get(route('timetables.index'))
            ->assertStatus(200)
            ->assertSee(route('timetables.logistics.index', $this->timetable1))
            ->assertSee('搬出入');

        $this->get(route('timetables.show', $this->timetable1))
            ->assertStatus(200)
            ->assertSee('資材搬出入・物流配備サマリー')
            ->assertSee(route('timetables.logistics.index', $this->timetable1));
    }

    /**
     * カスタム搬出入パターンの新規登録・更新・削除ができること
     */
    public function test_can_manage_logistics_patterns(): void
    {
        $this->actingAs($this->operator);

        // 1. 新規登録
        $response = $this->post(route('equipment-patterns.store'), [
            'name' => '新規作成パターン',
            'target_type' => 'rental',
            'description' => '業者レンタル機材用',
            'steps' => [
                [
                    'display_order' => 10,
                    'day_phase' => 'pre_day',
                    'action_type' => 'vendor_receive',
                    'title' => '業者受取・立会',
                    'operator_type' => 'committee',
                    'witness_required' => 1,
                ],
                [
                    'display_order' => 20,
                    'day_phase' => 'post_day',
                    'action_type' => 'vendor_pickup',
                    'title' => '業者回収・立会',
                    'operator_type' => 'committee',
                    'witness_required' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('equipment-patterns.index'));
        $newPattern = EquipmentLogisticsPattern::where('name', '新規作成パターン')->first();
        $this->assertNotNull($newPattern);
        $this->assertEquals(2, $newPattern->steps()->count());

        // 2. 更新
        $updateResponse = $this->put(route('equipment-patterns.update', $newPattern), [
            'name' => '更新後パターン名',
            'target_type' => 'rental',
            'description' => '更新後の説明',
            'steps' => [
                [
                    'display_order' => 10,
                    'day_phase' => 'day1_morning',
                    'action_type' => 'setup',
                    'title' => '朝設営',
                    'operator_type' => 'committee',
                    'witness_required' => 0,
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('equipment-patterns.index'));
        $newPattern->refresh();
        $this->assertEquals('更新後パターン名', $newPattern->name);
        $this->assertEquals(1, $newPattern->steps()->count());

        // 3. 削除
        $deleteResponse = $this->delete(route('equipment-patterns.destroy', $newPattern));
        $deleteResponse->assertRedirect(route('equipment-patterns.index'));
        $this->assertDatabaseMissing('equipment_logistics_patterns', ['id' => $newPattern->id]);
    }

    /**
     * 資材へパターンを一括適用できること
     */
    public function test_can_apply_pattern_to_equipment(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => '大型パイプテント',
            'quantity' => 10,
            'storage_location' => '番所地下倉庫',
        ]);

        $response = $this->post(route('timetables.logistics.apply-template', [$this->timetable1, $equipment]), [
            'pattern_id' => $this->pattern->id,
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationKitagawa->id,
            'night_location_2_id' => '',
        ]);

        $response->assertRedirect();

        // 3ステップが登録されていること
        $this->assertEquals(3, $equipment->plans()->count());

        // 移動先が反映されていること
        $stagingPlan = $equipment->plans()->where('day_phase', 'pre_day')->first();
        $this->assertNotNull($stagingPlan);
        $this->assertEquals($this->locationTaxOffice->id, $stagingPlan->to_location_id);
        $this->assertEquals($this->timetable1->id, $stagingPlan->timetable_id);
    }

    /**
     * 搬出入指示書兼現場チェックリストPDFダウンロードが正常に動作すること
     */
    public function test_can_download_logistics_pdf(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.logistics.print', $this->timetable1));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * A4横 搬出入指示書のプレビュー表示が正常に描画されること
     */
    public function test_can_preview_logistics_print_sheet(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.logistics.print', ['timetable' => $this->timetable1, 'preview' => 1]));

        $response->assertStatus(200);
        $response->assertSee('資材搬出入計画書 印刷プレビュー');
        $response->assertSee('ブラウザ印刷');
    }

    /**
     * 資材の全ステップを一括削除できること
     */
    public function test_clear_plans_removes_all_steps_of_equipment(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create(['name' => '一括クリア検証資材']);
        $equipment->plans()->create(['timetable_id' => $this->timetable1->id, 'title' => 'ステップ1', 'day_phase' => 'pre_day']);
        $equipment->plans()->create(['timetable_id' => $this->timetable1->id, 'title' => 'ステップ2', 'day_phase' => 'day1_morning']);

        $this->assertSame(2, $equipment->plans()->count());

        $response = $this->delete(route('timetables.logistics.plans.clear', [$this->timetable1, $equipment]));
        $response->assertRedirect();

        $this->assertDatabaseHas('equipments', ['id' => $equipment->id]);
        $this->assertSame(0, $equipment->plans()->where('timetable_id', $this->timetable1->id)->count());
    }

    /**
     * 個別の搬出入ステップを削除できること
     */
    public function test_destroy_plan_removes_individual_step(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create();
        $plan = $equipment->plans()->create([
            'timetable_id' => $this->timetable1->id,
            'title' => '不要になった個別ステップ',
            'day_phase' => 'pre_day',
        ]);

        $response = $this->delete(route('timetables.logistics.plans.destroy', [$this->timetable1, $plan]));
        $response->assertRedirect();

        $this->assertDatabaseMissing('equipment_logistics_plans', ['id' => $plan->id]);
    }

    /**
     * 印刷プレビューで区分によるフィルタリングが正常に動作すること
     */
    public function test_print_preview_supports_filtering(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->rental()->create([
            'name' => 'トランシーバー特小',
        ]);

        $equipment->plans()->create([
            'timetable_id' => $this->timetable1->id,
            'title' => '前日受取立会',
            'day_phase' => 'pre_day',
            'action_type' => 'vendor_receive',
            'operator_type' => 'committee',
            'witness_required' => true,
        ]);

        $response = $this->get(route('timetables.logistics.print', [
            'timetable' => $this->timetable1,
            'preview' => 1,
            'type' => 'rental',
        ]));

        $response->assertStatus(200);
        $response->assertSee('トランシーバー特小');
    }

    /**
     * パターンデータや拠点が0件の状態でも、搬出入計画一覧や印刷画面が例外なく描画されること
     */
    public function test_logistics_views_and_print_render_cleanly_when_initial_data_is_empty(): void
    {
        $this->actingAs($this->operator);

        EquipmentLogisticsPattern::query()->delete();
        Location::query()->delete();
        Equipment::query()->delete();

        $this->get(route('timetables.logistics.index', $this->timetable1))
            ->assertStatus(200)
            ->assertSee('搬出入計画一覧')
            ->assertSee('条件に該当する資材はありません');

        $this->get(route('timetables.logistics.print', ['timetable' => $this->timetable1, 'preview' => 1]))
            ->assertStatus(200)
            ->assertSee('該当する資材または搬出入計画データはありません');

        $this->get(route('timetables.logistics.print', $this->timetable1))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * 拠点が未登録（0件）の状態でもパターン一括適用がエラーなく動作すること
     */
    public function test_apply_pattern_works_when_no_locations_exist(): void
    {
        $this->actingAs($this->operator);

        Location::query()->delete();

        $equipment = Equipment::factory()->committee()->create([
            'name' => '拠点なし検証資材',
            'quantity' => 10,
            'storage_location' => '番所地下倉庫',
        ]);

        $response = $this->post(route('timetables.logistics.apply-template', [$this->timetable1, $equipment]), [
            'pattern_id' => $this->pattern->id,
            'staging_location_id' => '',
            'night_location_1_id' => '',
            'night_location_2_id' => '',
        ]);

        $response->assertRedirect();
        $this->assertSame(3, $equipment->plans()->where('timetable_id', $this->timetable1->id)->count());
    }

    /**
     * 個別資材の搬出入計画画面が表示され、マスター詳細および計画一覧への導線が存在すること
     */
    public function test_equipment_logistics_show_displays_plans_and_navigation(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（個別計画検証）',
            'storage_location' => '番所地下倉庫',
        ]);

        $equipment->plans()->create([
            'timetable_id' => $this->timetable1->id,
            'title' => '前日13時 中継駐車場へ運搬',
            'day_phase' => 'pre_day',
            'action_type' => 'staging_move',
        ]);

        $response = $this->get(route('timetables.logistics.equipments.show', [$this->timetable1, $equipment]));
        $response->assertStatus(200);
        $response->assertSee('カラーコーン（個別計画検証）');
        $response->assertSee('前日13時 中継駐車場へ運搬');
        $response->assertSee('搬出入・中継・夜間保管計画ステップ一覧');
        $response->assertSee(route('equipments.show', $equipment));
        $response->assertSee(route('timetables.logistics.index', $this->timetable1));
    }

    /**
     * 拠点制約警告が存在する場合でも搬出入詳細画面がエラーなく正常に描画されること
     */
    public function test_equipment_logistics_show_renders_correctly_with_location_warnings(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->rental()->create([
            'name' => '足踏み式給水設備（警告テスト）',
        ]);

        // スケジュール未登録の拠点を移動先に指定することで警告（location_warnings）を発生させる
        $locationWithoutSchedule = Location::factory()->create([
            'timetable_id' => $this->timetable1->id,
            'name' => '未登録駐車場',
            'is_all_day' => false,
            'is_active' => true,
        ]);

        $equipment->plans()->create([
            'timetable_id' => $this->timetable1->id,
            'title' => '給水設備受取・移動',
            'target_date' => '2026-10-10',
            'day_phase' => 'pre_day',
            'action_type' => 'staging_move',
            'to_location_id' => $locationWithoutSchedule->id,
            'to_location' => $locationWithoutSchedule->name,
        ]);

        $response = $this->get(route('timetables.logistics.equipments.show', [$this->timetable1, $equipment]));
        $response->assertStatus(200);
        $response->assertSee('足踏み式給水設備（警告テスト）');
        $response->assertSee('給水設備受取・移動');
        $response->assertSee('利用可能時間帯が登録されていません');
    }
}
