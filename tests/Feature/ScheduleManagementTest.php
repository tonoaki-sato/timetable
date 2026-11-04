<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->staff = Staff::factory()->create([
            'name' => '漆原 拓也',
            'affiliation' => 'まつり運行本部 幹事',
        ]);
    }

    /**
     * スケジュール一覧が表示され、カテゴリ・ステータス等で絞り込みできること
     */
    public function test_index_displays_schedules_and_filters(): void
    {
        $this->actingAs($this->user);

        $schedule1 = Schedule::factory()->create([
            'title' => '本祭パレード設営',
            'category' => 'operation',
            'status' => 'planned',
            'start_date' => '2027-10-10',
        ]);

        $schedule2 = Schedule::factory()->create([
            'title' => '定例幹事会ミーティング',
            'category' => 'committee',
            'status' => 'completed',
            'start_date' => '2027-02-15',
        ]);

        $response = $this->get(route('schedules.index'));
        $response->assertStatus(200);
        $response->assertSee('本祭パレード設営');
        $response->assertSee('定例幹事会ミーティング');

        // カテゴリ絞り込み
        $catResponse = $this->get(route('schedules.index', ['category' => 'committee']));
        $catResponse->assertStatus(200);
        $catResponse->assertSee('定例幹事会ミーティング');
        $catResponse->assertDontSee('本祭パレード設営');

        // 運行スケジュール（timetable）での絞り込み
        $timetable = Timetable::factory()->create([
            'name' => '宵宮運行タイムテーブル',
        ]);
        $timetableSchedule = Schedule::factory()->create([
            'title' => '宵宮運行タイムテーブル',
            'category' => 'operation',
            'timetable_id' => $timetable->id,
            'start_date' => '2027-10-11',
        ]);
        $timetableCatResponse = $this->get(route('schedules.index', ['category' => 'timetable']));
        $timetableCatResponse->assertStatus(200);
        $timetableCatResponse->assertSee('宵宮運行タイムテーブル');
        $timetableCatResponse->assertDontSee('定例幹事会ミーティング');
        $timetableCatResponse->assertDontSee('本祭パレード設営');

        // 新規登録文言・操作列文言（編集・削除）の表示確認、および詳細ボタン・PKIサマリー非表示の確認
        $indexResponse = $this->get(route('schedules.index'));
        $indexResponse->assertSee('新規スケジュールを登録');
        $indexResponse->assertSee('編集');
        $indexResponse->assertSee('削除');
        $indexResponse->assertDontSee('総登録件数');

        // キーワード検索
        $searchResponse = $this->get(route('schedules.index', ['search' => 'パレード']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('本祭パレード設営');
        $searchResponse->assertDontSee('定例幹事会ミーティング');
    }

    /**
     * タブ切り替え（今後の予定 / 過去ログ）ができること
     */
    public function test_index_can_switch_tabs(): void
    {
        $this->actingAs($this->user);

        $upcoming = Schedule::factory()->create([
            'title' => '来月の定例会',
            'start_date' => now()->addDays(10)->toDateString(),
        ]);

        $past = Schedule::factory()->create([
            'title' => '先月の反省会',
            'start_date' => now()->subDays(10)->toDateString(),
        ]);

        // 今後の予定タブ
        $resUpcoming = $this->get(route('schedules.index', ['tab' => 'upcoming']));
        $resUpcoming->assertStatus(200);
        $resUpcoming->assertSee('来月の定例会');
        $resUpcoming->assertDontSee('先月の反省会');

        // 過去タブ
        $resPast = $this->get(route('schedules.index', ['tab' => 'past']));
        $resPast->assertStatus(200);
        $resPast->assertSee('先月の反省会');
        $resPast->assertDontSee('来月の定例会');
    }

    /**
     * 正しいデータで新規登録できること（時刻・メモ含む）
     */
    public function test_can_create_schedule(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('schedules.store'), [
            'title' => '資機材一斉点検・整備',
            'category' => 'operation',
            'start_date' => '2027-10-18',
            'end_date' => '2027-10-18',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'location' => '第1倉庫',
            'status' => 'planned',
            'description' => '関係者一同参列のこと。',
            'staff_id' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('schedules', [
            'title' => '資機材一斉点検・整備',
            'category' => 'operation',
            'location' => '第1倉庫',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'staff_id' => $this->staff->id,
            'created_by' => $this->user->id,
        ]);

        $schedule = Schedule::where('title', '資機材一斉点検・整備')->first();
        $response->assertRedirect(route('schedules.show', $schedule));
    }

    /**
     * バリデーションエラーが正しく機能すること（target_yearは不要）
     */
    public function test_validation_rules_for_creating_schedule(): void
    {
        $this->actingAs($this->user);

        $response = $this->from(route('schedules.create'))->post(route('schedules.store'), []);

        $response->assertRedirect(route('schedules.create'));
        $response->assertSessionHasErrors(['title', 'category', 'start_date', 'status']);
        $this->assertFalse(session('errors')->has('target_year'));
    }

    /**
     * 詳細画面が表示され、LINE告知文が生成されること
     */
    public function test_show_displays_schedule_and_share_text(): void
    {
        $this->actingAs($this->user);

        $schedule = Schedule::factory()->create([
            'title' => '第1回 幹事会',
            'category' => 'committee',
            'start_date' => '2027-01-10',
            'start_time' => '18:00',
            'end_time' => '20:00',
            'location' => 'コミュニティルーム',
            'staff_id' => $this->staff->id,
            'description' => '年間スケジュール案の策定',
        ]);

        $response = $this->get(route('schedules.show', $schedule));
        $response->assertStatus(200);
        $response->assertSee('第1回 幹事会');
        $response->assertSee('コミュニティルーム');
        $response->assertSee('漆原 拓也');
        $response->assertSee('【会議開催のお知らせ】');
        $response->assertSee('年間スケジュール案の策定');
    }

    /**
     * スケジュールを更新できること
     */
    public function test_can_update_schedule(): void
    {
        $this->actingAs($this->user);

        $schedule = Schedule::factory()->create([
            'title' => '変更前タイトル',
            'category' => 'other',
            'start_date' => '2027-05-01',
            'status' => 'planned',
        ]);

        $response = $this->put(route('schedules.update', $schedule), [
            'title' => '変更後タイトル',
            'category' => 'briefing',
            'start_date' => '2027-05-05',
            'status' => 'in_progress',
        ]);

        $response->assertRedirect(route('schedules.show', $schedule));

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'title' => '変更後タイトル',
            'category' => 'briefing',
            'status' => 'in_progress',
        ]);
    }

    /**
     * スケジュールを削除できること
     */
    public function test_can_delete_schedule(): void
    {
        $this->actingAs($this->user);

        $schedule = Schedule::factory()->create([
            'title' => '削除対象予定',
        ]);

        $response = $this->delete(route('schedules.destroy', $schedule));

        $response->assertRedirect(route('schedules.index'));
        $this->assertDatabaseMissing('schedules', [
            'id' => $schedule->id,
        ]);
    }

    /**
     * 運行スケジュール（タイムテーブル連動）はスケジュール管理画面から削除できないこと
     */
    public function test_cannot_delete_linked_timetable_schedule_from_schedules_screen(): void
    {
        $this->actingAs($this->user);

        $timetable = Timetable::factory()->create([
            'name' => '本宮本番運行',
        ]);

        $schedule = Schedule::factory()->create([
            'title' => '本宮本番運行',
            'category' => 'operation',
            'timetable_id' => $timetable->id,
        ]);

        // 一覧画面で削除ボタンではなく「連動中」バッジが表示されていること
        $listResponse = $this->get(route('schedules.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('連動中');

        // 詳細画面でも削除ボタンが表示されないこと
        $showResponse = $this->get(route('schedules.show', $schedule));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('予定「本宮本番運行」を削除しますか？');

        // DELETE リクエストを送信しても削除が拒否され、エラーメッセージでリダイレクトされること
        $deleteResponse = $this->delete(route('schedules.destroy', $schedule));
        $deleteResponse->assertRedirect(route('schedules.index'));
        $deleteResponse->assertSessionHas('error');

        // DB 上で schedule も timetable も削除されずに残っていること
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
        ]);
        $this->assertDatabaseHas('timetables', [
            'id' => $timetable->id,
        ]);
    }
}
