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
        $response->assertSee('Step 1 / 4');
        $response->assertSee('タイムテーブル作成');
        $response->assertSee('担当班・スタッフ編成');
        $response->assertSee('タスク登録');
        $response->assertSee('人員不足・余剰スタッフ配置');
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
        $response->assertSee('Step 1 / 4');
        $response->assertSee('運行日（タイムテーブル）を登録しましょう');
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
        $response->assertSee('Step 2 / 4');
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
        $response->assertSee('Step 3 / 4');
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
        $response->assertSee('Step 4 / 4');
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
     * 複数運行日がある場合に query パラメータで運行日を切り替えられること
     */
    public function test_can_switch_timetable_in_navigator(): void
    {
        $timetable1 = Timetable::factory()->create([
            'name' => '1日目',
            'status' => 'draft',
        ]);
        $timetable2 = Timetable::factory()->create([
            'name' => '2日目',
            'status' => 'draft',
        ]);

        $lead = Staff::factory()->create(['is_active' => true]);
        Lane::factory()->create([
            'timetable_id' => $timetable1->id,
            'name' => '案内班',
            'lead_staff_id' => $lead->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index', ['timetable_id' => $timetable1->id]));

        $response->assertStatus(200);
        // timetable1は班・リーダーあり、タスク未登録なのでStep 3
        $response->assertSee('Step 3 / 4');

        $response2 = $this->actingAs($this->user)->get(route('preparation-navigator.index', ['timetable_id' => $timetable2->id]));

        $response2->assertStatus(200);
        // timetable2は班未登録なのでStep 2
        $response2->assertSee('Step 2 / 4');
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

        // 「運行準備 4つのステップ詳細」が「まつり運行準備ステップガイド」より前に配置されていること
        $content = $response->getContent();
        $stepDetailsPos = mb_strpos($content, '運行準備 4つのステップ詳細');
        $stepGuidePos = mb_strpos($content, 'まつり運行準備ステップガイド');

        $this->assertNotFalse($stepDetailsPos);
        $this->assertNotFalse($stepGuidePos);
        $this->assertLessThan($stepGuidePos, $stepDetailsPos, '「運行準備 4つのステップ詳細」が「まつり運行準備ステップガイド」より上に配置されていません');

        // 運行日切り替えと現在の推奨アクションがステップガイド内に統合されていること
        $response->assertSee('準備対象の運行日:');
        $response->assertSee('現在の推奨アクション');
    }
}
