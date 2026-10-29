<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableManagementTest extends TestCase
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
     * 未認証ユーザーはログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('timetables.index'))->assertRedirect(route('login'));
        $this->get(route('timetables.create'))->assertRedirect(route('login'));
    }

    /**
     * 停止中ユーザーはアクセス拒否（403）されること
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $this->actingAs($inactive)->get(route('timetables.index'))->assertStatus(403);
    }

    /**
     * 一覧画面が表示され、ステータス絞り込みができること
     */
    public function test_index_displays_timetables_and_filters(): void
    {
        $this->actingAs($this->operator);

        $timetableA = Timetable::factory()->create([
            'name' => '1日目 本祭運行',
            'status' => 'active',
            'target_date' => '2026-10-17',
        ]);
        $timetableB = Timetable::factory()->create([
            'name' => '前日 設営運行',
            'status' => 'draft',
            'target_date' => '2026-10-16',
        ]);

        $response = $this->get(route('timetables.index'));
        $response->assertStatus(200);
        $response->assertSee('1日目 本祭運行');
        $response->assertSee('前日 設営運行');
        $response->assertSee(route('timetable.index', ['timetable_id' => $timetableA->id]));
        $response->assertSee(route('timetables.lanes.index', $timetableA));
        $response->assertSee('タイムテーブル');
        $response->assertSee('担当班管理');

        // active のみ絞り込み
        $filterResponse = $this->get(route('timetables.index', ['status' => 'active']));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('1日目 本祭運行');
        $filterResponse->assertDontSee(route('timetables.show', $timetableB));
        $filterResponse->assertViewHas('timetables', function ($timetables) use ($timetableA, $timetableB) {
            return $timetables->contains($timetableA) && ! $timetables->contains($timetableB);
        });
    }

    /**
     * 新規タイムテーブルを登録できること
     */
    public function test_can_create_timetable(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-17',
            'name' => '1日目 (Day 1) 本祭運行',
            'description' => 'パレード及びメインステージ周辺の交通規制含む',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'active',
        ]);

        $timetable = Timetable::where('name', '1日目 (Day 1) 本祭運行')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertDatabaseHas('timetables', [
            'id' => $timetable->id,
            'name' => '1日目 (Day 1) 本祭運行',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);
        $this->assertCount(count(Lane::DEFAULT_LANES), $timetable->lanes);
    }

    /**
     * create_default_lanes を false にすると初期班が自動生成されないこと
     */
    public function test_can_create_timetable_without_default_lanes(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-18',
            'name' => '2日目 独自班編成',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'active',
            'create_default_lanes' => 0,
        ]);

        $timetable = Timetable::where('name', '2日目 独自班編成')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertCount(0, $timetable->lanes);
    }

    /**
     * 新規作成画面にゆとりあるコンテナ幅、ステップガイド、およびデフォルト班自動生成のhidden inputが存在すること
     */
    public function test_create_view_displays_workflow_steps_with_expanded_width_and_hidden_input(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.create'));
        $response->assertStatus(200);
        $response->assertSee('max-w-5xl');
        $response->assertSee('まつり運行準備ステップガイド');
        $response->assertSee('<input type="hidden" name="create_default_lanes" value="0">', false);
        $response->assertSee('まつり標準10班（カラーコーン・給水・衛生・巡回・キッズ村など）を自動生成する（推奨）');
    }

    /**
     * チェックを外して文字列 '0' が送信された場合に標準10班が作成されないこと
     */
    public function test_can_create_timetable_without_default_lanes_with_string_zero(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-19',
            'name' => '3日目 ゼロ班編成',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'active',
            'create_default_lanes' => '0',
        ]);

        $timetable = Timetable::where('name', '3日目 ゼロ班編成')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertCount(0, $timetable->lanes);
    }

    /**
     * 閲覧者ロール（viewer）は登録・編集・削除が禁止されること
     */
    public function test_viewer_cannot_create_or_edit_or_delete_timetable(): void
    {
        $this->actingAs($this->viewer);

        $timetable = Timetable::factory()->create();

        $this->get(route('timetables.create'))->assertStatus(403);
        $this->post(route('timetables.store'), [
            'target_date' => '2026-10-25',
            'name' => 'テスト運行',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'draft',
        ])->assertStatus(403);

        $this->get(route('timetables.edit', $timetable))->assertStatus(403);
        $this->put(route('timetables.update', $timetable), [
            'target_date' => $timetable->target_date->format('Y-m-d'),
            'name' => '改変運行',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'draft',
        ])->assertStatus(403);

        $this->delete(route('timetables.destroy', $timetable))->assertStatus(403);

        // 詳細閲覧は許可されること
        $this->get(route('timetables.show', $timetable))->assertStatus(200);
    }

    /**
     * タイムテーブル情報を更新できること
     */
    public function test_can_update_timetable(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '更新前運行',
            'status' => 'draft',
        ]);

        $response = $this->put(route('timetables.update', $timetable), [
            'target_date' => $timetable->target_date->format('Y-m-d'),
            'name' => '更新後運行名称',
            'description' => '更新された説明文',
            'start_time' => '07:30',
            'end_time' => '19:00',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('timetables.show', $timetable));
        $this->assertDatabaseHas('timetables', [
            'id' => $timetable->id,
            'name' => '更新後運行名称',
            'status' => 'active',
        ]);
    }

    /**
     * タイムテーブルを削除できること
     */
    public function test_can_delete_timetable(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => '削除対象運行']);

        $response = $this->delete(route('timetables.destroy', $timetable));
        $response->assertRedirect(route('timetables.index'));

        $this->assertDatabaseMissing('timetables', ['id' => $timetable->id]);
    }
}
