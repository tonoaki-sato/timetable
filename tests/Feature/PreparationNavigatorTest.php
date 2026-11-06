<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Timetable;
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
        $response->assertSee('運行準備ナビ');
        $response->assertSee(route('preparation-navigator.index'));
    }

    /**
     * ステップ詳細が全4ステップ（Step 1〜4）で構成され、不要なステップを含まないこと
     */
    public function test_step_guide_displays_4_steps_without_login_step(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('運行準備 4つのステップ詳細');
        $response->assertSee('Step 1: タイムテーブル作成');
        $response->assertSee('Step 2: 担当班編成');
        $response->assertSee('Step 3: タスク登録');
        $response->assertSee('Step 4: 人員配置');
        $response->assertDontSee('ログイン・運行状況確認');
    }

    /**
     * タイムテーブル未登録時は Step 1 カードが未着手表示となり新規作成リンクになること
     */
    public function test_shows_step_1_unstarted_when_no_timetable_exists(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('まつり運行準備ナビゲーター');
        $response->assertSee('未着手');
        $response->assertSee('＋ 新規作成へ');
        $response->assertSee(route('timetables.create'));
    }

    /**
     * タイムテーブル登録時は Step 1 カードが登録済表示となり、Step 4 からスタッフタイムラインへリンクすること
     */
    public function test_shows_step_1_registered_and_step_4_timeline_link_when_timetable_exists(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertSee('登録済 (1日)');
        $response->assertSee('一覧確認へ');
        $response->assertSee(route('timetables.staff-timeline', $timetable));
    }

    /**
     * ページヘッダーのバッジおよび「進行表へ」アクションリンクが削除されていること
     */
    public function test_header_badge_and_actions_are_removed(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);
        $response->assertDontSee('px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-50 text-indigo-700');
        $response->assertDontSee('進行表へ');
    }

    /**
     * まつり運行準備ステップガイド（日程別一覧）、完了スケジュール、最下部リンクが削除されていること
     */
    public function test_schedule_list_and_bottom_links_are_removed(): void
    {
        Timetable::factory()->create([
            'name' => '進行中日程',
            'status' => 'active',
        ]);
        Timetable::factory()->create([
            'name' => '完了日程',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.index'));

        $response->assertStatus(200);

        // 日程別一覧見出しおよび各日程カード・完了スケジュールが表示されないこと
        $response->assertDontSee('まつり運行準備ステップガイド（日程別一覧）');
        $response->assertDontSee('運行完了したスケジュール');
        $response->assertDontSee('全ての運行スケジュールを見る');
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
     * 進行中のタイムテーブルがある場合、run ルートが未完了ステップ（Step 2 班編成）へリダイレクトすること
     */
    public function test_run_redirects_to_incomplete_step_when_timetable_exists(): void
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

        $response = $this->actingAs($this->user)->get(route('preparation-navigator.run'));

        $response->assertRedirect(route('timetables.lanes.index', $timetable));
    }
}
