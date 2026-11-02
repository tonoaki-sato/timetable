<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
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

    private EquipmentLogisticsPattern $pattern;

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
        $this->locationKitagawa = Location::factory()->create(['name' => '長島駐車場（旧北川駐車場）']);

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
                'to_location_id' => $this->locationKitagawa->id,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
            [
                'display_order' => 30,
                'day_phase' => 'day2_evening',
                'action_type' => 'return_storage',
                'title' => '2日目夜撤収格納',
                'from_location_id' => $this->locationKitagawa->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
            ],
        ]);
    }

    /**
     * 搬出入パターン管理画面が表示されること
     */
    public function test_logistics_dashboard_displays_correctly(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('logistics.index'));
        $response->assertStatus(200);
        $response->assertSee('搬出入パターン管理');
        $response->assertSee('テスト標準パターンA');
        $response->assertSee('前日倉庫出し');
    }

    /**
     * 搬出入パターンの詳細情報が一覧に表示されること
     */
    public function test_logistics_tabs_display_correctly(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('logistics.index', ['tab' => 'patterns']));
        $response->assertStatus(200);
        $response->assertSee('テスト標準パターンA');
        $response->assertSee('税務署前駐車場');
    }

    /**
     * カスタム搬出入パターンの新規登録・更新・削除ができること
     */
    public function test_can_create_update_and_delete_logistics_pattern(): void
    {
        $this->actingAs($this->operator);

        $storeRes = $this->post(route('logistics.patterns.store'), [
            'name' => 'パターンC: イベント会社設営一任',
            'target_type' => 'rental',
            'description' => 'パイプテント等の設営・解体',
            'steps' => [
                [
                    'day_phase' => 'day1_morning',
                    'action_type' => 'setup',
                    'title' => 'イベント会社による搬入・設営',
                    'from_location_id' => null,
                    'to_location_id' => $this->locationTaxOffice->id,
                    'operator_type' => 'event_company',
                    'witness_required' => false,
                ],
                [
                    'day_phase' => 'day2_evening',
                    'action_type' => 'vendor_pickup',
                    'title' => 'イベント会社による解体・搬出',
                    'from_location_id' => $this->locationTaxOffice->id,
                    'to_location_id' => null,
                    'operator_type' => 'event_company',
                    'witness_required' => false,
                ],
            ],
        ]);

        $storeRes->assertRedirect(route('logistics.index', ['tab' => 'patterns']));
        $pattern = EquipmentLogisticsPattern::where('name', 'パターンC: イベント会社設営一任')->first();
        $this->assertNotNull($pattern);
        $this->assertCount(2, $pattern->steps);

        // 更新
        $updateRes = $this->put(route('logistics.patterns.update', $pattern), [
            'name' => 'パターンC: 設営一任（改訂版）',
            'target_type' => 'rental',
            'description' => '更新された説明',
            'steps' => [
                [
                    'day_phase' => 'day1_morning',
                    'action_type' => 'setup',
                    'title' => '搬入設営のみ',
                    'from_location_id' => null,
                    'to_location_id' => $this->locationKitagawa->id,
                    'operator_type' => 'event_company',
                ],
            ],
        ]);
        $updateRes->assertRedirect(route('logistics.index', ['tab' => 'patterns']));
        $pattern->refresh();
        $this->assertSame('パターンC: 設営一任（改訂版）', $pattern->name);
        $this->assertCount(1, $pattern->steps);

        // 削除
        $delRes = $this->delete(route('logistics.patterns.destroy', $pattern));
        $delRes->assertRedirect(route('logistics.index', ['tab' => 'patterns']));
        $this->assertDatabaseMissing('equipment_logistics_patterns', ['id' => $pattern->id]);
    }

    /**
     * パターン適用でステップが一括自動生成され、再適用時に重複せず上書きされること
     */
    public function test_apply_pattern_creates_and_safely_overwrites_plans(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（パターンテスト）',
            'quantity' => 50,
            'storage_location' => '番所地下倉庫',
        ]);

        // 1回目の適用
        $response = $this->post(route('logistics.apply-template', $equipment), [
            'pattern_id' => $this->pattern->id,
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
        ]);

        $response->assertRedirect();
        $firstCount = $equipment->plans()->count();
        $this->assertSame(3, $firstCount);

        // 2回目の適用（上書き）
        $this->post(route('logistics.apply-template', $equipment), [
            'pattern_id' => $this->pattern->id,
            'staging_location_id' => $this->locationTaxOffice->id,
            'night_location_1_id' => $this->locationTaxOffice->id,
            'override_existing' => true,
        ]);

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
     * A4横 搬出入計画書印刷画面が表示され、担当班や区分による絞り込みができること
     */
    public function test_logistics_print_displays_and_supports_filtering(): void
    {
        $this->actingAs($this->operator);

        $coneLane = Lane::factory()->create(['name' => '特設コーン班']);
        $tentLane = Lane::factory()->create(['name' => '特設テント班']);

        Equipment::factory()->committee()->create([
            'name' => 'コーン班専用機材',
            'lane_id' => $coneLane->id,
        ]);

        Equipment::factory()->personal()->create([
            'name' => 'テント班私物台車',
            'lane_id' => $tentLane->id,
        ]);

        // 全体印刷表示
        $response = $this->get(route('logistics.print'));
        $response->assertStatus(200);
        $response->assertSee('資材・機材 搬出入・中継・夜間保管計画書');
        $response->assertSee('コーン班専用機材');
        $response->assertSee('テント班私物台車');

        // 担当班で絞り込み
        $laneResponse = $this->get(route('logistics.print', ['lane_id' => $coneLane->id]));
        $laneResponse->assertStatus(200);
        $laneResponse->assertSee('コーン班専用機材');
        $laneResponse->assertDontSee('テント班私物台車');

        // 区分（personal）で絞り込み
        $typeResponse = $this->get(route('logistics.print', ['type' => 'personal']));
        $typeResponse->assertStatus(200);
        $typeResponse->assertSee('テント班私物台車');
        $typeResponse->assertDontSee('コーン班専用機材');
    }

    /**
     * 初期データが一切ない状態でも搬出入計画の全タブおよび印刷画面がエラーなく表示されること
     */
    public function test_logistics_views_and_print_render_cleanly_when_initial_data_is_empty(): void
    {
        $this->actingAs($this->operator);

        EquipmentLogisticsPattern::query()->delete();
        Location::query()->delete();
        Timetable::query()->delete();
        Equipment::query()->delete();

        $this->get(route('logistics.index'))
            ->assertStatus(200)
            ->assertSee('搬出入パターン管理')
            ->assertSee('登録されている搬出入パターンはありません');

        $this->get(route('logistics.print'))
            ->assertStatus(200)
            ->assertSee('登録されている拠点はありません')
            ->assertSee('該当する資材または搬出入計画データはありません');
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

        $response = $this->post(route('logistics.apply-template', $equipment), [
            'pattern_id' => $this->pattern->id,
            'staging_location_id' => '',
            'night_location_1_id' => '',
            'night_location_2_id' => '',
        ]);

        $response->assertRedirect();
        $this->assertSame(3, $equipment->plans()->count());
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
            'title' => '前日13時 中継駐車場へ運搬',
            'day_phase' => 'pre_day',
            'action_type' => 'staging_move',
        ]);

        $response = $this->get(route('logistics.equipments.show', $equipment));
        $response->assertStatus(200);
        $response->assertSee('カラーコーン（個別計画検証）');
        $response->assertSee('前日13時 中継駐車場へ運搬');
        $response->assertSee('搬出入・中継・夜間保管計画ステップ一覧');
        $response->assertSee(route('equipments.show', $equipment));
        $response->assertSee(route('logistics.index'));
    }
}
