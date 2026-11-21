<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LayoutAreaMasterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    }

    /**
     * 会場エリアマスター一覧画面が専用Viteアセット・モーダルDOM・厳格CSP準拠で表示される
     */
    public function test_index_renders_cleanly_with_vite_script_modals_and_strict_csp(): void
    {
        $locationType = LocationType::create([
            'name' => 'parking',
            'label' => '駐車場',
            'color' => 'amber',
            'display_order' => 10,
            'is_active' => true,
        ]);
        $location = Location::factory()->create([
            'location_type_id' => $locationType->id,
            'name' => '長島駐車場（旧北川駐車場）',
        ]);

        $activeUsedArea = LayoutArea::factory()->forLocation($location)->create([
            'name' => '北川駐車場内',
            'notes' => 'キッチンカー電源あり',
            'display_order' => 10,
            'is_active' => true,
        ]);
        $inactiveUnusedArea = LayoutArea::factory()->inactive()->create([
            'name' => '旧キッズ広場',
            'display_order' => 20,
        ]);

        $tt1 = Timetable::factory()->create(['target_date' => '2026-10-17']);
        $tt2 = Timetable::factory()->create(['target_date' => '2026-10-18']);
        $map1 = LayoutMap::create(['timetable_id' => $tt1->id, 'name' => '1日目配置図']);
        $map2 = LayoutMap::create(['timetable_id' => $tt2->id, 'name' => '2日目配置図']);

        LayoutItem::create([
            'layout_map_id' => $map1->id,
            'layout_area_id' => $activeUsedArea->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'KC-01',
            'status' => LayoutItemStatus::Confirmed,
        ]);
        LayoutItem::create([
            'layout_map_id' => $map2->id,
            'layout_area_id' => $activeUsedArea->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'KC-02',
            'status' => LayoutItemStatus::Confirmed,
        ]);

        $response = $this->actingAs($this->operator)->get(route('layout-areas.index'));

        $response->assertOk()
            ->assertSee('会場エリアマスター')
            ->assertSee('北川駐車場内')
            ->assertSee('長島駐車場（旧北川駐車場）')
            ->assertSee('旧キッズ広場')
            ->assertSee('無効')
            ->assertSee('2日程')
            ->assertSee('2配置')
            ->assertSee('🔒 使用中')
            ->assertSee('id="modal-layout-area-detail"', false)
            ->assertSee('id="modal-layout-area-form"', false)
            ->assertSee('id="btn-open-create-layout-area"', false)
            ->assertDontSee('zone_code');

        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'layout-areas.js') || str_contains($content, 'layout-areas-'),
            'layout-areas Vite script asset must be loaded'
        );
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
    }

    /**
     * 会場エリアのCRUD・display_order自動採番・一意制約バリデーションが機能する
     */
    public function test_crud_and_validation_work_properly(): void
    {
        $location = Location::factory()->create(['name' => 'S&S駐車場']);

        // 1. 新規登録（1件目: display_order = 10）
        $this->actingAs($this->operator)->post(route('layout-areas.store'), [
            'name' => 'S&S前駐車場内',
            'location_id' => $location->id,
            'notes' => '本陣・体験スペース用',
            'is_active' => '1',
        ])->assertRedirect(route('layout-areas.index'));

        $area1 = LayoutArea::where('name', 'S&S前駐車場内')->firstOrFail();
        $this->assertSame($location->id, $area1->location_id);
        $this->assertSame(10, $area1->display_order);
        $this->assertTrue($area1->is_active);

        // 2. 2件目登録（display_order = 20）
        $this->actingAs($this->operator)->postJson(route('layout-areas.store'), [
            'name' => 'メイン通りA列（東側歩道）',
            'is_active' => '1',
        ])->assertOk()->assertJson(['success' => true]);

        $area2 = LayoutArea::where('name', 'メイン通りA列（東側歩道）')->firstOrFail();
        $this->assertSame(20, $area2->display_order);

        // 3. 重複名登録は422で拒否
        $this->actingAs($this->operator)->postJson(route('layout-areas.store'), [
            'name' => 'S&S前駐車場内',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);

        // 4. 更新（display_order は変更されず維持され、他エリア名への変更は422）
        $this->actingAs($this->operator)->putJson(route('layout-areas.update', $area1), [
            'name' => 'メイン通りA列（東側歩道）',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);

        $this->actingAs($this->operator)->put(route('layout-areas.update', $area1), [
            'name' => 'S&S前特設エリア',
            'location_id' => '',
            'notes' => '更新済み備考',
            'is_active' => '0',
        ])->assertRedirect(route('layout-areas.index'));

        $fresh1 = $area1->fresh();
        $this->assertSame('S&S前特設エリア', $fresh1->name);
        $this->assertNull($fresh1->location_id);
        $this->assertSame('更新済み備考', $fresh1->notes);
        $this->assertSame(10, $fresh1->display_order);
        $this->assertFalse($fresh1->is_active);

        // 5. 未使用エリアの削除
        $this->actingAs($this->operator)->delete(route('layout-areas.destroy', $fresh1))
            ->assertRedirect(route('layout-areas.index'));

        $this->assertDatabaseMissing('layout_areas', ['id' => $area1->id]);
    }

    /**
     * 配置データで使用中のエリアは削除がブロックされ、データ保護されること
     */
    public function test_delete_is_blocked_when_referenced_by_layout_items(): void
    {
        $usedArea = LayoutArea::factory()->create(['name' => '使用中エリア']);
        $timetable = Timetable::factory()->create();
        $map = LayoutMap::create(['timetable_id' => $timetable->id, 'name' => '配置図']);

        LayoutItem::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $usedArea->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'A01',
            'status' => LayoutItemStatus::Confirmed,
        ]);

        // JSON 削除は 422
        $this->actingAs($this->operator)
            ->deleteJson(route('layout-areas.destroy', $usedArea))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // フォーム削除はエラー付きリダイレクト
        $this->actingAs($this->operator)
            ->delete(route('layout-areas.destroy', $usedArea))
            ->assertRedirect(route('layout-areas.index'))
            ->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('layout_areas', ['id' => $usedArea->id]);
    }

    /**
     * 並び替え（reorder）が単一UPDATEクエリで表示順を更新し、不正IDを拒否すること
     */
    public function test_reorder_updates_display_order_with_single_query_and_validates_ids(): void
    {
        $a1 = LayoutArea::factory()->create(['name' => 'エリア1', 'display_order' => 10]);
        $a2 = LayoutArea::factory()->create(['name' => 'エリア2', 'display_order' => 20]);
        $a3 = LayoutArea::factory()->create(['name' => 'エリア3', 'display_order' => 30]);

        $this->actingAs($this->operator)
            ->postJson(route('layout-areas.reorder'), ['layout_area_ids' => [$a1->id, 999999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['layout_area_ids.1']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $res = $this->actingAs($this->operator)
            ->postJson(route('layout-areas.reorder'), ['layout_area_ids' => [$a3->id, $a1->id, $a2->id]]);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $res->assertOk()->assertJson(['success' => true]);

        $updateCount = collect($queries)
            ->filter(fn (array $q): bool => str_starts_with(strtoupper(trim($q['query'])), 'UPDATE'))
            ->count();
        $this->assertSame(1, $updateCount);

        $this->assertSame(10, $a3->fresh()->display_order);
        $this->assertSame(20, $a1->fresh()->display_order);
        $this->assertSame(30, $a2->fresh()->display_order);
    }

    /**
     * 権限検証: viewerは一覧閲覧のみ可で登録・更新・削除・並び替えは403
     */
    public function test_authorization_restricts_mutations_to_operator_and_admin(): void
    {
        $area = LayoutArea::factory()->create(['name' => '権限テストエリア']);

        $this->actingAs($this->viewer)->get(route('layout-areas.index'))->assertOk();
        $this->actingAs($this->viewer)->postJson(route('layout-areas.store'), ['name' => '不正登録'])->assertStatus(403);
        $this->actingAs($this->viewer)->putJson(route('layout-areas.update', $area), ['name' => '不正更新'])->assertStatus(403);
        $this->actingAs($this->viewer)->deleteJson(route('layout-areas.destroy', $area))->assertStatus(403);
        $this->actingAs($this->viewer)->postJson(route('layout-areas.reorder'), ['layout_area_ids' => [$area->id]])->assertStatus(403);

        $this->actingAs($this->operator)->postJson(route('layout-areas.store'), ['name' => '正規登録'])->assertOk();
    }

    /**
     * 複数運行日での完全分離・白紙運行日での全有効エリア表示・無効エリアの制御検証
     */
    public function test_multi_timetable_isolation_blank_timetable_and_inactive_area_behavior(): void
    {
        $areaActive1 = LayoutArea::factory()->create(['name' => 'メイン通りA列', 'display_order' => 10, 'is_active' => true]);
        $areaActive2 = LayoutArea::factory()->create(['name' => '北川駐車場内', 'display_order' => 20, 'is_active' => true]);
        $areaInactiveUsedInA = LayoutArea::factory()->inactive()->create(['name' => '旧駅南口エリア', 'display_order' => 30]);
        $areaInactiveUnused = LayoutArea::factory()->inactive()->create(['name' => '廃止済み倉庫前', 'display_order' => 40]);

        $ttA = Timetable::factory()->create(['target_date' => '2026-10-17', 'name' => '1日目']);
        $ttB = Timetable::factory()->create(['target_date' => '2026-10-18', 'name' => '2日目']);
        $ttBlank = Timetable::factory()->create(['target_date' => '2026-10-19', 'name' => '新規白紙日']);

        $mapA = LayoutMap::create(['timetable_id' => $ttA->id, 'name' => '1日目配置図']);
        $mapB = LayoutMap::create(['timetable_id' => $ttB->id, 'name' => '2日目配置図']);

        $itemA1 = LayoutItem::create([
            'layout_map_id' => $mapA->id,
            'layout_area_id' => $areaActive1->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'A-01',
            'name' => '1日目だけの出店',
            'status' => LayoutItemStatus::Confirmed,
        ]);
        $itemAOld = LayoutItem::create([
            'layout_map_id' => $mapA->id,
            'layout_area_id' => $areaInactiveUsedInA->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'OLD-01',
            'name' => '旧エリア既存出店',
            'status' => LayoutItemStatus::Confirmed,
        ]);
        $itemB1 = LayoutItem::create([
            'layout_map_id' => $mapB->id,
            'layout_area_id' => $areaActive1->id,
            'item_type' => LayoutItemType::Booth,
            'code' => 'B-01',
            'name' => '2日目だけの出店',
            'status' => LayoutItemStatus::Confirmed,
        ]);

        // 1. 運行日Aの配置計画・配置一覧に他運行日Bのアイテムが混入しないこと（集計値も運行日Aの件数のみ）
        $layoutIndexA = $this->actingAs($this->operator)->get(route('timetables.layouts.index', $ttA));
        $layoutIndexA->assertOk()
            ->assertSee('1日目だけの出店')
            ->assertSee('旧エリア既存出店')
            ->assertSee('旧駅南口エリア')
            ->assertDontSee('2日目だけの出店')
            ->assertDontSee('廃止済み倉庫前');

        $boothsA = $this->actingAs($this->operator)->get(route('timetables.layouts.booths', $ttA));
        $boothsA->assertOk()
            ->assertSee('1日目だけの出店')
            ->assertSee('旧エリア既存出店')
            ->assertSee('メイン通りA列 (1)')
            ->assertSee('旧駅南口エリア（無効） (1)')
            ->assertDontSee('2日目だけの出店')
            ->assertDontSee('メイン通りA列 (2)')
            ->assertDontSee('廃止済み倉庫前');

        // 2. 新規白紙運行日では全有効マスターエリアが即使え、未使用の無効エリアは除外されること
        $blankBooths = $this->actingAs($this->operator)->get(route('timetables.layouts.booths', $ttBlank));
        $blankBooths->assertOk()
            ->assertSee('メイン通りA列 (0)')
            ->assertSee('北川駐車場内 (0)')
            ->assertDontSee('旧駅南口エリア')
            ->assertDontSee('廃止済み倉庫前');

        // 3. 新規登録時は無効エリアの指定が422で拒否されること
        $this->actingAs($this->operator)->post(route('timetables.layouts.booths.store', $ttBlank), [
            'item_type' => 'booth',
            'code' => 'NG-01',
            'layout_area_id' => $areaInactiveUnused->id,
            'name' => '無効エリア新規配置',
            'category' => BoothCategory::GozaCookedFood->value,
            'booth_type' => BoothType::Tent->value,
            'status' => 'confirmed',
        ])->assertSessionHasErrors(['layout_area_id']);

        $this->assertDatabaseMissing('layout_items', ['code' => 'NG-01']);

        // 4. 無効エリアに配置済みの既存アイテムは自身の既存エリアのまま更新可能（他無効エリアへの変更は422）
        $this->actingAs($this->operator)->put(route('timetables.layouts.booths.update', [$ttA, $itemAOld]), [
            'item_type' => 'booth',
            'code' => 'OLD-01',
            'layout_area_id' => $areaInactiveUnused->id,
            'name' => '他無効エリアへ不正変更',
            'status' => 'confirmed',
        ])->assertSessionHasErrors(['layout_area_id']);

        $this->actingAs($this->operator)->put(route('timetables.layouts.booths.update', [$ttA, $itemAOld]), [
            'item_type' => 'booth',
            'code' => 'OLD-01',
            'layout_area_id' => $areaInactiveUsedInA->id,
            'name' => '旧エリア既存出店（更新済）',
            'status' => 'confirmed',
        ])->assertRedirect();

        $this->assertSame('旧エリア既存出店（更新済）', $itemAOld->fresh()->name);
    }
}
