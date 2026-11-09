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
     * 「当日運行」メニューに当日進行表・人員当日配置・当日スタッフ登録・搬出入チェックが含まれること
     */
    public function test_day_operations_menu_contains_expected_items(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 進行表
        $response->assertSee(route('timetable.index', ['timetable_id' => $this->timetable->id]));

        // 人員当日配置
        $response->assertSee(route('timetables.staff-timeline', $this->timetable));
        $response->assertSee('人員当日配置');

        // 当日スタッフ登録
        $response->assertSee(route('staff.create'));
        $response->assertSee('当日スタッフ登録');

        // 搬出入チェック
        $response->assertSee(route('logistics.index'));
        $response->assertSee('搬出入チェック');
    }

    /**
     * 「準備・計画」メニューの先頭に運行準備ナビ、人員事前配置（アサインワークスペース）が含まれること
     */
    public function test_planning_menu_contains_navigator_and_advance_assignment(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 運行準備ナビ
        $response->assertSee(route('preparation-navigator.index'));
        $response->assertSee('運行準備ナビ');

        // 運行日程管理
        $response->assertSee(route('timetables.index'));
        $response->assertSee('運行日程管理');

        // 担当班編成
        $response->assertSee(route('lanes.index'));
        $response->assertSee('担当班編成');

        // タスク台帳
        $response->assertSee(route('timetable-tasks.index'));
        $response->assertSee('作業タスク台帳');

        // 人員事前配置
        $response->assertSee(route('timetables.assignments', $this->timetable));
        $response->assertSee('人員事前配置');

        // カレンダー & スケジュール
        $response->assertSee(route('calendar.index'));
        $response->assertSee(route('schedules.index'));
    }

    /**
     * マスター管理から担当班の重複が排除され、業務マスターのみに整理されていること
     */
    public function test_master_data_menu_does_not_contain_duplicate_lanes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // スタッフ・資材・拠点タイプ・パターンが存在すること
        $response->assertSee(route('staff.index'));
        $response->assertSee(route('equipments.index'));
        $response->assertSee(route('location-types.index'));
        $response->assertSee(route('equipment-patterns.index'));

        // マスター管理内に担当班が重複登録されていないこと（デスクトップ・モバイル各1箇所のみ）
        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, route('lanes.index')));
    }

    /**
     * 帳票・印刷メニューが当日用と事前準備用にセクション化されていること
     */
    public function test_reports_menu_is_properly_sectioned(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        // 現場・当日用帳票
        $response->assertSee(route('timetables.print-assignments', $this->timetable));
        $response->assertSee(route('logistics.print'));
        $response->assertSee('個別名札緊急印刷');

        // 事前準備用帳票
        $response->assertSee(route('staff.print-cards'));
        $response->assertSee(route('staff.export-csv'));
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

        // 複数運行日を作成して lanes.index の単一リダイレクトを回避
        Timetable::factory()->create([
            'name' => '前夜祭2026',
            'status' => 'planning',
            'target_date' => now()->addDay()->toDateString(),
            'start_time' => '17:00:00',
            'end_time' => '21:00:00',
        ]);

        // timetable-tasks.index はアクティブな運行日へリダイレクトされる仕様
        $taskRes = $this->get(route('timetable-tasks.index'));
        $taskRes->assertRedirect(route('timetables.tasks.index', $this->timetable));
        $this->get(route('timetables.tasks.index', $this->timetable))->assertStatus(200);

        $routesToTest = [
            route('dashboard'),
            route('timetable.index', ['timetable_id' => $this->timetable->id]),
            route('timetables.staff-timeline', $this->timetable),
            route('staff.create'),
            route('logistics.index'),
            route('preparation-navigator.index'),
            route('timetables.index'),
            route('lanes.index'),
            route('timetables.assignments', $this->timetable),
            route('calendar.index'),
            route('schedules.index'),
            route('staff.index'),
            route('equipments.index'),
            route('timetables.locations.index', $this->timetable),
            route('location-types.index'),
            route('timetables.print-assignments', $this->timetable),
            route('logistics.print'),
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
}
