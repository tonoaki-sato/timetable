<?php

namespace Tests\Feature\Dashboard;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextTasksCardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_renders_non_event_day_empty_state_without_dummy_next_tasks(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 25, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('NEXT : 次に控えるタスク');
        $response->assertDontSee('ゴミ箱チェック（圧縮・袋交換）');
        $response->assertDontSee('給水設備 定期点検 (第1回)');
    }

    public function test_renders_event_day_empty_state_when_no_upcoming_tasks_remain(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 19, 45, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
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

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('以降の予定タスクなし');
        $response->assertSee('本日これ以降に開始予定のタスクはありません');
    }

    public function test_renders_upcoming_next_tasks_with_countdown_lane_leader_and_shortage_alert(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 10, 25, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
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
            'requires_review' => true,
            'status' => 'not_started',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $leader->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('(11:00開始)');
        $response->assertSee('あと 35分後 開始');
        $response->assertSee('11:00 - 12:00');
        $response->assertSee('🚻 衛生 (有エ 喜一郎)');
        $response->assertSee('🚨 最優先');
        $response->assertSee('🔍 要確認・立会');
        $response->assertSee('北川製粉所横ゴミステーション巡回回収');
        $response->assertSee('📍 北川製粉所横');
        $response->assertSee('⚠️ 1/4名以上 (不足3名)');
    }
}

