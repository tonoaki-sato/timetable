<?php

namespace Tests\Feature;

use App\Models\AnnualSchedule;
use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualScheduleManagementTest extends TestCase
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
     * 年間スケジュール一覧が表示され、年度・ステータス等で絞り込みできること
     */
    public function test_index_displays_schedules_and_filters(): void
    {
        $this->actingAs($this->user);

        $schedule2026 = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '2026年本祭パレード',
            'category' => 'operation',
            'status' => 'planned',
        ]);

        $schedule2025 = AnnualSchedule::factory()->create([
            'target_year' => 2025,
            'title' => '2025年旧年度イベント',
            'category' => 'committee',
            'status' => 'completed',
        ]);

        // 2026年度のみ表示されること
        $response = $this->get(route('annual-schedules.index', ['year' => 2026]));
        $response->assertStatus(200);
        $response->assertSee('2026年本祭パレード');
        $response->assertDontSee('2025年旧年度イベント');

        // カテゴリ絞り込み
        $catResponse = $this->get(route('annual-schedules.index', ['year' => 2026, 'category' => 'committee']));
        $catResponse->assertStatus(200);
        $catResponse->assertDontSee('2026年本祭パレード');

        // キーワード検索
        $searchResponse = $this->get(route('annual-schedules.index', ['year' => 2026, 'search' => 'パレード']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('2026年本祭パレード');
    }

    /**
     * 正しいデータで新規登録できること
     */
    public function test_can_create_annual_schedule(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => '資機材一斉点検・整備',
            'category' => 'operation',
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-18',
            'location' => '第1倉庫',
            'status' => 'planned',
            'description' => '関係者一同参列のこと。',
        ]);

        $response->assertRedirect(route('annual-schedules.index', ['year' => 2026]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('annual_schedules', [
            'title' => '資機材一斉点検・整備',
            'target_year' => 2026,
            'category' => 'operation',
            'location' => '第1倉庫',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * バリデーションエラーが正しく機能すること
     */
    public function test_validation_rules_for_creating_schedule(): void
    {
        $this->actingAs($this->user);

        // 必須項目未入力
        $response = $this->post(route('annual-schedules.store'), []);
        $response->assertSessionHasErrors(['target_year', 'title', 'category', 'start_date', 'status']);

        // 終了日が開始日より前の場合
        $invalidDateResponse = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => '日付逆転テスト',
            'category' => 'committee',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-10',
            'status' => 'planned',
        ]);
        $invalidDateResponse->assertSessionHasErrors('end_date');
    }

    /**
     * スケジュールを正常に更新できること
     */
    public function test_can_update_annual_schedule(): void
    {
        $this->actingAs($this->user);

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '変更前タイトル',
            'category' => 'committee',
            'start_date' => '2026-05-10',
            'status' => 'planned',
        ]);

        $response = $this->put(route('annual-schedules.update', $schedule), [
            'target_year' => 2026,
            'title' => '変更後新タイトル',
            'category' => 'operation',
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-16',
            'location' => '市民会館2F',
            'status' => 'completed',
            'description' => '議事録確認済み',
        ]);

        $response->assertRedirect(route('annual-schedules.show', $schedule));
        $response->assertSessionHas('success');

        $schedule->refresh();
        $this->assertSame('変更後新タイトル', $schedule->title);
        $this->assertSame('operation', $schedule->category);
        $this->assertSame('completed', $schedule->status);
        $this->assertSame('市民会館2F', $schedule->location);
    }

    /**
     * スケジュールを正常に削除できること
     */
    public function test_can_delete_annual_schedule(): void
    {
        $this->actingAs($this->user);

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '削除対象行事',
        ]);

        $response = $this->delete(route('annual-schedules.destroy', $schedule));

        $response->assertRedirect(route('annual-schedules.index', ['year' => 2026]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('annual_schedules', [
            'id' => $schedule->id,
        ]);
    }

    /**
     * 担当スタッフを指定して新規登録できること
     */
    public function test_can_create_annual_schedule_with_assignee(): void
    {
        $this->actingAs($this->user);

        $staff = Staff::factory()->create([
            'name' => '漆原 拓也',
            'affiliation' => 'まつり運行本部',
        ]);

        $response = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => 'ござ市出店者事前説明会',
            'category' => 'briefing',
            'start_date' => '2026-07-20',
            'status' => 'planned',
            'staff_id' => $staff->id,
        ]);

        $response->assertRedirect(route('annual-schedules.index', ['year' => 2026]));
        $this->assertDatabaseHas('annual_schedules', [
            'title' => 'ござ市出店者事前説明会',
            'staff_id' => $staff->id,
        ]);
    }

    /**
     * 担当スタッフを変更・解除できること
     */
    public function test_can_update_annual_schedule_assignee(): void
    {
        $this->actingAs($this->user);

        $staff1 = Staff::factory()->create(['name' => '担当者A']);
        $staff2 = Staff::factory()->create(['name' => '担当者B']);

        $schedule = AnnualSchedule::factory()->create([
            'staff_id' => $staff1->id,
        ]);

        // 担当者をstaff2に変更
        $this->put(route('annual-schedules.update', $schedule), [
            'target_year' => $schedule->target_year,
            'title' => $schedule->title,
            'category' => $schedule->category,
            'start_date' => $schedule->start_date->format('Y-m-d'),
            'status' => $schedule->status,
            'staff_id' => $staff2->id,
        ]);

        $schedule->refresh();
        $this->assertSame($staff2->id, $schedule->staff_id);

        // 担当者を未指定（null）に解除
        $this->put(route('annual-schedules.update', $schedule), [
            'target_year' => $schedule->target_year,
            'title' => $schedule->title,
            'category' => $schedule->category,
            'start_date' => $schedule->start_date->format('Y-m-d'),
            'status' => $schedule->status,
            'staff_id' => null,
        ]);

        $schedule->refresh();
        $this->assertNull($schedule->staff_id);
    }

    /**
     * 担当スタッフによる一覧絞り込みができること
     */
    public function test_filter_by_assignee(): void
    {
        $this->actingAs($this->user);

        $staffA = Staff::factory()->create(['name' => '漆原 拓也']);
        $staffB = Staff::factory()->create(['name' => '有江 浩二']);

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '漆原担当タスク',
            'staff_id' => $staffA->id,
        ]);

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '有江担当タスク',
            'staff_id' => $staffB->id,
        ]);

        $response = $this->get(route('annual-schedules.index', [
            'year' => 2026,
            'staff_id' => $staffA->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('漆原担当タスク');
        $response->assertDontSee('有江担当タスク');
    }

    /**
     * 担当スタッフ名でのキーワード検索ができること
     */
    public function test_search_by_assignee_name(): void
    {
        $this->actingAs($this->user);

        $staff = Staff::factory()->create([
            'name' => '宮永 健一',
            'kana' => 'ミヤナガ ケンイチ',
        ]);

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '設備定期点検',
            'staff_id' => $staff->id,
        ]);

        $response = $this->get(route('annual-schedules.index', [
            'year' => 2026,
            'search' => '宮永',
        ]));

        $response->assertStatus(200);
        $response->assertSee('設備定期点検');
    }

    /**
     * 担当スタッフが削除された時に staff_id が null になること
     */
    public function test_assignee_null_on_delete(): void
    {
        $staff = Staff::factory()->create();
        $schedule = AnnualSchedule::factory()->create([
            'staff_id' => $staff->id,
        ]);

        $staff->delete();

        $schedule->refresh();
        $this->assertNull($schedule->staff_id);
    }

    /**
     * カレンダー表示で予定と会議が表示され、D&Dパレットは非表示であり、神事・式典が含まれないこと
     */
    public function test_calendar_view_displays_events(): void
    {
        $this->actingAs($this->user);

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '警察署協議（4月）',
            'category' => 'permit',
            'start_date' => '2026-04-15',
        ]);

        $meeting = Meeting::factory()->create([
            'title' => '第1回幹事会（4月）',
            'meeting_date' => '2026-04-20',
            'start_time' => '18:00',
            'staff_id' => null,
        ]);

        $response = $this->get(route('annual-schedules.index', [
            'view' => 'calendar',
            'year' => 2026,
            'month' => 4,
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('ドラッグ＆ドロップ パレット');
        $response->assertSee('会議・打合せ');
        $response->assertSee('申請・協議');
        $response->assertSee('募集・説明会');
        $response->assertSee('運行・設営');
        $response->assertSee('その他');
        // 神事・式典が含まれないこと
        $response->assertDontSee('神事・式典');

        // カレンダー上に予定と会議が表示されること
        $response->assertSee('警察署協議（4月）');
        $response->assertSee('第1回幹事会（4月）');
        $response->assertSee('主催未定');
    }
}
