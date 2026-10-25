<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableShowViewTest extends TestCase
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
            'name' => '1日目 本祭運行',
            'target_date' => '2026-10-17',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'status' => 'active',
            'description' => 'パレード及びメインステージ周辺の運行計画',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '警備班',
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $lane->id,
            'title' => '交差点誘導',
            'start_time' => '08:30:00',
            'end_time' => '12:00:00',
            'required_people_count' => 2,
        ]);
    }

    /**
     * タイムテーブル詳細画面が新デザインのヘッダーで正常に表示されること
     */
    public function test_show_view_renders_new_header_design(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.show', $this->timetable));

        $response->assertStatus(200);

        // パンくずリスト
        $response->assertSee('タイムテーブル一覧');
        $response->assertSee(route('timetables.index'));

        // タイトル & 説明（タイトル横の日付・時間帯バッジは削除されていること）
        $response->assertSee('1日目 本祭運行');
        $response->assertDontSee('08:00 〜 18:00');
        $response->assertSee('パレード及びメインステージ周辺の運行計画');

        // 残すアクションボタンが表示されていること
        $response->assertSee('タイムテーブル一覧に戻る');
        $response->assertSee(route('timetables.staff-timeline', $this->timetable));
        $response->assertSee('スタッフ稼働タイムライン');
        $response->assertSee(route('timetables.print-assignments', $this->timetable));
        $response->assertSee('人員配置表印刷');
        $response->assertSee(route('timetable-tasks.create', ['timetable_id' => $this->timetable->id]));
        $response->assertSee('作業タスクを追加');

        // タスク一覧ヘッダーのサマリー統計
        $response->assertSee('登録タスク:');
        $response->assertSee('人員充足:');
    }

    /**
     * 指定された削除対象のボタン・モーダルが表示されないこと
     */
    public function test_removed_buttons_and_modals_are_not_present(): void
    {
        $this->actingAs($this->operator);

        // 別の日程を作成（他日程アサインコピーの条件を満たす状況）
        Timetable::factory()->create([
            'name' => '前日 設営運行',
            'target_date' => '2026-10-16',
        ]);

        $response = $this->get(route('timetables.show', $this->timetable));

        $response->assertStatus(200);

        // 1. 他日程からアサインコピー
        $response->assertDontSee('他日程からアサインコピー');
        $response->assertDontSee('copyAssignmentsModal');
        $response->assertDontSee('open-copy-modal');

        // 2. 担当班（レーン）管理 ボタン
        $response->assertDontSee(route('timetables.lanes.index', $this->timetable));

        // 3. 全体マトリクス ボタン（showページのアクションボタンから削除されていること）
        $response->assertDontSee('全体マトリクス');

        // 4. 運行枠編集 ボタン
        $response->assertDontSee(route('timetables.edit', $this->timetable));
        $response->assertDontSee('運行枠編集');

        // 5. 運行枠削除 ボタン
        $response->assertDontSee("タイムテーブル「{$this->timetable->name}」を削除しますか？");
    }

    /**
     * 他日程からアサインコピーのルートが削除されていること
     */
    public function test_copy_assignments_from_route_is_removed(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post("/timetables/{$this->timetable->id}/copy-assignments-from", [
            'source_timetable_id' => 999,
        ]);

        $response->assertStatus(404);
    }
}
