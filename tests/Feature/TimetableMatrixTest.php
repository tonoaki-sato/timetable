<?php

namespace Tests\Feature;

use App\Models\Lane;
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
     * タイムテーブル画面（PDF帳票グリッド形式）が正常に表示されること
     */
    public function test_matrix_displays_selected_timetable_and_lanes(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '1日目 本祭運行',
            'target_date' => '2026-10-17',
            'start_time' => '07:00:00',
            'end_time' => '19:30:00',
            'status' => 'active',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'display_order' => 10,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '中央通りコーン設置',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
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
        $response->assertSee('タイムテーブル');
        $response->assertSee('1日目 本祭運行');
        $response->assertSee('カラーコーン');
        $response->assertSee('中央通りコーン設置');
        $response->assertSee('【2名以上】');
        $response->assertSee('現場 健太');
        // 30分刻みスロットと道路・まつり判定
        $response->assertSee('07:00');
        $response->assertSee('10:00');
        $response->assertSee('通行止め');
        $response->assertSee('開催中');
    }

    /**
     * タスク未登録でも運行日作成時点で開始〜終了時刻の30分刻みスケルトンが即座に表示されること
     */
    public function test_matrix_renders_skeleton_grid_immediately_when_no_tasks_exist(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '新規作成運行日',
            'target_date' => '2026-10-20',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'active',
        ]);

        // タスクは0件
        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));
        $response->assertStatus(200);
        $response->assertSee('新規作成運行日');
        // スロットが08:00〜12:00で即時生成されていること
        $response->assertSee('08:00');
        $response->assertSee('08:30');
        $response->assertSee('11:30');
        $response->assertSee('12:00');
        // 空枠ハイフンが表示されていること
        $response->assertSee('-');
    }

    /**
     * 随時追加された新しい担当班（レーン）がグリッドに動的に反映されること
     */
    public function test_matrix_supports_dynamically_added_lanes(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '随時追加テスト運行日',
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
            'status' => 'active',
        ]);

        // 新規レーンを随時追加
        $newLane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'ドローン空撮班',
            'display_order' => 999,
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));
        $response->assertStatus(200);
        $response->assertSee('ドローン空撮班');
        $response->assertSee('全表示');
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

    /**
     * まつり運行メニューが案A（目的別2セクション構造）で正常に描画されること
     */
    public function test_navigation_displays_redesigned_operation_menu_sections(): void
    {
        $this->actingAs($this->operator);

        $activeTimetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'target_date' => '2026-10-17',
            'status' => 'active',
        ]);
        $draftTimetable = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2026-10-18',
            'status' => 'draft',
        ]);
        $completedTimetable = Timetable::factory()->create([
            'name' => '前日準備完了分',
            'target_date' => '2026-10-16',
            'status' => 'completed',
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // まつり運行グループ
        $response->assertSee('まつり運行');

        // セクション1: 進行表（日程別）
        $response->assertSee('進行表（日程別）');
        $response->assertSee('本祭1日目');
        $response->assertSee('運行中');
        $response->assertSee(route('timetable.index', ['timetable_id' => $activeTimetable->id]));
        $response->assertSee('本祭2日目');
        $response->assertSee(route('timetable.index', ['timetable_id' => $draftTimetable->id]));

        // 運行完了（completed）はプルダウンメニューに表示されないこと
        $response->assertDontSee(route('timetable.index', ['timetable_id' => $completedTimetable->id]));

        // セクション2: 計画・編成・帳票出力
        $response->assertSee('計画・編成・帳票出力');
        $response->assertSee('運行計画・タスク台帳');
        $response->assertSee('担当班（レーン）管理');
        $response->assertSee('人員配置表の印刷');
        $response->assertSee(route('timetables.print-assignments', $activeTimetable));

        // 各ルートへのリンクが含まれていること
        $response->assertSee(route('timetables.index'));
        $response->assertSee(route('lanes.index'));
    }

    /**
     * /lanes ショートカットがアクティブ運行日へ正常にリダイレクトすること
     */
    public function test_lanes_shortcut_redirects_to_active_or_first_timetable_lanes(): void
    {
        $this->actingAs($this->operator);

        // 運行日が存在する場合
        $timetable = Timetable::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->get(route('lanes.index'));
        $response->assertRedirect(route('timetables.lanes.index', $timetable));
    }

    /**
     * 運行日が0件の場合、/lanes は運行日管理へリダイレクトすること
     */
    public function test_lanes_shortcut_redirects_to_timetables_index_when_no_timetables(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('lanes.index'));
        $response->assertRedirect(route('timetables.index'));
    }
}
