<?php

namespace Tests\Unit\Services;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\NowTasksService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NowTasksServiceTest extends TestCase
{
    use RefreshDatabase;

    private NowTasksService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NowTasksService;
    }

    public function test_returns_non_main_event_day_context_when_no_timetable_for_today(): void
    {
        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $now = Carbon::create(2026, 10, 28, 10, 15, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertFalse($context->isMainEventDay);
        $this->assertNull($context->activeTimetable);
        $this->assertTrue($context->tasks->isEmpty());
        $this->assertSame('10:15', $context->currentTimeLabel);
        $this->assertSame(route('timetables.index'), $context->actionUrl);
    }

    public function test_extracts_in_progress_and_current_time_slot_not_started_tasks(): void
    {
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
        ]);

        // 1. 時間帯内の未着手タスク -> 抽出対象
        $taskInWindowNotStarted = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '時間内未着手タスク',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'not_started',
        ]);

        // 2. 時間帯外だが進行中のタスク -> 抽出対象
        $taskOutWindowInProgress = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '時間外進行中タスク',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'status' => 'in_progress',
        ]);

        // 3. 時間帯内だが完了済のタスク -> 除外
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '時間内完了済タスク',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'completed',
        ]);

        // 4. 時間帯内だが中止のタスク -> 除外
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '時間内中止タスク',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'cancelled',
        ]);

        // 5. 未来時間帯の未着手タスク -> 除外
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '未来未着手タスク',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'not_started',
        ]);

        // 6. 境界時刻（10:15終了ちょうど）の未着手タスク -> 除外
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '終了境界未着手タスク',
            'start_time' => '09:30:00',
            'end_time' => '10:15:00',
            'status' => 'not_started',
        ]);

        $now = Carbon::create(2026, 10, 11, 10, 15, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertTrue($context->isMainEventDay);
        $this->assertSame($timetable->id, $context->activeTimetable?->id);
        $this->assertCount(2, $context->tasks);

        $extractedIds = $context->tasks->pluck('id')->all();
        $this->assertSame([
            $taskOutWindowInProgress->id,
            $taskInWindowNotStarted->id,
        ], $extractedIds);
    }

    public function test_sorts_tasks_by_critical_then_in_progress_then_start_time(): void
    {
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
        ]);

        $normalNotStartedLate = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '通常・未着手・遅い開始',
            'start_time' => '10:10:00',
            'end_time' => '11:00:00',
            'is_critical' => false,
            'status' => 'not_started',
        ]);

        $normalNotStartedEarly = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '通常・未着手・早い開始',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_critical' => false,
            'status' => 'not_started',
        ]);

        $normalInProgress = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '通常・進行中',
            'start_time' => '10:05:00',
            'end_time' => '11:00:00',
            'is_critical' => false,
            'status' => 'in_progress',
        ]);

        $criticalNotStarted = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '最優先・未着手',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_critical' => true,
            'status' => 'not_started',
        ]);

        $criticalInProgress = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '最優先・進行中',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_critical' => true,
            'status' => 'in_progress',
        ]);

        $now = Carbon::create(2026, 10, 11, 10, 15, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertSame([
            $criticalInProgress->id,
            $criticalNotStarted->id,
            $normalInProgress->id,
            $normalNotStartedEarly->id,
            $normalNotStartedLate->id,
        ], $context->tasks->pluck('id')->all());
    }
}
