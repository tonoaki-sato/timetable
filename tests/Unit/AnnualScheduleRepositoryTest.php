<?php

namespace Tests\Unit;

use App\Models\AnnualSchedule;
use App\Models\User;
use App\Repositories\Eloquent\AnnualScheduleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualScheduleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AnnualScheduleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AnnualScheduleRepository;
    }

    /**
     * 条件付き一覧取得（ページネーション・絞り込み）が正常に動作すること
     */
    public function test_paginate_filtered_works(): void
    {
        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => 'パレード安全講習会',
            'category' => 'briefing',
            'status' => 'planned',
        ]);

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '実行委員会反省会',
            'category' => 'committee',
            'status' => 'completed',
        ]);

        AnnualSchedule::factory()->create([
            'target_year' => 2025,
            'title' => '過去年度イベント',
            'category' => 'briefing',
            'status' => 'completed',
        ]);

        // 2026年度のみ取得
        $results = $this->repository->paginateFiltered(2026);
        $this->assertSame(2, $results->total());

        // カテゴリ絞り込み
        $catResults = $this->repository->paginateFiltered(2026, category: 'committee');
        $this->assertSame(1, $catResults->total());
        $this->assertSame('実行委員会反省会', $catResults->items()[0]->title);

        // キーワード検索
        $searchResults = $this->repository->paginateFiltered(2026, search: '講習会');
        $this->assertSame(1, $searchResults->total());
        $this->assertSame('パレード安全講習会', $searchResults->items()[0]->title);
    }

    /**
     * 登録済み対象年度一覧が正しく取得でき、降順ソートされること
     */
    public function test_get_available_years(): void
    {
        AnnualSchedule::factory()->create(['target_year' => 2027]);
        AnnualSchedule::factory()->create(['target_year' => 2025]);

        $years = $this->repository->getAvailableYears();

        $this->assertContains(2027, $years);
        $this->assertContains(2026, $years);
        $this->assertContains(2025, $years);
        $this->assertSame(2027, $years[0]);
    }

    /**
     * 年度内ステータス別集計が正しく集計されること
     */
    public function test_get_year_status_summary(): void
    {
        AnnualSchedule::factory()->count(2)->create(['target_year' => 2026, 'status' => 'planned']);
        AnnualSchedule::factory()->count(3)->create(['target_year' => 2026, 'status' => 'completed']);
        AnnualSchedule::factory()->count(1)->create(['target_year' => 2026, 'status' => 'in_progress']);
        AnnualSchedule::factory()->create(['target_year' => 2025, 'status' => 'completed']);

        $summary = $this->repository->getYearStatusSummary(2026);

        $this->assertSame(6, $summary['total']);
        $this->assertSame(2, $summary['planned']);
        $this->assertSame(1, $summary['in_progress']);
        $this->assertSame(3, $summary['completed']);
    }

    /**
     * 作成・更新・削除が正常に動作すること
     */
    public function test_crud_operations(): void
    {
        $user = User::factory()->create();

        // 作成
        $schedule = $this->repository->create([
            'target_year' => 2026,
            'title' => '新規行事テスト',
            'category' => 'operation',
            'start_date' => '2026-09-01',
            'status' => 'planned',
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('annual_schedules', ['id' => $schedule->id, 'title' => '新規行事テスト']);

        // 更新
        $updated = $this->repository->update($schedule, ['title' => '更新後行事名']);
        $this->assertTrue($updated);
        $this->assertSame('更新後行事名', $schedule->fresh()->title);

        // 削除
        $deleted = $this->repository->delete($schedule);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('annual_schedules', ['id' => $schedule->id]);
    }
}
