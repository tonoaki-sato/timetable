<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaneMasterTest extends TestCase
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
     * 担当班マスター一覧画面が5列構成・専用Viteスクリプト・各モーダルDOMを含めて正常に表示されること
     */
    public function test_index_displays_master_table_without_usage_column_and_with_modal_dom(): void
    {
        $usedMaster = Lane::factory()->create([
            'name' => '本部総務班',
            'color' => 'indigo',
            'icon' => '🏛️',
            'description' => '全体統括・本部連絡',
            'is_active' => true,
        ]);
        Lane::factory()->inactive()->create([
            'name' => '旧夜間警備班',
            'is_active' => false,
        ]);

        $timetable = Timetable::factory()->create(['name' => '1日目 本祭']);
        TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $usedMaster->id,
        ]);

        $response = $this->actingAs($this->operator)->get(route('lanes.index'));

        $response->assertStatus(200);
        $response->assertSee('担当班マスター');
        $response->assertSee('本部総務班');
        $response->assertSee('旧夜間警備班');
        $response->assertSee('無効');
        $response->assertSee('🔒');
        $response->assertSee('使用中');

        // テーブルヘッダーは5列（利用状況列を直接表に置かない仕様）
        $response->assertSee('<th scope="col" class="px-4 py-2.5 whitespace-nowrap w-20">順</th>', false);
        $response->assertSee('<th scope="col" class="px-4 py-2.5 whitespace-nowrap">班名</th>', false);
        $response->assertSee('<th scope="col" class="px-4 py-2.5 whitespace-nowrap">バッジ</th>', false);
        $response->assertSee('<th scope="col" class="px-4 py-2.5">標準役割概要</th>', false);
        $response->assertSee('<th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>', false);
        $response->assertDontSee('利用状況</th>', false);

        // モーダルDOMとViteスクリプト検証
        $response->assertSee('id="modal-lane-detail"', false);
        $response->assertSee('id="modal-lane-form"', false);
        $response->assertSee('id="modal-lane-delete"', false);
        $response->assertSee('id="btn-open-create-lane"', false);
        $response->assertSee('lanes-', false);
    }

    /**
     * 担当班マスターを新規登録でき、同名登録はバリデーションエラーになること
     */
    public function test_store_creates_master_and_rejects_duplicate_name(): void
    {
        $response = $this->actingAs($this->operator)->postJson(route('lanes.store'), [
            'name' => '来場者案内班',
            'color' => 'emerald',
            'icon' => '🪧',
            'description' => '主要交差点でのパンフレット配布と道案内',
            'is_active' => true,
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('lanes', [
            'name' => '来場者案内班',
            'color' => 'emerald',
            'icon' => '🪧',
            'is_active' => true,
        ]);

        // 同名の二重登録は失敗すること
        $duplicate = $this->actingAs($this->operator)->postJson(route('lanes.store'), [
            'name' => '来場者案内班',
            'color' => 'amber',
        ]);
        $duplicate->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    /**
     * 担当班マスターの更新内容が、それを参照する全運行日の日別編成へ自動反映されること
     */
    public function test_update_master_propagates_to_referencing_timetable_lanes(): void
    {
        $master = Lane::factory()->create([
            'name' => '仮設衛生班',
            'color' => 'slate',
            'icon' => '🚻',
        ]);
        $timetableA = Timetable::factory()->create(['name' => 'Day1']);
        $timetableB = Timetable::factory()->create(['name' => 'Day2']);

        $tlA = TimetableLane::factory()->create(['timetable_id' => $timetableA->id, 'lane_id' => $master->id]);
        $tlB = TimetableLane::factory()->create(['timetable_id' => $timetableB->id, 'lane_id' => $master->id]);

        $response = $this->actingAs($this->operator)->putJson(route('lanes.update', $master), [
            'name' => '衛生・トイレ管理班',
            'color' => 'teal',
            'icon' => '🧼',
            'description' => '巡回補充および清掃',
            'is_active' => true,
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        $tlA->refresh();
        $tlB->refresh();
        $this->assertSame('衛生・トイレ管理班', $tlA->name);
        $this->assertSame('teal', $tlA->color);
        $this->assertSame('🧼', $tlA->icon);
        $this->assertSame('衛生・トイレ管理班', $tlB->name);
    }

    /**
     * 無効化したマスターは運行日側の追加候補に表示されず、追加リクエストも拒否されること
     */
    public function test_inactive_master_is_excluded_from_timetable_candidates_and_rejected_on_assign(): void
    {
        $activeMaster = Lane::factory()->create(['name' => '有効警備班', 'is_active' => true]);
        $inactiveMaster = Lane::factory()->inactive()->create(['name' => '廃止予定班']);
        $timetable = Timetable::factory()->create();

        $indexResponse = $this->actingAs($this->operator)->get(route('timetables.lanes.index', $timetable));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('有効警備班');
        $indexResponse->assertDontSee('廃止予定班');

        $storeSingle = $this->actingAs($this->operator)->postJson(route('timetables.lanes.store', $timetable), [
            'lane_id' => $inactiveMaster->id,
        ]);
        $storeSingle->assertStatus(422)->assertJsonValidationErrors(['lane_id']);

        $storeBulk = $this->actingAs($this->operator)->postJson(route('timetables.lanes.bulk-store', $timetable), [
            'lane_ids' => [$activeMaster->id, $inactiveMaster->id],
        ]);
        $storeBulk->assertStatus(422)->assertJsonValidationErrors(['lane_ids.1']);
    }

    /**
     * 使用中のマスター削除は422エラーで保護され、未使用マスターのみ削除できること
     */
    public function test_destroy_blocks_used_master_and_deletes_unused_master(): void
    {
        $usedMaster = Lane::factory()->create(['name' => '使用中班']);
        $unusedMaster = Lane::factory()->create(['name' => '未使用班']);
        $timetable = Timetable::factory()->create();
        TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $usedMaster->id,
        ]);

        // 使用中マスター削除は拒否されること
        $blockedResponse = $this->actingAs($this->operator)->deleteJson(route('lanes.destroy', $usedMaster));
        $blockedResponse->assertStatus(422)->assertJsonPath('success', false);
        $this->assertDatabaseHas('lanes', ['id' => $usedMaster->id]);

        // 未使用マスターは削除できること
        $okResponse = $this->actingAs($this->operator)->deleteJson(route('lanes.destroy', $unusedMaster));
        $okResponse->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseMissing('lanes', ['id' => $unusedMaster->id]);
    }

    /**
     * 担当班マスターの表示順を一括並び替えできること
     */
    public function test_reorder_updates_master_display_order(): void
    {
        $laneA = Lane::factory()->create(['display_order' => 10]);
        $laneB = Lane::factory()->create(['display_order' => 20]);

        $response = $this->actingAs($this->operator)->postJson(route('lanes.reorder'), [
            'lane_ids' => [$laneB->id, $laneA->id],
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertLessThan($laneA->fresh()->display_order, $laneB->fresh()->display_order);
    }

    /**
     * viewerロールは閲覧のみ可能で、登録・編集・削除・並び替えが403になること
     */
    public function test_viewer_can_view_index_but_cannot_mutate_masters(): void
    {
        $lane = Lane::factory()->create();

        $this->actingAs($this->viewer)->get(route('lanes.index'))->assertStatus(200);
        $this->actingAs($this->viewer)->postJson(route('lanes.store'), ['name' => 'NG班', 'color' => 'slate'])->assertStatus(403);
        $this->actingAs($this->viewer)->putJson(route('lanes.update', $lane), ['name' => 'NG変更', 'color' => 'slate'])->assertStatus(403);
        $this->actingAs($this->viewer)->deleteJson(route('lanes.destroy', $lane))->assertStatus(403);
        $this->actingAs($this->viewer)->postJson(route('lanes.reorder'), ['lane_ids' => [$lane->id]])->assertStatus(403);
    }
}
