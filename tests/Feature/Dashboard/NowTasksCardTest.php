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

class NowTasksCardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_renders_non_event_day_empty_state_without_dummy_tasks_on_normal_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 28, 10, 30, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('⚡ NOW : 現在進行中のタスク');
        $response->assertSee('本日の運行予定なし');
        $response->assertSee('本日は運行予定日が設定されていません');
        $response->assertDontSee('通行止め出入り口・支道接続点 最優先コーン設置');
    }

    public function test_renders_event_day_empty_state_when_no_tasks_are_active_at_current_time(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 6, 30, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '06時完了済の早朝準備タスク',
            'start_time' => '06:00:00',
            'end_time' => '06:15:00',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('06:30 時点');
        $response->assertSee('現在進行中のタスクはありません');
        $response->assertSee('タイムテーブルを確認');
        $response->assertDontSee('運行タイムテーブルマトリクスを確認');
        $response->assertDontSee('06時完了済の早朝準備タスク');
    }

    public function test_renders_active_now_tasks_with_badges_lane_leader_and_staff_shortage_on_event_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 10, 15, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $leader = Staff::factory()->create(['name' => '漆原 功']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'icon' => '🔺',
            'lead_staff_id' => $leader->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '支道接続点カラーコーン本設置',
            'location' => '税務署前交差点',
            'content' => '通行止め開始後速やかにコーンを設置',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 3,
            'is_at_least' => false,
            'is_critical' => true,
            'requires_review' => true,
            'status' => 'in_progress',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $leader->id,
            'attendance_status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('1件');
        $response->assertSee('10:15 時点');
        $response->assertSee('10:00 - 11:00');
        $response->assertSee('🚨 最優先設置');
        $response->assertSee('🔍 要確認・立会');
        $response->assertSee('🔺 カラーコーン (漆原 功)');
        $response->assertSee('支道接続点カラーコーン本設置');
        $response->assertSee('税務署前交差点');
        $response->assertSee('通行止め開始後速やかにコーンを設置');
        $response->assertSee('⚠️ 1 / 3名 (不足2名)');
        $response->assertSee('進行中');
    }
}
