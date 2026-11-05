<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTaskIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable;

    private Lane $lane;

    private TimetableTask $task;

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

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '警備班',
        ]);

        $this->task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '交差点誘導',
            'content' => '北側交差点での歩行者誘導および迂回案内',
            'start_time' => '08:30:00',
            'end_time' => '12:00:00',
            'required_people_count' => 2,
            'is_critical' => true,
        ]);
    }

    /**
     * 作業タスク一覧画面が正常に表示されること
     */
    public function test_tasks_index_view_renders_tasks_and_actions(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.tasks.index', $this->timetable));

        $response->assertStatus(200);

        // パンくずリスト
        $response->assertSee('タイムテーブル一覧');
        $response->assertSee(route('timetables.index'));
        $response->assertSee($this->timetable->name);
        $response->assertSee(route('timetables.show', $this->timetable));

        // ヘッダーアクションボタン: 人員配置表印刷は表示され、全般の「作業タスクを追加」リンクは削除されていること
        $response->assertSee(route('timetables.print-assignments', $this->timetable));
        $response->assertSee('人員配置表印刷');
        $response->assertDontSee('作業タスクを追加');

        // 班アコーディオンヘッダー: タスク追加モーダルトリガーが表示され、旧一括タスク編集リンクは表示されないこと
        $response->assertSee('data-action="open-task-create"', false);
        $response->assertSee('タスク追加');
        $response->assertDontSee('>タスク編集<', false);

        // ヘッダーのスタッフ稼働タイムラインリンクは削除されていること
        $response->assertDontSee('<span>スタッフ稼働タイムライン</span>', false);

        // タスク一覧ヘッダーのサマリー統計は削除されていること
        $response->assertDontSee('登録タスク:');
        $response->assertDontSee('人員充足:');

        // 班別アコーディオン構造と班名が表示されていること
        $response->assertSee('運行作業タスク一覧 (班別時系列)');
        $response->assertSee('すべて折りたたむ');
        $response->assertSee('警備班');

        // テーブルヘッダーの列構成（最優先、時間帯、作業名、作業内容、必要人員、操作）
        $response->assertSee('最優先');
        $response->assertSee('時間帯');
        $response->assertSee('作業名');
        $response->assertSee('作業内容');
        $response->assertSee('必要人員');
        $response->assertSee('操作');

        // 削除された列（場所、状態、充足状況）が含まれていないこと
        $response->assertDontSee('必要人員 & 充足状況');
        $response->assertDontSee('(充足)');
        $response->assertDontSee('(不足');
        $response->assertDontSee('<th class="px-3.5 py-2.5 whitespace-nowrap">場所</th>', false);
        $response->assertDontSee('<th class="px-3.5 py-2.5 whitespace-nowrap">状態</th>', false);

        // タスク情報（タイトル、詳細モーダル属性、作業内容、必要人員）が表示されていること
        $response->assertSee('交差点誘導');
        $response->assertSee('data-action="open-task-detail"', false);
        $response->assertSee('北側交差点での歩行者誘導および迂回案内');
        $response->assertSee('2名');

        // 作業名リンクの後ろの矢印（→）が削除されていること
        $response->assertDontSee('text-indigo-500 font-normal">→</span>', false);

        // 操作列には編集モーダル呼び出しボタンと削除ボタンが表示されること
        $response->assertDontSee('👥 人員管理');
        $response->assertDontSee('人員管理');
        $response->assertSee('data-action="open-task-edit"', false);
        $response->assertSee('編集');
        $response->assertSee('🗑️');
        $response->assertSee('削除');
    }

    /**
     * 班ごとにアコーディオンでグループ化され、各班内で時系列順に表示されること
     */
    public function test_tasks_are_grouped_by_lane_and_ordered_chronologically(): void
    {
        $this->actingAs($this->operator);

        $laneA = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '衛生班',
            'display_order' => 1,
        ]);

        $taskLater = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $laneA->id,
            'title' => '午後ゴミ回収',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'is_critical' => false,
        ]);

        $taskEarlier = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $laneA->id,
            'title' => '午前トイレ点検',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'is_critical' => true,
        ]);

        $laneEmpty = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => 'キッズ村班',
            'display_order' => 99,
        ]);

        $response = $this->get(route('timetables.tasks.index', $this->timetable));

        $response->assertStatus(200);

        // 班名と各タスクが表示されていること
        $response->assertSee('衛生班');
        $response->assertSee('午前トイレ点検');
        $response->assertSee('午後ゴミ回収');

        // タスクが時系列順に並んでいること
        $content = $response->getContent();
        $posEarlier = strpos($content, '午前トイレ点検');
        $posLater = strpos($content, '午後ゴミ回収');
        $this->assertTrue($posEarlier < $posLater, '午前トイレ点検 should appear before 午後ゴミ回収 in chronological order');

        // 空の班のアコーディオンに空状態メッセージが表示されること
        $response->assertSee('キッズ村班');
        $response->assertSee('この班に登録されている作業タスクはありません。');
        $response->assertDontSee('>タスク編集<', false);
    }

    /**
     * timetable-tasks.index ルートへのリクエストが適切に処理されること
     */
    public function test_timetable_tasks_resource_index_redirects_or_shows(): void
    {
        $this->actingAs($this->operator);

        // timetable_id 付き
        $responseWithId = $this->get(route('timetable-tasks.index', ['timetable_id' => $this->timetable->id]));
        $responseWithId->assertStatus(200);
        $responseWithId->assertSee('交差点誘導');

        // timetable_id なし（アクティブな運行日へリダイレクト）
        $responseWithoutId = $this->get(route('timetable-tasks.index'));
        $responseWithoutId->assertRedirect(route('timetables.tasks.index', $this->timetable));
    }
}
