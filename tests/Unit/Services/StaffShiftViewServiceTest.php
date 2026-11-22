<?php

namespace Tests\Unit\Services;

use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Services\StaffShiftViewService;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\Stub;
use Tests\TestCase;

class StaffShiftViewServiceTest extends TestCase
{
    private StaffShiftViewService $service;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var StaffRepositoryInterface&Stub $staffRepository */
        $staffRepository = $this->createStub(StaffRepositoryInterface::class);
        $this->service = new StaffShiftViewService($staffRepository);
    }

    public function test_resolve_now_and_next_tasks_identifies_current_and_next_for_today(): void
    {
        $now = Carbon::create(2026, 10, 11, 11, 15, 0, 'Asia/Tokyo');
        $todayTimetable = new Timetable(['target_date' => '2026-10-11']);

        $pastAssignment = $this->makeAssignment($todayTimetable, '09:00:00', '10:00:00', '朝礼');
        $currentAssignment = $this->makeAssignment($todayTimetable, '11:00:00', '12:00:00', '巡回');
        $nextAssignment = $this->makeAssignment($todayTimetable, '13:00:00', '14:00:00', '片付け');
        $laterAssignment = $this->makeAssignment($todayTimetable, '15:00:00', '16:00:00', '報告');

        $resolved = $this->service->resolveNowAndNextTasks(
            [$pastAssignment, $currentAssignment, $nextAssignment, $laterAssignment],
            $now
        );

        $this->assertSame($currentAssignment, $resolved['current_task']);
        $this->assertSame($nextAssignment, $resolved['next_task']);
    }

    public function test_resolve_now_and_next_tasks_falls_back_to_first_future_day_task_when_none_today(): void
    {
        $now = Carbon::create(2026, 10, 10, 15, 0, 0, 'Asia/Tokyo');
        $futureDay1 = new Timetable(['target_date' => '2026-10-11']);
        $futureDay2 = new Timetable(['target_date' => '2026-10-12']);

        $orphanAssignment = new TaskStaffAssignment;
        $firstFutureAssignment = $this->makeAssignment($futureDay1, '09:00:00', '10:00:00', '初日搬入');
        $secondFutureAssignment = $this->makeAssignment($futureDay2, '09:00:00', '10:00:00', '二日目設営');

        $resolved = $this->service->resolveNowAndNextTasks(
            [$orphanAssignment, $firstFutureAssignment, $secondFutureAssignment],
            $now
        );

        $this->assertNull($resolved['current_task']);
        $this->assertSame($firstFutureAssignment, $resolved['next_task']);
    }

    private function makeAssignment(Timetable $timetable, string $startTime, ?string $endTime, string $title): TaskStaffAssignment
    {
        $task = new TimetableTask([
            'title' => $title,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
        $task->setRelation('timetable', $timetable);

        $assignment = new TaskStaffAssignment;
        $assignment->setRelation('task', $task);

        return $assignment;
    }
}
