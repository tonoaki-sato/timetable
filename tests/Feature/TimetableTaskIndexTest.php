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

        // アクションボタン
        $response->assertSee(route('timetables.staff-timeline', $this->timetable));
        $response->assertSee('スタッフ稼働タイムライン');
        $response->assertSee(route('timetables.print-assignments', $this->timetable));
        $response->assertSee('人員配置表印刷');
        $response->assertSee(route('timetable-tasks.create', ['timetable_id' => $this->timetable->id]));
        $response->assertSee('作業タスクを追加');

        // タスク一覧ヘッダーのサマリー統計
        $response->assertSee('登録タスク:');
        $response->assertSee('人員充足:');

        // タスク内容が表示されていること
        $response->assertSee('交差点誘導');
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

