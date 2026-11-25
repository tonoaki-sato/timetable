<?php

namespace Tests\Feature;

use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationMenuStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '本祭2026',
            'status' => 'active',
            'target_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    /**
     * 独立した「本部状況」リンクが削除され、ロゴおよび当日運行内に本部ダッシュボードが存在すること
     */
    public function test_standalone_dashboard_link_is_removed_and_integrated_into_day_operations(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 独立した「本部状況」ボタン文字列が存在しないこと
        $response->assertDontSee('<span>本部状況</span>', false);

        // 当日運行内に「本部ダッシュボード」が存在すること
        $response->assertSee('本部ダッシュボード');
        $response->assertSee(route('dashboard'));
    }

    /**
     * 「当日運行」メニューに当日進行表・スタッフタイムライン・搬出入タイムラインが含まれ、当日スタッフ登録が含まれないこと
     */
    public function test_day_operations_menu_contains_expected_items(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 進行表
        $response->assertSee(route('timetable.index', ['timetable_id' => $this->timetable->id]));

        // スタッフタイムライン（旧：人員当日配置）
        $response->assertSee(route('timetables.staff-timeline', $this->timetable));
        $response->assertSee('スタッフタイムライン');
        $response->assertDontSee('人員当日配置');

        // 当日スタッフ登録リンクはメニューから削除されていること
        $response->assertDontSee('当日スタッフ登録');
        $response->assertDontSee(route('staff.create'));

        // 搬出入タイムライン（旧：搬出入チェック）
        $response->assertSee(route('logistics-timeline.index'));
        $response->assertSee('搬出入タイムライン');
        $response->assertDontSee('搬出入チェック');
    }

    /**
     * グローバルメニューが「準備・計画」「当日運行」「マスター管理」「システム・ガイド」の順に並び、「帳票・印刷」が削除されていること
     */
    public function test_global_navigation_order_and_print_menu_removal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '準備・計画',
            '当日運行',
            'マスター管理',
            'システム・ガイド',
        ]);

        // 「帳票・印刷」および「運行準備ナビ」はメニューに存在しないこと
        $response->assertDontSee('帳票・印刷');
        $response->assertDontSee('全体・マスター帳票');
        $response->assertDontSee('運行準備ナビ');

        // 削除された /preparation-navigator は 404 となること
        $this->get('/preparation-navigator')->assertStatus(404);
        $this->get('/preparation-navigator/run')->assertStatus(404);
    }

    /**
     * 「準備・計画」メニューにタスク計画・人員事前配置（アサインワークスペース）が含まれ、運行準備ナビが削除されていること
     */
    public function test_planning_menu_contains_task_plan_and_advance_assignment(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 運行準備ナビは削除されていること
        $response->assertDontSee('運行準備ナビ');

        // 運行日程管理
        $response->assertSee(route('timetables.index'));
        $response->assertSee('運行日程管理');

        // 担当班編成
        $response->assertSee(route('timetable-lanes.index'));
        $response->assertSee('担当班編成');

        // タスク計画
        $response->assertSee(route('timetable-tasks.index'));
        $response->assertSee('タスク計画');
        $response->assertDontSee('作業タスク台帳');

        // 人員事前配置
        $response->assertSee(route('assignments.index'));
        $response->assertSee('人員事前配置');

        // 搬出入計画
        $response->assertSee(route('logistics.index'));
        $response->assertSee('搬出入計画');

        // カレンダー & スケジュール & カラーコーン配置エディタ
        $response->assertSee(route('calendar.index'));
        $response->assertSee(route('schedules.index'));
        $response->assertSee(route('timetables.cone-maps.editor', $this->timetable));
        $response->assertSee('カラーコーン配置エディタ');

        // 2カラム化・論理グループ見出しおよびスクロール対応の検証
        $response->assertSee('grid grid-cols-2');
        $response->assertSee('📋 運行・人員編成');
        $response->assertSee('🗺️ 会場・物流計画');
        $response->assertSee('📅 全体日程・会議');
        $response->assertSee('max-h-[calc(100vh-4.5rem)]');
        $response->assertSee('overflow-y-auto');
    }

    /**
     * マスター管理から担当班の重複が排除され、業務マスターのみに整理されていること
     */
    public function test_master_data_menu_does_not_contain_duplicate_lanes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // スタッフ・組織管理・資材・拠点タイプ・パターン・白地図・会場エリアマスターが存在すること
        $response->assertSee(route('organizations.index'));
        $response->assertSee(route('staff.index'));
        $response->assertSee(route('vendors.index'));
        $response->assertSee(route('site-base-maps.index'));
        $response->assertSee('白地図マスター');
        $response->assertSee(route('layout-areas.index'));
        $response->assertSee('会場エリアマスター');
        $response->assertSee('出展・図面マスター');
        $response->assertSee(route('equipments.index'));
        $response->assertSee(route('location-types.index'));
        $response->assertSee(route('equipment-patterns.index'));

        // 担当班マスターが存在し、担当班編成(日別)とは別箇所に重複なく配置されていること（各デスクトップ・モバイル各1箇所＝2箇所ずつ）
        $response->assertSee(route('lanes.index'));
        $response->assertSee('担当班マスター');
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, route('lanes.index')));
        $this->assertSame(2, substr_count($html, route('timetable-lanes.index')));
    }

    /**
     * グローバルメニューから帳票・印刷が削除され、運行詳細画面側の区分B専用カードおよび個別帳票ルートは維持されていること
     */
    public function test_reports_menu_removed_from_global_nav_while_detail_reports_remain(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // グローバルメニューから「帳票・印刷」が削除されていること
        $response->assertDontSee('帳票・印刷');
        $response->assertDontSee('全体・マスター帳票 (区分A)');
        $response->assertDontSee('個別名札緊急印刷');
        $response->assertDontSee('/logistics/print');
        $response->assertDontSee(route('timetables.logistics.print', $this->timetable));

        // 運行詳細画面側で区分B専用カードが表示されること
        $timetableResponse = $this->actingAs($this->admin)->get(route('timetables.show', $this->timetable));
        $timetableResponse->assertStatus(200);
        $timetableResponse->assertSee('運行日別・現場帳票（PDF印刷）');
        $timetableResponse->assertSee(route('timetables.print-matrix', $this->timetable));
        $timetableResponse->assertSee(route('timetables.lanes.print', $this->timetable));
        $timetableResponse->assertSee(route('timetables.logistics.print', $this->timetable));
        $timetableResponse->assertSee(route('timetables.print-assignments', $this->timetable));
        $timetableResponse->assertSee('スタッフ別タスク一覧');
    }

    /**
     * ユーザー管理がシステム・ガイドメニューに配置され、非アクティブユーザーには非表示であること
     */
    public function test_user_management_is_in_system_menu_and_role_restricted(): void
    {
        // 管理者には表示される
        $adminRes = $this->actingAs($this->admin)->get(route('dashboard'));
        $adminRes->assertSee(route('users.index'));
        $adminRes->assertSee('ユーザー管理');

        // 非アクティブユーザーにはポリシーにより非表示
        $inactive = User::factory()->create(['is_active' => false]);
        $inactiveRes = $this->actingAs($inactive)->get(route('dashboard'));
        // 非アクティブはダッシュボード自体が403
        $inactiveRes->assertStatus(403);
    }

    /**
     * 新メニューの全主要リンク先が 200 OK (またはリダイレクト成功) を返すこと
     */
    public function test_all_navigated_routes_return_ok(): void
    {
        $this->actingAs($this->admin);

        // 複数運行日を作成
        Timetable::factory()->create([
            'name' => '前夜祭2026',
            'status' => 'planning',
            'target_date' => now()->addDay()->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '21:00:00',
        ]);

        // timetable-tasks.index, timetable-lanes.index, assignments.index, logistics.index はアクティブな運行日へリダイレクトされる仕様
        $this->get(route('timetable-tasks.index'))
            ->assertRedirect(route('timetables.tasks.index', $this->timetable));
        $this->get(route('timetable-lanes.index'))
            ->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $this->get(route('assignments.index'))
            ->assertRedirect(route('timetables.assignments', $this->timetable));
        $this->get(route('logistics.index'))
            ->assertRedirect(route('timetables.logistics.index', $this->timetable));

        $routesToTest = [
            route('dashboard'),
            route('timetable.index', ['timetable_id' => $this->timetable->id]),
            route('timetables.staff-timeline', $this->timetable),
            route('staff.create'),
            route('timetables.logistics.index', $this->timetable),
            route('timetables.index'),
            route('lanes.index'),
            route('timetables.lanes.index', $this->timetable),
            route('timetables.assignments', $this->timetable),
            route('calendar.index'),
            route('schedules.index'),
            route('staff.index'),
            route('equipments.index'),
            route('locations.index'),
            route('location-types.index'),
            route('site-base-maps.index'),
            route('layout-areas.index'),
            route('timetables.print-assignments', $this->timetable),
            route('timetables.logistics.print', $this->timetable),
            route('timetables.cone-maps.editor', $this->timetable),
            route('staff.print-cards'),
            route('manual.index'),
            route('presentation.index'),
            route('users.index'),
        ];

        foreach ($routesToTest as $url) {
            $response = $this->get($url);
            $this->assertSame(200, $response->getStatusCode(), "Route {$url} failed with status {$response->getStatusCode()}");
        }
    }

    /**
     * 運行日程が0件の場合、担当班編成・タスク計画・人員事前配置・搬出入計画のリンクが無効化されグレーアウトされること
     */
    public function test_navigation_links_disabled_and_grayed_out_when_no_timetables_exist(): void
    {
        // 既存の運行日程をすべて削除
        Timetable::query()->delete();

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 各リンクの href 属性が出力されていないこと（担当班マスター /lanes は常時利用可能）
        $response->assertSee(route('lanes.index'));
        $response->assertDontSee(route('timetable-lanes.index'));
        $response->assertDontSee(route('timetable-tasks.index'));
        $response->assertDontSee(route('assignments.index'));
        $response->assertDontSee(route('logistics.index'));
        $response->assertDontSee(route('logistics-timeline.index'));

        // メニューの表示テキスト自体は存在すること
        $response->assertSee('担当班編成');
        $response->assertSee('タスク計画');
        $response->assertSee('人員事前配置');
        $response->assertSee('搬出入計画');
        $response->assertSee('搬出入タイムライン');

        // グレーアウト用のクラス・属性が存在すること
        $response->assertSee('cursor-not-allowed');
        $response->assertSee('運行日程の登録が必要です');

        // 直接代表URLにアクセスした場合は timetables.index へフォールバックリダイレクトされること
        $this->get(route('timetable-lanes.index'))->assertRedirect(route('timetables.index'));
        $this->get(route('lanes.index'))->assertStatus(200);
        $this->get(route('timetable-tasks.index'))->assertRedirect(route('timetables.index'));
        $this->get(route('assignments.index'))->assertRedirect(route('timetables.index'));
        $this->get(route('logistics.index'))->assertRedirect(route('timetables.index'));
        $this->get(route('logistics-timeline.index'))->assertRedirect(route('timetables.index'));
    }
}
