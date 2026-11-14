<?php

namespace Tests\Unit;

use App\Models\Schedule;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Services\ScheduleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * getIndexViewData がリポジトリのメソッドを呼び出し、整形した画面データを返すこと
     */
    public function test_get_index_view_data(): void
    {
        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $mockRepo = Mockery::mock(ScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('paginateFiltered')
            ->once()
            ->with(null, 'planned', '検索語', null, 'upcoming', 15)
            ->andReturn($mockPaginator);

        $mockRepo->shouldReceive('getStatusSummary')
            ->once()
            ->andReturn(['total' => 5, 'planned' => 2, 'in_progress' => 1, 'completed' => 2]);

        $service = new ScheduleService($mockRepo);

        $result = $service->getIndexViewData(
            category: 'committee',
            status: 'planned',
            search: '検索語',
            tab: 'upcoming'
        );

        $this->assertSame($mockPaginator, $result['schedules']);
        $this->assertSame(5, $result['statusSummary']['total']);
        $this->assertArrayHasKey('committee', $result['categories']);
        $this->assertArrayHasKey('planned', $result['statuses']);
        $this->assertArrayHasKey('staffs', $result);
        $this->assertSame(['committee'], $result['selectedCategories']);
        $this->assertArrayHasKey('timetable', $result['displayCategories']);
        $this->assertSame('committee', $result['currentCategory']);
        $this->assertSame('planned', $result['currentStatus']);
        $this->assertSame('検索語', $result['currentSearch']);
        $this->assertSame('upcoming', $result['currentTab']);
    }

    /**
     * createSchedule がユーザーIDを作成者としてセットしてリポジトリに委譲すること
     */
    public function test_create_schedule(): void
    {
        $user = new User;
        $user->id = 42;

        $inputData = [
            'title' => 'テスト予定',
            'category' => 'other',
            'start_date' => '2027-01-01',
            'status' => 'planned',
        ];

        $expectedCreatedSchedule = new Schedule($inputData + ['created_by' => 42]);

        $mockRepo = Mockery::mock(ScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('create')
            ->once()
            ->with($inputData + ['created_by' => 42])
            ->andReturn($expectedCreatedSchedule);

        $service = new ScheduleService($mockRepo);

        $created = $service->createSchedule($inputData, $user);

        $this->assertSame($expectedCreatedSchedule, $created);
    }

    /**
     * updateSchedule がリポジトリの更新メソッドを呼び出すこと
     */
    public function test_update_schedule(): void
    {
        $schedule = new Schedule;

        $mockRepo = Mockery::mock(ScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('update')
            ->once()
            ->with($schedule, ['title' => '更新'])
            ->andReturn(true);

        $service = new ScheduleService($mockRepo);

        $this->assertTrue($service->updateSchedule($schedule, ['title' => '更新']));
    }

    /**
     * deleteSchedule がリポジトリの削除メソッドを呼び出すこと
     */
    public function test_delete_schedule(): void
    {
        $schedule = new Schedule;

        $mockRepo = Mockery::mock(ScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('delete')
            ->once()
            ->with($schedule)
            ->andReturn(true);

        $service = new ScheduleService($mockRepo);

        $this->assertTrue($service->deleteSchedule($schedule));
    }

    /**
     * getPdfViewData がリポジトリの getFilteredForPdf を呼び出し、整形したPDFデータを返すこと
     */
    public function test_get_pdf_view_data(): void
    {
        $mockCollection = new Collection([
            new Schedule(['title' => '幹事会']),
        ]);

        $mockRepo = Mockery::mock(ScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('getFilteredForPdf')
            ->once()
            ->with(['committee'], 'planned', '検索語', 5, 'upcoming')
            ->andReturn($mockCollection);

        $service = new ScheduleService($mockRepo);

        $result = $service->getPdfViewData(
            category: 'committee',
            status: 'planned',
            search: '検索語',
            staffId: 5,
            tab: 'upcoming'
        );

        $this->assertSame('保土ケ谷宿場まつり実行委員会 年間スケジュール一覧', $result['title']);
        $this->assertSame($mockCollection, $result['schedules']);
        $this->assertStringContainsString('対象期間: 今後の予定', $result['subtitle']);
        $this->assertStringContainsString('区分: 会議・打ち合わせ', $result['subtitle']);
        $this->assertStringContainsString('状態: 予定', $result['subtitle']);
        $this->assertStringContainsString('検索: 「検索語」', $result['subtitle']);
        $this->assertNotEmpty($result['generatedAt']);
    }
}
