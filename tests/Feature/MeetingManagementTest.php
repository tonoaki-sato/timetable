<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingManagementTest extends TestCase
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
     * 一覧画面がデフォルトで開催予定の会議を表示すること
     */
    public function test_index_displays_upcoming_meetings_by_default(): void
    {
        $this->actingAs($this->user);

        $upcomingMeeting = Meeting::factory()->create([
            'title' => '未来の定例幹事会',
            'meeting_date' => now()->addDays(5)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        $pastMeeting = Meeting::factory()->create([
            'title' => '過去の反省会',
            'meeting_date' => now()->subDays(5)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('meetings.index'));
        $response->assertStatus(200);
        $response->assertSee('未来の定例幹事会');
        $response->assertDontSee('過去の反省会');
    }

    /**
     * 過去タブに切り替えて過去の会議が表示されること
     */
    public function test_index_can_switch_to_past_tab(): void
    {
        $this->actingAs($this->user);

        $upcomingMeeting = Meeting::factory()->create([
            'title' => '未来の定例幹事会',
            'meeting_date' => now()->addDays(5)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        $pastMeeting = Meeting::factory()->create([
            'title' => '過去の反省会',
            'meeting_date' => now()->subDays(5)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('meetings.index', ['tab' => 'past']));
        $response->assertStatus(200);
        $response->assertSee('過去の反省会');
        $response->assertDontSee('未来の定例幹事会');
    }

    /**
     * 主催スタッフによる絞り込みができること
     */
    public function test_filter_by_host_staff(): void
    {
        $this->actingAs($this->user);

        $otherStaff = Staff::factory()->create(['name' => '佐藤 秀樹']);

        $meeting1 = Meeting::factory()->create([
            'title' => '漆原さん主催会議',
            'meeting_date' => now()->addDays(3)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        $meeting2 = Meeting::factory()->create([
            'title' => '佐藤さん主催会議',
            'meeting_date' => now()->addDays(4)->format('Y-m-d'),
            'host_staff_id' => $otherStaff->id,
        ]);

        $response = $this->get(route('meetings.index', ['host_staff_id' => $this->staff->id]));
        $response->assertStatus(200);
        $response->assertSee('漆原さん主催会議');
        $response->assertDontSee('佐藤さん主催会議');
    }

    /**
     * キーワード検索が機能すること
     */
    public function test_search_meetings(): void
    {
        $this->actingAs($this->user);

        Meeting::factory()->create([
            'title' => 'カラーコーン搬入会議',
            'location' => '第1倉庫',
            'meeting_date' => now()->addDays(2)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        Meeting::factory()->create([
            'title' => '給水設備点検会議',
            'location' => '給水所',
            'meeting_date' => now()->addDays(2)->format('Y-m-d'),
            'host_staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('meetings.index', ['search' => 'カラーコーン']));
        $response->assertStatus(200);
        $response->assertSee('カラーコーン搬入会議');
        $response->assertDontSee('給水設備点検会議');
    }

    /**
     * 正しいデータで新規登録できること
     */
    public function test_can_create_meeting(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('meetings.store'), [
            'title' => '第1回 警備・動線協議会',
            'meeting_date' => '2027-01-15',
            'start_time' => '18:00',
            'end_time' => '19:30',
            'location' => 'コミュニティルーム',
            'host_staff_id' => $this->staff->id,
            'description' => '車両通行規制と迂回路の事前確認',
            'notice_note' => '出欠は前日までにお願いします。',
        ]);

        $meeting = Meeting::where('title', '第1回 警備・動線協議会')->first();
        $this->assertNotNull($meeting);

        $response->assertRedirect(route('meetings.show', $meeting));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('meetings', [
            'title' => '第1回 警備・動線協議会',
            'location' => 'コミュニティルーム',
            'host_staff_id' => $this->staff->id,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * バリデーションルールが機能すること
     */
    public function test_validation_for_creating_meeting(): void
    {
        $this->actingAs($this->user);

        // 必須項目未入力
        $response = $this->post(route('meetings.store'), []);
        $response->assertSessionHasErrors(['title', 'meeting_date', 'start_time', 'location', 'host_staff_id']);

        // 終了時刻が開始時刻より前
        $invalidTimeResponse = $this->post(route('meetings.store'), [
            'title' => '時刻逆転テスト',
            'meeting_date' => '2027-01-15',
            'start_time' => '18:00',
            'end_time' => '17:00',
            'location' => '場所',
            'host_staff_id' => $this->staff->id,
        ]);
        $invalidTimeResponse->assertSessionHasErrors('end_time');
    }

    /**
     * 詳細画面で会議情報およびLINE告知文が表示されること
     */
    public function test_can_show_meeting_with_line_texts(): void
    {
        $this->actingAs($this->user);

        $meeting = Meeting::factory()->create([
            'title' => '全体進行打ち合わせ',
            'meeting_date' => '2027-02-10',
            'start_time' => '19:00',
            'end_time' => '20:30',
            'location' => '市民会館2F',
            'host_staff_id' => $this->staff->id,
            'description' => 'タイムテーブルの最終すり合わせ',
            'notice_note' => '返信期日は2/8まで。',
        ]);

        $response = $this->get(route('meetings.show', $meeting));
        $response->assertStatus(200);
        $response->assertSee('全体進行打ち合わせ');
        $response->assertSee('漆原 拓也');
        $response->assertSee('全体LINE告知テキスト');
        $response->assertSee('LINEで送る');
        $response->assertSee('文面をコピーする');
    }

    /**
     * 会議情報を正常に更新できること
     */
    public function test_can_update_meeting(): void
    {
        $this->actingAs($this->user);

        $meeting = Meeting::factory()->create([
            'title' => '更新前タイトル',
            'host_staff_id' => $this->staff->id,
        ]);

        $newStaff = Staff::factory()->create(['name' => '新主催者']);

        $response = $this->put(route('meetings.update', $meeting), [
            'title' => '更新後タイトル',
            'meeting_date' => '2027-03-01',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'location' => '新会場',
            'host_staff_id' => $newStaff->id,
            'description' => '新アジェンダ',
        ]);

        $response->assertRedirect(route('meetings.show', $meeting));
        $response->assertSessionHas('success');

        $meeting->refresh();
        $this->assertSame('更新後タイトル', $meeting->title);
        $this->assertSame('新会場', $meeting->location);
        $this->assertSame($newStaff->id, $meeting->host_staff_id);
    }

    /**
     * 会議を正常に削除できること
     */
    public function test_can_delete_meeting(): void
    {
        $this->actingAs($this->user);

        $meeting = Meeting::factory()->create([
            'title' => '削除対象会議',
            'host_staff_id' => $this->staff->id,
        ]);

        $response = $this->delete(route('meetings.destroy', $meeting));
        $response->assertRedirect(route('meetings.index', ['tab' => $meeting->is_upcoming ? 'upcoming' : 'past']));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('meetings', [
            'id' => $meeting->id,
        ]);
    }

    /**
     * LINE共有用・リマインド用のテキストアクセサが正しく組み立てられること
     */
    public function test_line_text_accessors(): void
    {
        $meeting = Meeting::factory()->create([
            'title' => '幹事会キックオフ',
            'meeting_date' => '2027-01-10',
            'start_time' => '18:00',
            'end_time' => '20:00',
            'location' => 'コミュニティルーム',
            'host_staff_id' => $this->staff->id,
            'description' => '年間スケジュールの策定',
            'notice_note' => '出欠は1/8までにお願いします。',
        ]);

        $shareText = $meeting->line_share_text;
        $this->assertStringContainsString('【会議開催のお知らせ】', $shareText);
        $this->assertStringContainsString('「幹事会キックオフ」の開催日程が決まりました。', $shareText);
        $this->assertStringContainsString('2027年1月10日(日) 18:00 〜 20:00', $shareText);
        $this->assertStringContainsString('コミュニティルーム', $shareText);
        $this->assertStringContainsString('漆原 拓也', $shareText);
        $this->assertStringContainsString('年間スケジュールの策定', $shareText);
        $this->assertStringContainsString('出欠は1/8までにお願いします。', $shareText);

        $remindText = $meeting->line_remind_text;
        $this->assertStringContainsString('【リマインド】明日開催の会議案内', $remindText);
        $this->assertStringContainsString('明日、「幹事会キックオフ」が開催されます。', $remindText);
    }
}
