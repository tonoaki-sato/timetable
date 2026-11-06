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

    private User $viewer;

    private Timetable $timetable;

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

        $this->timetable = Timetable::factory()->create([
            'name' => '1日目 本祭運行',
            'target_date' => '2026-10-17',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'status' => 'active',
            'description' => 'パレード及びメインステージ周辺の運行計画',
            'road_periods' => [
                [
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                    'label' => '車両通行止め',
                    'status' => 'closed',
                ],
            ],
            'festival_periods' => [
                [
                    'start_time' => '11:00',
                    'end_time' => '17:00',
                    'label' => '本祭開催中',
                    'status' => 'active',
                ],
            ],
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
     * タイムテーブル詳細画面で基本情報・規制時間帯・サマリーが正常に表示されること
     */
    public function test_show_view_renders_basic_information_and_summaries(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.show', $this->timetable));

        $response->assertStatus(200);

        // パンくずリスト & 戻るリンク
        $response->assertSee('運行情報一覧');
        $response->assertSee('運行情報一覧に戻る');
        $response->assertSee(route('timetables.index'));

        // タイトル & 説明
        $response->assertSee('1日目 本祭運行');
        $response->assertSee('パレード及びメインステージ周辺の運行計画');

        // 運行基本枠カード
        $response->assertSee('運行基本情報');
        $response->assertSee('2026年10月17日');
        $response->assertSee('08:00 〜 18:00');
        $response->assertSee('運行中');

        // カード内編集ボタン
        $response->assertSee(route('timetables.edit', $this->timetable));
        $response->assertSee('基本情報を編集');

        // ヘッダーから削除されたリンクが表示されていないこと
        $response->assertDontSee('マトリクス進行表');

        // 道路規制・まつり状況カード
        $response->assertSee('道路の状況（交通規制・通行止め）');
        $response->assertSee('車両通行止め');
        $response->assertSee('まつりの状況（イベント進行状況）');
        $response->assertSee('本祭開催中');

        // 担当班編成・タスクサマリーカード
        $response->assertSee('担当班（10レーン）編成状況');
        $response->assertSee('警備班');
        $response->assertSee('作業タスク・人員充足サマリー');
        $response->assertSee('登録タスク総数');

        // 削除ボタン
        $response->assertSee('このタイムテーブルを削除する');
    }

    /**
     * 閲覧者ロール（viewer）は編集・削除ボタンが表示されないこと
     */
    public function test_viewer_does_not_see_edit_or_delete_actions_on_show(): void
    {
        $this->actingAs($this->viewer);

        $response = $this->get(route('timetables.show', $this->timetable));

        $response->assertStatus(200);
        $response->assertDontSee(route('timetables.edit', $this->timetable));
        $response->assertDontSee('基本情報を編集');
        $response->assertDontSee('このタイムテーブルを削除する');
    }
}
