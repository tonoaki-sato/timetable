<?php

namespace Tests\Feature;

use App\Models\AnnualSchedule;
use App\Models\Meeting;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
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
     * 未ログインユーザーはカレンダーにアクセスできずログイン画面へリダイレクトされること
     */
    public function test_calendar_requires_authentication(): void
    {
        $response = $this->get(route('calendar.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * カレンダー画面が表示され、年間スケジュール・会議・本番運行日が表示されること
     */
    public function test_calendar_displays_monthly_grid_and_events(): void
    {
        $this->actingAs($this->user);

        // 1. 年間スケジュール
        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '警察署協議（4月）',
            'category' => 'permit',
            'start_date' => '2026-04-15',
        ]);

        // 2. 会議（主催未定）
        Meeting::factory()->create([
            'title' => '第1回幹事会（4月）',
            'meeting_date' => '2026-04-20',
            'start_time' => '18:00',
            'staff_id' => null,
        ]);

        // 3. 本番運行日（タイムテーブル）
        Timetable::factory()->create([
            'name' => '本祭1日目 運行',
            'target_date' => '2026-04-25',
        ]);

        $response = $this->get(route('calendar.index', [
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertStatus(200);

        // カテゴリ表示
        $response->assertSee('会議・打合せ');
        $response->assertSee('申請・協議');
        $response->assertSee('募集・説明会');
        $response->assertSee('運行・設営');
        $response->assertSee('その他');

        // カレンダーグリッド内に予定・会議・運行日が表示されること
        $response->assertSee('警察署協議（4月）');
        $response->assertSee('第1回幹事会（4月）');
        $response->assertSee('本祭1日目 運行');
        $response->assertSee('主催未定');
    }

    /**
     * カテゴリやキーワードでカレンダー内イベントを絞り込めること
     */
    public function test_calendar_filters_by_category_and_search(): void
    {
        $this->actingAs($this->user);

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '出店者募集説明会',
            'category' => 'briefing',
            'start_date' => '2026-04-10',
        ]);

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '交通管理者現地視察',
            'category' => 'permit',
            'start_date' => '2026-04-12',
        ]);

        // カテゴリ絞り込み（briefingのみ）
        $catResponse = $this->get(route('calendar.index', [
            'year' => 2026,
            'month' => 4,
            'category' => 'briefing',
        ]));
        $catResponse->assertStatus(200);
        $catResponse->assertSee('出店者募集説明会');
        $catResponse->assertDontSee('交通管理者現地視察');

        // キーワード検索（「視察」のみ）
        $searchResponse = $this->get(route('calendar.index', [
            'year' => 2026,
            'month' => 4,
            'search' => '視察',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertDontSee('出店者募集説明会');
        $searchResponse->assertSee('交通管理者現地視察');
    }

    /**
     * 月送りリンクが正しく生成されていること
     */
    public function test_calendar_month_navigation_links(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('calendar.index', [
            'year' => 2026,
            'month' => 5,
        ]));

        $response->assertStatus(200);
        $response->assertSee('2026年 5月');
        $response->assertSee('4月');
        $response->assertSee('6月');
    }
}
