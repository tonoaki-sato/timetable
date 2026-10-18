<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('timetable.index'))->assertRedirect(route('login'));
    }

    /**
     * 全体マトリクス画面が正常に表示されること
     */
    public function test_matrix_displays_selected_timetable_and_lanes(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '1日目 本祭運行',
            'target_date' => '2026-10-17',
            'status' => 'active',
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane' => 'カラーコーン班',
            'title' => '中央通りコーン設置',
            'required_people_count' => 2,
            'is_at_least' => true,
        ]);

        $staff = Staff::factory()->create(['name' => '現場 健太']);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
        ]);

        $response = $this->get(route('timetable.index'));
        $response->assertStatus(200);
        $response->assertSee('運行タイムテーブル全体マトリクス');
        $response->assertSee('1日目 本祭運行');
        $response->assertSee('カラーコーン班');
        $response->assertSee('中央通りコーン設置');
        $response->assertSee('現場 健太');
    }

    /**
     * タイムテーブルID指定による運行日の切り替えができること
     */
    public function test_matrix_allows_switching_timetables(): void
    {
        $this->actingAs($this->operator);

        $timetable1 = Timetable::factory()->create([
            'name' => '1日目 本祭',
            'target_date' => '2026-10-17',
        ]);
        $timetable2 = Timetable::factory()->create([
            'name' => '2日目 撤収',
            'target_date' => '2026-10-18',
        ]);

        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $timetable1->id,
            'title' => 'パレード誘導',
        ]);
        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $timetable2->id,
            'title' => 'パイプ椅子撤収',
        ]);

        $response1 = $this->get(route('timetable.index', ['timetable_id' => $timetable1->id]));
        $response1->assertStatus(200);
        $response1->assertSee('パレード誘導');
        $response1->assertDontSee('パイプ椅子撤収');

        $response2 = $this->get(route('timetable.index', ['timetable_id' => $timetable2->id]));
        $response2->assertStatus(200);
        $response2->assertSee('パイプ椅子撤収');
        $response2->assertDontSee('パレード誘導');
    }

    /**
     * ダッシュボード画面が実DBタスクと連携して正常表示されること
     */
    public function test_dashboard_renders_with_real_database_tasks(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '本祭運行',
            'status' => 'active',
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'title' => '重要ゲート封鎖',
            'is_critical' => true,
            'status' => 'in_progress',
            'required_people_count' => 4,
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('本部運行総合ダッシュボード');
        $response->assertSee('重要ゲート封鎖');
    }
}
