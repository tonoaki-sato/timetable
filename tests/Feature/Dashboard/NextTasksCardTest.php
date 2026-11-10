<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\NextTasksService;
use App\View\Components\Dashboard\NextTasksCard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class NextTasksCardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function renderCard(int $limit = 5): string
    {
        return Blade::renderComponent(new NextTasksCard(app(NextTasksService::class), $limit));
    }

    public function test_renders_non_event_day_empty_state_without_dummy_next_tasks(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 25, 0, 'Asia/Tokyo'));

        $html = $this->renderCard();

        $this->assertStringContainsString('NEXT : 次に控えるタスク', $html);
        $this->assertStringNotContainsString('ゴミ箱チェック（圧縮・袋交換）', $html);
        $this->assertStringNotContainsString('給水設備 定期点検 (第1回)', $html);
    }

    public function test_renders_event_day_empty_state_when_no_upcoming_tasks_remain(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 19, 45, 0, 'Asia/Tokyo'));

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '終了済の朝礼タスク',
            'start_time' => '07:00:00',
            'end_time' => '07:30:00',
            'status' => 'completed',
        ]);

        $html = $this->renderCard();

        $this->assertStringContainsString('以降の予定タスクなし', $html);
        $this->assertStringContainsString('本日これ以降に開始予定のタスクはありません', $html);
    }

    public function test_renders_upcoming_next_tasks_with_countdown_lane_leader_and_shortage_alert(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 10, 25, 0, 'Asia/Tokyo'));

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $leader = Staff::factory()->create(['name' => '有エ 喜一郎']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'icon' => '🚻',
            'lead_staff_id' => $leader->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '北川製粉所横ゴミステーション巡回回収',
            'location' => '北川製粉所横',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'required_people_count' => 4,
            'is_at_least' => true,
            'is_critical' => true,
            'status' => 'not_started',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $leader->id,
            'attendance_status' => 'assigned',
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '午後開始の巡回タスク',
            'location' => '宿場通り中央',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'required_people_count' => 2,
            'is_at_least' => false,
            'is_critical' => false,
            'status' => 'not_started',
        ]);

        $html = $this->renderCard();

        $this->assertStringContainsString('(直近11:00開始)', $html);
        $this->assertStringContainsString('あと 35分後 開始', $html);
        $this->assertStringContainsString('あと 1時間35分後 開始', $html);
        $this->assertStringContainsString('11:00 - 12:00', $html);
        $this->assertStringContainsString('12:00 - 13:00', $html);
        $this->assertStringContainsString('🚻 衛生 (有エ 喜一郎)', $html);
        $this->assertStringContainsString('🚨 最優先', $html);
        $this->assertStringNotContainsString('要確認・立会', $html);
        $this->assertStringContainsString('北川製粉所横ゴミステーション巡回回収', $html);
        $this->assertStringContainsString('午後開始の巡回タスク', $html);
        $this->assertStringContainsString('📍 北川製粉所横', $html);
        $this->assertStringContainsString('⚠️ 1/4名以上 (不足3名)', $html);
    }
}
