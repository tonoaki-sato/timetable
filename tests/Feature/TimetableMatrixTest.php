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

        // ヘッダー1行化・リアルタイム時計・赤ボーダーライン・PDFダウンロードボタン
        $response->assertSee('matrix-live-clock');
        $response->assertSee('matrix-current-time-line');
        $response->assertSee('A4横 人員配置表PDF');
        $response->assertDontSee('<span>担当班管理</span>', false);

        // クイックアサインモーダルの統合ヘッダー要素が含まれていること
        $response->assertSee('quickAssignModal');
        $response->assertSee('quickAssignHeaderStatus');
        $response->assertSee('quickAssignHeaderPoolBtn');

        // ステップガイド、人員配置状況サマリー、表示班プリセットボタンが削除されていること
        $response->assertDontSee('まつり運行準備ステップガイド');
        $response->assertDontSee('人員配置状況サマリー');
        $response->assertDontSee('全表示');
        $response->assertDontSee('主要5班（PDF準拠）');
        $response->assertDontSee('マイ担当班');
    }

    /**
     * クイックアサインモーダル用のタスク・スタッフ候補JSONデータが取得できること
     */
    public function test_assign_modal_data_endpoint_returns_task_and_prioritized_candidates(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '1日目 本祭運行',
            'target_date' => '2026-10-17',
            'status' => 'active',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => 'コーン設置タスク',
            'location' => '北口ゲート',
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'required_people_count' => 3,
        ]);

        $staff = Staff::factory()->create([
            'name' => '候補スタッフA',
            'kana' => 'コウホスタッフエー',
            'affiliation' => 'カラーコーン班',
            'is_active' => true,
        ]);

        $response = $this->getJson(route('timetable-tasks.assign-modal-data', $task));
        $response->assertOk()
            ->assertJsonPath('task.id', $task->id)
            ->assertJsonPath('task.title', 'コーン設置タスク')
            ->assertJsonPath('task.lane', 'カラーコーン')
            ->assertJsonPath('task.location', '北口ゲート')
            ->assertJsonPath('task.required_people_count', 3)
            ->assertJsonPath('task.shortage_count', 3);

        $candidates = $response->json('candidate_staff');
        $this->assertNotEmpty($candidates);
        $this->assertSame($staff->id, $candidates[0]['id']);
        $this->assertTrue($candidates[0]['is_same_lane']);
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
     * 随時追加された新しい担当班がグリッドに動的に反映されること
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
        $response->assertSee('表示班:');
        $response->assertDontSee('全表示');
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
            'target_date' => now('Asia/Tokyo')->toDateString(),
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
        $response->assertDontSee('現場人員充足状況');
        $response->assertDontSee('現場機材調整要請');
    }

    /**
     * 計画メニューが正常に描画されること
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

        // 計画グループ
        $response->assertSee('計画');

        // セクション1: 進行表・当日運行
        $response->assertSee('進行表・当日運行');
        $response->assertSee('本祭1日目');
        $response->assertSee('運行中');
        $response->assertSee(route('timetable.index', ['timetable_id' => $activeTimetable->id]));
        $response->assertSee('本祭2日目');
        $response->assertSee(route('timetable.index', ['timetable_id' => $draftTimetable->id]));

        // 運行完了（completed）はプルダウンメニューに表示されないこと
        $response->assertDontSee(route('timetable.index', ['timetable_id' => $completedTimetable->id]));

        // セクション2: 編成・業務計画
        $response->assertSee('編成・業務計画');
        $response->assertSee('運行日程管理');
        $response->assertSee('搬出入計画');
        $response->assertSee(route('timetables.index'));
        $response->assertSee(route('logistics.index'));

        // セクション3: 日程・スケジュール
        $response->assertSee('日程・スケジュール');
        $response->assertSee('総合カレンダー');
        $response->assertSee(route('calendar.index'));
        $response->assertSee('スケジュール管理');
        $response->assertSee(route('schedules.index'));
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

    /**
     * マスター管理、帳票・印刷、システム管理メニューが正しく整理・表示されること
     */
    public function test_navigation_displays_resource_and_system_menu_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // ナビゲーションバーから独立した「本部状況」ボタン、「その他」、「アカウント情報」が削除されていること
        $response->assertDontSee('<span>本部状況</span>', false);
        $response->assertDontSee('その他');
        $response->assertDontSee('アカウント情報');

        // マスター管理グループ
        $response->assertSee('マスター管理');
        $response->assertSee('人員マスター');
        $response->assertSee('スタッフ名簿');
        $response->assertSee(route('staff.index'));
        $response->assertSee('資材・拠点マスター');
        $response->assertSee('資材管理');
        $response->assertSee(route('equipments.index'));
        $response->assertSee('拠点・駐車場管理');
        $response->assertSee(route('locations.index'));
        $response->assertSee('拠点タイプ管理');
        $response->assertSee(route('location-types.index'));

        // 帳票・印刷グループ
        $response->assertSee('帳票・印刷');
        $response->assertSee('名札一括印刷');
        $response->assertSee(route('staff.print-cards'));
        $response->assertSee('搬出入指示書印刷');
        $response->assertSee(route('logistics.print'));
        $response->assertSee('スタッフCSV出力');
        $response->assertSee(route('staff.export-csv'));

        // システム管理グループ（マニュアル・資料 ＆ システム設定）
        $response->assertSee('システム・ガイド');
        $response->assertSee('ガイド・広報資料');
        $response->assertSee('操作マニュアル');
        $response->assertSee(route('manual.index'));
        $response->assertSee('幹事会プレゼン');
        $response->assertSee(route('presentation.index'));
        $response->assertSee('ユーザー管理');
        $response->assertSee(route('users.index'));
        $response->assertSee('セルフ登録フォーム');
        $response->assertSee(route('staff.join'));

        $response->assertSee('システム設定');
        $response->assertSee('Webセルフ登録受付');

        // 右上のプロフィールアイコンリンクは維持されていること
        $response->assertSee(route('profile'));
    }

    /**
     * 道路予定がない場合、道路列が非表示になり、まつり列のみが表示されること
     */
    public function test_matrix_hides_road_column_when_road_periods_is_empty(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '道路予定なし運行日',
            'road_periods' => null,
            'festival_periods' => [
                ['start_time' => '11:00', 'end_time' => '17:00', 'label' => '開催中', 'status' => 'active'],
            ],
            'status' => 'active',
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));
        $response->assertStatus(200);

        // まつり列ヘッダーが表示され、道路列ヘッダーが表示されないこと
        $response->assertSee('まつり');
        $response->assertSee('開催中');
        $response->assertDontSee('<th scope="col" class="sticky left-[130px] z-40 bg-slate-200', false);
        $response->assertSee('sticky left-[130px] z-40 bg-slate-200 border-r-2 border-b-2 border-slate-800 p-2 text-center font-bold w-[75px]', false);
    }

    /**
     * まつり予定がない場合、まつり列が非表示になり、道路列のみが表示されること
     */
    public function test_matrix_hides_festival_column_when_festival_periods_is_empty(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => 'まつり予定なし運行日',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
            ],
            'festival_periods' => null,
            'status' => 'active',
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));
        $response->assertStatus(200);

        // 道路列ヘッダーが表示され、まつり列ヘッダーが表示されないこと
        $response->assertSee('道路');
        $response->assertSee('通行止め');
        $response->assertDontSee('<th scope="col" class="sticky left-[205px]', false);
        $response->assertDontSee('sticky left-[130px] z-40 bg-slate-200 border-r-2 border-b-2 border-slate-800 p-2 text-center font-bold w-[75px]', false);
        $response->assertSee('sticky left-[130px] z-40 bg-slate-200 border-r-2 border-b-2 border-slate-800 shadow-xs print:border-r-2 print:border-black p-2 text-center font-bold w-[75px]', false);
    }

    /**
     * 道路・まつり両方の予定がない場合、両方の列が非表示になること
     */
    public function test_matrix_hides_both_columns_when_neither_periods_exist(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '準備日（規制・まつり共になし）',
            'road_periods' => null,
            'festival_periods' => null,
            'status' => 'active',
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));
        $response->assertStatus(200);

        // 道路列・まつり列ヘッダーが共に描画されないこと
        $response->assertDontSee('sticky left-[130px]', false);
        $response->assertDontSee('sticky left-[205px]', false);
        // 時間列の右境界線が太線（border-r-2）になっていること
        $response->assertSee('sticky left-[65px] z-40 bg-slate-200 border-r-2 border-b-2 border-slate-800 shadow-xs', false);
    }

    /**
     * 道路・まつり両方の予定がある場合、両方の列が表示されること
     */
    public function test_matrix_shows_both_columns_when_both_periods_exist(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '本祭運行日（両方あり）',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
            ],
            'festival_periods' => [
                ['start_time' => '11:00', 'end_time' => '17:00', 'label' => '開催中', 'status' => 'active'],
            ],
            'status' => 'active',
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));
        $response->assertStatus(200);

        // 道路列（left-[130px]）とまつり列（left-[205px]）が共に描画されること
        $response->assertSee('sticky left-[130px] z-40 bg-slate-200 border-r border-b-2 border-slate-800', false);
        $response->assertSee('sticky left-[205px] z-40 bg-slate-200 border-r-2 border-b-2 border-slate-800', false);
        $response->assertSee('通行止め');
        $response->assertSee('開催中');
    }
}
