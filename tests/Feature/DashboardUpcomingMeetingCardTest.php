<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUpcomingMeetingCardTest extends TestCase
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
     * 未来の会議が最大3件まで開催日順に表示され、4件目は除外されること
     */
    public function test_upcoming_meetings_card_renders_up_to_3_meetings_in_chronological_order(): void
    {
        $this->actingAs($this->user);

        // 4件の未来会議を作成
        $meeting1 = Meeting::factory()->create([
            'title' => '第1回 幹事会',
            'meeting_date' => Carbon::now('Asia/Tokyo')->addDays(1)->toDateString(),
            'start_time' => '18:00:00',
            'staff_id' => $this->staff->id,
            'location' => '商工会議所',
        ]);

        $meeting2 = Meeting::factory()->create([
            'title' => '第2回 幹事会',
            'meeting_date' => Carbon::now('Asia/Tokyo')->addDays(3)->toDateString(),
            'start_time' => '19:00:00',
            'staff_id' => $this->staff->id,
            'location' => '本部テント',
        ]);

        $meeting3 = Meeting::factory()->create([
            'title' => '第3回 幹事会',
            'meeting_date' => Carbon::now('Asia/Tokyo')->addDays(5)->toDateString(),
            'start_time' => '14:00:00',
            'staff_id' => $this->staff->id,
            'location' => '公民館',
        ]);

        $meeting4 = Meeting::factory()->create([
            'title' => '第4回 幹事会（遠い未来）',
            'meeting_date' => Carbon::now('Asia/Tokyo')->addDays(10)->toDateString(),
            'start_time' => '15:00:00',
            'staff_id' => $this->staff->id,
            'location' => '市民ホール',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('直近の会議・打合せ');
        $response->assertSee('第1回 幹事会');
        $response->assertSee('第2回 幹事会');
        $response->assertSee('第3回 幹事会');
        $response->assertDontSee('第4回 幹事会（遠い未来）');

        // 主催者情報と場所が表示されていること
        $response->assertSee('漆原 拓也');
        $response->assertSee('商工会議所');
    }

    /**
     * 過去の会議は直近カードに表示されないこと
     */
    public function test_upcoming_meetings_card_excludes_past_meetings(): void
    {
        $this->actingAs($this->user);

        $pastMeeting = Meeting::factory()->create([
            'title' => '先週の反省会',
            'meeting_date' => Carbon::now('Asia/Tokyo')->subDays(3)->toDateString(),
            'staff_id' => $this->staff->id,
        ]);

        $upcomingMeeting = Meeting::factory()->create([
            'title' => '明日の打合せ',
            'meeting_date' => Carbon::now('Asia/Tokyo')->addDays(1)->toDateString(),
            'staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('明日の打合せ');
        $response->assertDontSee('先週の反省会');
    }

    /**
     * 会議予定が0件の場合に空状態のメッセージが表示されること
     */
    public function test_upcoming_meetings_card_renders_empty_state_when_no_upcoming_meetings(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('直近の会議・打合せ');
        $response->assertSee('予定されている会議はありません');
        $response->assertSee('定例幹事会や部会打ち合わせを登録できます');
    }

    /**
     * 当日開催の会議に「本日開催」バッジが表示されること
     */
    public function test_upcoming_meetings_card_displays_today_badge_for_today_meeting(): void
    {
        $this->actingAs($this->user);

        Meeting::factory()->create([
            'title' => '本日の緊急調整会議',
            'meeting_date' => Carbon::now('Asia/Tokyo')->toDateString(),
            'staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('本日の緊急調整会議');
        $response->assertSee('本日開催');
    }

    /**
     * 3日以内の期日の会議に「あとX日」バッジが表示されること
     */
    public function test_upcoming_meetings_card_displays_days_remaining_badge_within_3_days(): void
    {
        $this->actingAs($this->user);

        Meeting::factory()->create([
            'title' => '2日後の準備会',
            'meeting_date' => Carbon::now('Asia/Tokyo')->addDays(2)->toDateString(),
            'staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('2日後の準備会');
        $response->assertSee('あと2日');
    }
}
