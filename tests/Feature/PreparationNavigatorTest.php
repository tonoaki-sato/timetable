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

class PreparationNavigatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * ダッシュボードからまつり運行準備ナビゲーターが除外されていること
     */
    public function test_dashboard_does_not_display_navigator(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('タイムテーブル作成から班編成・タスク登録・余剰スタッフ配置までの手順をナビゲートします。');
        $response->assertDontSee('現在の推奨アクション:');
        $response->assertDontSee('新規タイムテーブルを登録 (Step');
    }

    /**
     * グローバルナビゲーションメニューにまつり運行準備ナビゲーターへのリンクが存在すること
     */
    public function test_menu_contains_preparation_navigator_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('まつり運行準備ナビゲーター');
        $response->assertSee(route('preparation-navigator.index'));
    }

    /**
     * ステップガイドが「ログイン・運行状況確認」を含まず全4ステップで構成されていること
     */
    public function test_step_guide_displays_4_steps_without_login_step(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('まつり運行準備ステップガイド');
        $response->assertSee('Step 1: タイムテーブル作成');
        $response->assertSee('Step 2: 担当班・スタッフ編成');
        $response->assertSee('Step 3: 作業タスク登録');
        $response->assertSee('Step 4: 人員不足・過不足調整');
        $response->assertDontSee('ログイン・運行状況確認');
    }

    /**
     * タイムテーブル未登録時は Step 1 推奨が表示されること
     */
    public function test_shows_step_1_when_no_timetable_exists(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('まつり運行準備ナビゲーター');
        $response->assertSee('運行日（タイムテーブル）が未登録です');
        $response->assertSee('新規タイムテーブルを登録 (Step 1)');
        $response->assertSee(route('timetables.create'));
    }

    /**
     * タイムテーブルはあるが班長未設定の場合は Step 2 推奨が表示されること
     */
    public function test_shows_step_2_when_lanes_have_no_lead_staff(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('担当班の編成・班長・固定スタッフを登録しましょう');
        $response->assertSee(route('timetables.lanes.index', $timetable));
    }

    /**
     * 班長が設定されているがタスクが未登録の場合は Step 3 推奨が表示されること
     */
    public function test_shows_step_3_when_no_tasks_exist(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        $lead = Staff::factory()->create(['is_active' => true]);
        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => $lead->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('作業タスクを登録しましょう');
        $response->assertSee(route('timetable-tasks.create', ['timetable_id' => $timetable->id]));
    }

    /**
     * 人員不足タスクがある場合は Step 4 推奨が表示されること
     */
    public function test_shows_step_4_when_tasks_have_shortage(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        $lead = Staff::factory()->create(['is_active' => true]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => $lead->id,
        ]);

        // 必要人数2名で未配置のタスクを作成
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'required_people_count' => 2,
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('余剰スタッフを配置しましょう');
        $response->assertSee(route('timetable.index', ['timetable_id' => $timetable->id]));
    }

    /**
     * 全タスクの人員が充足している場合は完了案内が表示されること
     */
    public function test_shows_step_4_satisfied_when_all_tasks_filled(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);
        $lead = Staff::factory()->create(['is_active' => true]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'lead_staff_id' => $lead->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'required_people_count' => 1,
        ]);

        $staff = Staff::factory()->create(['is_active' => true]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('全タスクの人員配置が充足しています！');
    }

    /**
     * run ルートが推奨ステップの URL へリダイレクトすること
     */
    public function test_run_redirects_to_current_recommended_step(): void
    {
        // タイムテーブル未登録時は Step 1 (timetables.create) へリダイレクト
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.run'));

        $response->assertRedirect(route('timetables.create'));
    }

    /**
     * 全運行日のステップ進捗がタブ切り替えではなく同時に一覧として描画されること
     */
    public function test_displays_all_timetables_in_list_simultaneously(): void
    {
        $timetable1 = Timetable::factory()->create([
            'name' => '1日目 案内フェーズ',
            'status' => 'draft',
        ]);
        $timetable2 = Timetable::factory()->create([
            'name' => '2日目 本番フェーズ',
            'status' => 'draft',
        ]);

        $lead = Staff::factory()->create(['is_active' => true]);
        Lane::factory()->create([
            'timetable_id' => $timetable1->id,
            'name' => '案内班',
            'lead_staff_id' => $lead->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);

        // 両方の日程が同時に一覧内に描画されていること
        $response->assertSee('1日目 案内フェーズ');
        $response->assertSee('2日目 本番フェーズ');

        // timetable1 (班編成済・タスク未登録) の推奨アクションが表示されていること
        $response->assertSee(route('timetable-tasks.create', ['timetable_id' => $timetable1->id]));

        // timetable2 (班未編成) の推奨アクションが表示されていること
        $response->assertSee(route('timetables.lanes.index', $timetable2));

        // タブ切り替え案内ラベル（準備対象の運行日:）が存在せず、日程別一覧として表示されること
        $response->assertSee('まつり運行準備ステップガイド（日程別一覧）');
        $response->assertDontSee('準備対象の運行日:');
    }

    /**
     * ヘッダー右のリンクが削除され、ステップ詳細がコンテンツの最初（上部）に配置されていること
     */
    public function test_header_is_slimmed_down_and_layout_reordered(): void
    {
        Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);

        // ヘッダー右リンクが削除されていること
        $response->assertDontSee('進行表（マトリクス）を開く');
        $response->assertDontSee('運行日一覧');

        // 「運行準備 4つのステップ詳細」が「まつり運行準備ステップガイド（日程別一覧）」より前に配置されていること
        $content = $response->getContent();
        $stepDetailsPos = mb_strpos($content, '運行準備 4つのステップ詳細');
        $stepGuidePos = mb_strpos($content, 'まつり運行準備ステップガイド（日程別一覧）');

        $this->assertNotFalse($stepDetailsPos);
        $this->assertNotFalse($stepGuidePos);
        $this->assertLessThan($stepGuidePos, $stepDetailsPos, '「運行準備 4つのステップ詳細」が「まつり運行準備ステップガイド（日程別一覧）」より上に配置されていません');

        // 各日程行に推奨アクションが表示されていること
        $response->assertSee('💡 推奨:');
    }

    /**
     * 完了した運行スケジュールがグレーアウトされ、進行中一覧の下部に表示されること
     */
    public function test_completed_timetables_are_displayed_at_bottom_and_grayed_out(): void
    {
        $activeTimetable = Timetable::factory()->create([
            'name' => '本祭1日目 運行中日程',
            'status' => 'active',
            'target_date' => '2026-10-17',
        ]);

        $completedTimetable = Timetable::factory()->create([
            'name' => '前日設営 完了日程',
            'status' => 'completed',
            'target_date' => '2026-10-16',
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);

        // 両方の日程が表示されていること
        $response->assertSee('本祭1日目 運行中日程');
        $response->assertSee('前日設営 完了日程');

        // 完了した日程が進行中日程よりも後ろ（下部）に描画されていること
        $content = $response->getContent();
        $activePos = mb_strpos($content, '本祭1日目 運行中日程');
        $completedPos = mb_strpos($content, '前日設営 完了日程');

        $this->assertNotFalse($activePos);
        $this->assertNotFalse($completedPos);
        $this->assertLessThan($completedPos, $activePos, '進行中日程が完了日程より前に配置されていません');

        // 完了スケジュールセクションとグレーアウトクラスが存在すること
        $response->assertSee('運行完了したスケジュール');
        $response->assertSee('opacity-75 grayscale-[30%]');
        $response->assertSee('運行完了');
        $response->assertSee('運行実績を確認');
    }

    /**
     * 完了した運行スケジュールは最大2件までに制限されること
     */
    public function test_completed_timetables_are_limited_to_max_2(): void
    {
        // 完了スケジュールを3件作成（日付降順で直近2件が取得される想定）
        Timetable::factory()->create([
            'name' => '過去の運行日1',
            'status' => 'completed',
            'target_date' => '2026-10-01',
        ]);
        Timetable::factory()->create([
            'name' => '過去の運行日2',
            'status' => 'completed',
            'target_date' => '2026-10-05',
        ]);
        Timetable::factory()->create([
            'name' => '過去の運行日3',
            'status' => 'completed',
            'target_date' => '2026-10-10',
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);

        // 直近2件（10/10 と 10/05）が表示され、最も古い 10/01 はナビゲーター一覧に表示されないこと
        $response->assertSee('過去の運行日3');
        $response->assertSee('過去の運行日2');
        $response->assertDontSee('過去の運行日1');
    }

    /**
     * リスト最下部に「全ての運行スケジュールを見る」リンクが設置されていること
     */
    public function test_contains_link_to_all_timetables_at_the_bottom(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('全ての運行スケジュールを見る');
        $response->assertSee(route('timetables.index'));
    }
}
