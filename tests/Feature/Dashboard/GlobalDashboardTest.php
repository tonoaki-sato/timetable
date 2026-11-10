<?php

namespace Tests\Feature\Dashboard;

use App\Models\Equipment;
use App\Models\Lane;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * ゲストはダッシュボードへアクセスできず、ログイン画面へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 運行計画に紐づく運行詳細（Now/Nextタスク、交通規制、駐車場利用監視）が表示されないこと
     */
    public function test_dashboard_does_not_display_operation_plan_tied_information(): void
    {
        $now = Carbon::create(2026, 10, 11, 10, 15, 0, 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
            'target_date' => '2026-10-11',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'status' => 'closed', 'label' => '通行止め'],
            ],
        ]);

        $lane = Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => 'カラーコーン']);
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '通行止めコーン一斉設置作業',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);

        // 運行計画固有のカード・タスク・規制・駐車場文言がダッシュボードに存在しないこと
        $response->assertDontSee('⚡ NOW : 現在進行中のタスク');
        $response->assertDontSee('⏳ NEXT : 次に控えるタスク');
        $response->assertDontSee('道路交通状況');
        $response->assertDontSee('車両通行止め (残り');
        $response->assertDontSee('拠点・駐車場 利用状況監視');
        $response->assertDontSee('通行止めコーン一斉設置作業');
        $response->assertDontSee('未対応 人員要請・機材申請');
    }

    /**
     * 年間運営マイルストーン、会議、許認可申請、マスタサマリー、運行ゲートウェイが表示されること
     */
    public function test_dashboard_displays_global_operations_and_master_gateway(): void
    {
        $now = Carbon::create(2026, 6, 1, 10, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $staff = Staff::factory()->create(['name' => '佐藤 幹事', 'staff_type' => 'fixed', 'is_active' => true]);
        Equipment::factory()->create(['name' => 'カラーコーン']);
        Location::factory()->create(['name' => '番所倉庫']);

        // 会議
        Schedule::factory()->asMeeting()->create([
            'title' => '第1回 実行委員会・全体協議',
            'start_date' => '2026-06-05',
            'staff_id' => $staff->id,
        ]);

        // 許認可・申請
        Schedule::factory()->create([
            'title' => '保土ケ谷警察署 道路使用許可申請',
            'category' => 'permit',
            'start_date' => '2026-06-12',
            'status' => 'planned',
            'staff_id' => $staff->id,
        ]);

        // 運行計画
        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 本祭運行',
            'target_date' => '2026-10-11',
            'status' => 'draft',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);

        // ヘッダー & タイトル
        $response->assertSee('🏮');
        $response->assertSee('本部運行総合ダッシュボード');
        $response->assertSee('システム正常稼働中');

        // 年間フェーズ
        $response->assertSee('準備フェーズ');

        // 薄型 4大KPI
        $response->assertSee('年間行事・進捗');
        $response->assertSee('本部スタッフ体制');
        $response->assertSee('資機材・拠点マスタ');
        $response->assertSee('運行日程・計画');

        // 会議カード
        $response->assertSee('直近の会議・打合せ');
        $response->assertSee('第1回 実行委員会・全体協議');

        // 許認可・申請カード
        $response->assertSee('直近の許認可・申請・説明会');
        $response->assertSee('保土ケ谷警察署 道路使用許可申請');

        // 運行日程ゲートウェイ
        $response->assertSee('まつり運行日程ゲートウェイ');
        $response->assertSee('宿場まつり 本祭運行');
        $response->assertSee(route('timetables.show', $timetable));
        $response->assertSee('運行詳細コックピットを開く');

        // 組織・マスタ & クイックランチャー
        $response->assertSee('組織・資機材・拠点マスタ');
        $response->assertSee('司令塔クイックランチャー');
        $response->assertSee(route('preparation-navigator.index'));
        $response->assertSee(route('schedules.index'));
        $response->assertSee(route('staff.index'));
        $response->assertSee(route('equipments.index'));
        $response->assertSee(route('location-types.index'));
    }

    /**
     * 厳格CSP検証: プログレスバーにインラインstyle属性が出力されず、data-percent属性が設定されていること
     */
    public function test_progress_bar_adheres_to_strict_csp_without_inline_styles(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // data-percent が存在すること
        $this->assertStringContainsString('data-percent="', $content);

        // プログレスバー内部に style="width: が存在しないこと (厳格CSP)
        $this->assertStringNotContainsString('style="width:', $content);
    }
}
