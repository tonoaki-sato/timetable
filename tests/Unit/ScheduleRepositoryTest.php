<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Models\User;
use App\Repositories\Eloquent\ScheduleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ScheduleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ScheduleRepository;
    }

    /**
     * 条件付き一覧取得（ページネーション・絞り込み）が正常に動作すること
     */
    public function test_paginate_filtered_works(): void
    {
        Schedule::factory()->create([
            'title' => 'パレード安全講習会',
            'category' => 'briefing',
            'status' => 'planned',
        ]);

        Schedule::factory()->create([
            'title' => '実行委員会反省会',
            'category' => 'committee',
            'status' => 'completed',
        ]);

        // 全件取得
        $results = $this->repository->paginateFiltered();
        $this->assertSame(2, $results->total());

        // カテゴリ絞り込み
        $catResults = $this->repository->paginateFiltered(category: 'committee');
        $this->assertSame(1, $catResults->total());
        $this->assertSame('実行委員会反省会', $catResults->items()[0]->title);

        // キーワード検索
        $searchResults = $this->repository->paginateFiltered(search: '講習会');
        $this->assertSame(1, $searchResults->total());
        $this->assertSame('パレード安全講習会', $searchResults->items()[0]->title);
    }

    /**
     * 指定月のスケジュールがカレンダー用に取得できること
     */
    public function test_get_for_month_works(): void
    {
        Schedule::factory()->create([
            'title' => '2027年4月イベント',
            'start_date' => '2027-04-15',
        ]);

        Schedule::factory()->create([
            'title' => '2027年5月イベント',
            'start_date' => '2027-05-01',
        ]);

        $monthResults = $this->repository->getForMonth(2027, 4);
        $this->assertCount(1, $monthResults);
        $this->assertSame('2027年4月イベント', $monthResults->first()->title);
    }

    /**
     * 直近の会議予定が取得できること
     */
    public function test_get_upcoming_meetings(): void
    {
        // 未来の会議
        Schedule::factory()->create([
            'title' => '未来の幹事会',
            'category' => 'committee',
            'start_date' => now()->addDays(3)->toDateString(),
        ]);

        // 過去の会議
        Schedule::factory()->create([
            'title' => '過去の幹事会',
            'category' => 'committee',
            'start_date' => now()->subDays(3)->toDateString(),
        ]);

        // 未来の別カテゴリ
        Schedule::factory()->create([
            'title' => '未来の申請',
            'category' => 'permit',
            'start_date' => now()->addDays(5)->toDateString(),
        ]);

        $meetings = $this->repository->getUpcomingMeetings(3);
        $this->assertCount(1, $meetings);
        $this->assertSame('未来の幹事会', $meetings->first()->title);
    }

    /**
     * ステータス集計が正しく行われること
     */
    public function test_get_status_summary(): void
    {
        Schedule::factory()->count(2)->create(['status' => 'planned']);
        Schedule::factory()->count(3)->create(['status' => 'completed']);
        Schedule::factory()->count(1)->create(['status' => 'in_progress']);

        $summary = $this->repository->getStatusSummary();

        $this->assertSame(6, $summary['total']);
        $this->assertSame(2, $summary['planned']);
        $this->assertSame(3, $summary['completed']);
        $this->assertSame(1, $summary['in_progress']);
    }

    /**
     * 作成・更新・削除が正常に行えること
     */
    public function test_crud_methods_work(): void
    {
        $user = User::factory()->create();

        $schedule = $this->repository->create([
            'title' => '新規作成テスト',
            'category' => 'other',
            'start_date' => '2027-06-01',
            'status' => 'planned',
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Schedule::class, $schedule);
        $this->assertDatabaseHas('schedules', ['title' => '新規作成テスト']);

        $updated = $this->repository->update($schedule, ['title' => 'タイトル更新後']);
        $this->assertTrue($updated);
        $this->assertDatabaseHas('schedules', ['title' => 'タイトル更新後']);

        $deleted = $this->repository->delete($schedule);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }
}
