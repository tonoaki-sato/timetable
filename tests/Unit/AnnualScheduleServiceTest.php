<?php

namespace Tests\Unit;

use App\Models\AnnualSchedule;
use App\Models\User;
use App\Repositories\Contracts\AnnualScheduleRepositoryInterface;
use App\Services\AnnualScheduleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class AnnualScheduleServiceTest extends TestCase
{
    /**
     * getIndexViewData がリポジトリのメソッドを呼び出し、整形した画面データを返すこと
     */
    public function test_get_index_view_data(): void
    {
        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);

        $mockRepo = Mockery::mock(AnnualScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('getAvailableYears')
            ->once()
            ->andReturn([2026, 2025]);

        $mockRepo->shouldReceive('paginateFiltered')
            ->once()
            ->with(2026, 'committee', 'planned', '検索語')
            ->andReturn($mockPaginator);

        $mockRepo->shouldReceive('getYearStatusSummary')
            ->once()
            ->with(2026)
            ->andReturn(['total' => 5, 'planned' => 2, 'in_progress' => 1, 'completed' => 2]);

        $service = new AnnualScheduleService($mockRepo);

        $result = $service->getIndexViewData(2026, 'committee', 'planned', '検索語');

        $this->assertSame(2026, $result['selectedYear']);
        $this->assertSame([2026, 2025], $result['availableYears']);
        $this->assertSame($mockPaginator, $result['schedules']);
        $this->assertSame(5, $result['yearSummary']['total']);
        $this->assertArrayHasKey('committee', $result['categories']);
        $this->assertArrayHasKey('planned', $result['statuses']);
    }

    /**
     * createSchedule がユーザーIDを作成者としてセットしてリポジトリに委譲すること
     */
    public function test_create_schedule(): void
    {
        $user = new User;
        $user->id = 42;

        $inputData = [
            'target_year' => 2026,
            'title' => 'テスト行事',
        ];

        $expectedCreatedSchedule = new AnnualSchedule($inputData + ['created_by' => 42]);

        $mockRepo = Mockery::mock(AnnualScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($attributes) {
                return $attributes['created_by'] === 42 && $attributes['title'] === 'テスト行事';
            }))
            ->andReturn($expectedCreatedSchedule);

        $service = new AnnualScheduleService($mockRepo);

        $result = $service->createSchedule($inputData, $user);

        $this->assertSame($expectedCreatedSchedule, $result);
    }

    /**
     * updateSchedule がリポジトリに委譲すること
     */
    public function test_update_schedule(): void
    {
        $schedule = new AnnualSchedule;
        $data = ['title' => '新タイトル'];

        $mockRepo = Mockery::mock(AnnualScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('update')
            ->once()
            ->with($schedule, $data)
            ->andReturn(true);

        $service = new AnnualScheduleService($mockRepo);

        $this->assertTrue($service->updateSchedule($schedule, $data));
    }

    /**
     * deleteSchedule がリポジトリに委譲すること
     */
    public function test_delete_schedule(): void
    {
        $schedule = new AnnualSchedule;

        $mockRepo = Mockery::mock(AnnualScheduleRepositoryInterface::class);
        $mockRepo->shouldReceive('delete')
            ->once()
            ->with($schedule)
            ->andReturn(true);

        $service = new AnnualScheduleService($mockRepo);

        $this->assertTrue($service->deleteSchedule($schedule));
    }
}
