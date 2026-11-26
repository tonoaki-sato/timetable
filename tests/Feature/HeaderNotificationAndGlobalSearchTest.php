<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNotificationAndGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    /**
     * ヘッダー通知ベルに未完了の班長現場要請件数バッジと一覧が表示され、完了済みは除外されること
     */
    public function test_header_notification_bell_displays_pending_and_approved_lane_requests_only(): void
    {
        $staff = Staff::factory()->create(['name' => '申請班長テスト']);
        $timetable = Timetable::factory()->create(['created_by' => $this->admin->id]);
        $masterLane = Lane::factory()->create(['name' => '交通誘導A班']);
        $lane = TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $masterLane->id,
            'lead_staff_id' => $staff->id,
        ]);

        LaneRequest::create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'requested_by_staff_id' => $staff->id,
            'request_type' => 'equipment',
            'requested_count' => 3,
            'equipment_name' => '未対応メガホン',
            'reason' => '故障のため追加希望',
            'status' => 'pending',
        ]);

        LaneRequest::create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'requested_by_staff_id' => $staff->id,
            'request_type' => 'staffing',
            'requested_count' => 2,
            'reason' => '手配中テスト',
            'status' => 'approved',
        ]);

        LaneRequest::create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'requested_by_staff_id' => $staff->id,
            'request_type' => 'equipment',
            'requested_count' => 1,
            'equipment_name' => '完了済みカラーコーン',
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertSee('id="header-notification-bell"', false);
        $response->assertSee('未対応の現場要請 (2件)');
        $response->assertSee('未対応メガホン');
        $response->assertSee('手配中テスト');
        $response->assertDontSee('完了済みカラーコーン');

        // 検索・確認モーダルDOMも含まれ、CSP違反・rose-*クラスが含まれないこと
        $html = (string) $response->getContent();
        $response->assertSee('id="quickSearchModal"', false);
        $response->assertSee('id="appConfirmModal"', false);
        $response->assertDontSee('rose-', false);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $html);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html);
    }

    /**
     * グローバルヘッダーの検索トリガーが1280px未満(Edgeサイドバー表示等)でも「検索」ラベルを表示し、余白ズレしない構成になっていること
     */
    public function test_header_quick_search_button_displays_label_from_sm_breakpoint_with_gap_spacing(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $html = (string) $response->getContent();

        $response->assertSee('id="btn-open-quick-search"', false);
        $response->assertSee('inline-flex items-center gap-1.5', false);
        $response->assertSee('<span class="hidden sm:inline whitespace-nowrap">検索</span>', false);
        $response->assertSee('<kbd class="hidden xl:inline-block whitespace-nowrap', false);
        $response->assertSee('Ctrl K</kbd>', false);
        $response->assertDontSee('<span class="hidden xl:inline">検索</span>', false);
        $response->assertDontSee('space-x-1.5 px-2.5 py-1.5', false);

        // モーダルDOMおよび共通Viteアセット(app.css / app.js)が出力されていること
        $response->assertSee('id="quickSearchModal"', false);
        $this->assertMatchesRegularExpression('/assets\/app-[^"\']+\.css/', $html);
        $this->assertMatchesRegularExpression('/assets\/app-[^"\']+\.js/', $html);
    }

    /**
     * グローバル横断検索APIが5エンティティを横断検索してグループ返却し、空検索時は空配列を返すこと
     */
    public function test_global_search_api_returns_grouped_matches_and_respects_auth(): void
    {
        // 未認証時はログイン画面へリダイレクト
        $this->get(route('search.quick', ['q' => '横断ターゲット']))->assertRedirect(route('login'));

        $timetable = Timetable::factory()->create(['created_by' => $this->admin->id]);
        $lane = TimetableLane::factory()->create(['timetable_id' => $timetable->id]);

        Staff::factory()->create(['name' => '横断ターゲットスタッフ', 'kana' => 'オウダン']);
        Vendor::create(['name' => '横断ターゲット屋台']);
        Equipment::factory()->create(['name' => '横断ターゲット発電機', 'created_by' => $this->admin->id]);
        Schedule::factory()->create(['title' => '横断ターゲット協議会', 'created_by' => $this->admin->id]);
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => '横断ターゲット巡回タスク',
        ]);

        // 空検索
        $emptyRes = $this->actingAs($this->admin)->getJson(route('search.quick', ['q' => '   ']));
        $emptyRes->assertStatus(200);
        $emptyRes->assertJson([
            'query' => '',
            'total' => 0,
            'groups' => [],
        ]);

        // キーワード一致検索
        $searchRes = $this->actingAs($this->admin)->getJson(route('search.quick', ['q' => '横断ターゲット']));
        $searchRes->assertStatus(200);
        $searchRes->assertJson([
            'query' => '横断ターゲット',
            'total' => 5,
        ]);

        $groups = collect($searchRes->json('groups'))->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['staff', 'vendors', 'equipments', 'schedules', 'tasks'], $groups);
    }
}
