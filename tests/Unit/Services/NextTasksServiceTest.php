<?php

namespace Tests\Unit\Services;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\NextTasksService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextTasksServiceTest extends TestCase
{
    use RefreshDatabase;

    private NextTasksService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NextTasksService;
    }

    public function test_returns_non_main_event_day_context_when_no_timetable_for_today(): void
    {
        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $now = Carbon::create(2026, 10, 28, 10, 25, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertFalse($context->isMainEventDay);
        $this->assertNull($context->activeTimetable);
        $this->assertTrue($context->tasks->isEmpty());
        $this->assertSame(0, $context->totalNextCount);
        $this->assertNull($context->nextStartTimeLabel);
        $this->assertNull($context->remainingTimeLabel);
        $this->assertSame(route('timetables.index'), $context->actionUrl);
    }

    public function test_extracts_only_future_not_started_tasks_sorted_and_calculates_countdown(): void
    {
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
        ]);

        // 1. 現在進行中・開始時刻過去のタスク -> 除外
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '現在時間帯タスク',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'not_started',
        ]);

        // 2. 開始時刻が未来だが進行中のタスク -> 除外（NOW側で表示）
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '先行着手タスク',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'in_progress',
        ]);

        // 3. 未来時間帯だが完了済のタスク -> 除外
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '完了済タスク',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
        ]);

        // 4. 11:00開始・通常タスク -> 抽出（2番目）
        $task11Normal = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '11時通常タスク',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'is_critical' => false,
            'status' => 'not_started',
        ]);

        // 5. 11:00開始・最優先タスク -> 抽出（1番目）
        $task11Critical = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '11時最優先タスク',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'is_critical' => true,
            'status' => 'not_started',
        ]);

        // 6. 12:00開始・最優先タスク -> 抽出（3番目）
        $task12Critical = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '12時最優先タスク',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'is_critical' => true,
            'status' => 'not_started',
        ]);

        $now = Carbon::create(2026, 10, 11, 10, 25, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertTrue($context->isMainEventDay);
        $this->assertSame(3, $context->totalNextCount);
        $this->assertSame('11:00', $context->nextStartTimeLabel);
        $this->assertSame('あと 35分後 開始', $context->remainingTimeLabel);
        $this->assertSame([
            $task11Critical->id,
            $task11Normal->id,
            $task12Critical->id,
        ], $context->tasks->pluck('id')->all());
    }

    public function test_limits_tasks_to_default_six_and_formats_multi_hour_countdown(): void
    {
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
        ]);

        for ($i = 1; $i <= 8; $i++) {
            TimetableTask::factory()->create([
                'timetable_id' => $timetable->id,
                'lane_id' => $lane->id,
                'title' => "タスク{$i}",
                'start_time' => '12:00:00',
                'end_time' => '13:00:00',
                'status' => 'not_started',
            ]);
        }

        $now = Carbon::create(2026, 10, 11, 10, 15, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertSame(8, $context->totalNextCount);
        $this->assertCount(6, $context->tasks);
        $this->assertSame('12:00', $context->nextStartTimeLabel);
        $this->assertSame('あと 1時間45分後 開始', $context->remainingTimeLabel);
    }
}

