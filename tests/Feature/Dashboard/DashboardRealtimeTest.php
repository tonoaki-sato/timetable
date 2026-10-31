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

class DashboardRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_dashboard_realtime_endpoint(): void
    {
        $response = $this->getJson(route('dashboard.realtime'));

        $response->assertStatus(401);
    }

    public function test_dashboard_renders_realtime_containers_and_traffic_regulation_component(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 9, 40, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);
        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'status' => 'closed', 'label' => '通行止め'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('id="dashboard-realtime-root"', false);
        $response->assertSee('data-realtime-url="'.route('dashboard.realtime').'"', false);
        $response->assertSee('id="dashboard-card-traffic-regulation"', false);
        $response->assertSee('id="dashboard-card-now-tasks"', false);
        $response->assertSee('id="dashboard-card-next-tasks"', false);
        $response->assertSee('id="dashboard-card-parking-monitoring"', false);
        $response->assertSee('id="dashboard-manual-refresh"', false);
        $response->assertSee('10:00より車両通行止め (あと20分) / 路上本設置準備');
    }

    public function test_realtime_endpoint_returns_updated_html_fragments_as_time_and_task_status_change(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'status' => 'closed', 'label' => '通行止め'],
            ],
        ]);

        $leader = Staff::factory()->create(['name' => '佐藤 太郎']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'icon' => '🔺',
            'lead_staff_id' => $leader->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '10時開始の通行止めコーン一斉設置',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 2,
            'is_at_least' => false,
            'is_critical' => true,
            'status' => 'not_started',
        ]);

        $assignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $leader->id,
            'attendance_status' => 'assigned',
        ]);

        // 1. 09:45 時点: タスクは NEXT にあり、交通規制は「通行止め前（あと15分）」
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 9, 45, 0, 'Asia/Tokyo'));

        $resBefore = $this->actingAs($user)->getJson(route('dashboard.realtime'));
        $resBefore->assertStatus(200)
            ->assertJsonStructure([
                'server_time_ms',
                'updated_at_label',
                'cards' => [
                    'traffic_regulation',
                    'now_tasks',
                    'next_tasks',
                    'parking_monitoring',
                ],
            ]);

        $this->assertSame('09:45:00', $resBefore->json('updated_at_label'));
        $this->assertStringContainsString('10:00より車両通行止め (あと15分)', $resBefore->json('cards.traffic_regulation'));
        $this->assertStringNotContainsString('<a ', $resBefore->json('cards.traffic_regulation'));
        $this->assertStringContainsString('現在進行中のタスクはありません', $resBefore->json('cards.now_tasks'));
        $this->assertStringContainsString('タイムテーブルを確認', $resBefore->json('cards.now_tasks'));
        $this->assertStringContainsString('10時開始の通行止めコーン一斉設置', $resBefore->json('cards.next_tasks'));
        $this->assertStringContainsString('(直近10:00開始)', $resBefore->json('cards.next_tasks'));
        $this->assertStringContainsString('あと 15分後 開始', $resBefore->json('cards.next_tasks'));
        $this->assertStringContainsString('data-task-id="'.$task->id.'"', $resBefore->json('cards.next_tasks'));

        // 2. 10:05 時点: 時刻が10時を過ぎ、タスクが自動的に NEXT から NOW へ移動＆交通規制が「路上への本設置集中中」へ変化
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 10, 5, 0, 'Asia/Tokyo'));

        $resAfter = $this->actingAs($user)->getJson(route('dashboard.realtime'));
        $resAfter->assertStatus(200);

        $this->assertSame('10:05:00', $resAfter->json('updated_at_label'));
        $this->assertStringContainsString('路上への本設置集中中', $resAfter->json('cards.traffic_regulation'));
        $this->assertStringContainsString('11:00本番まであと55分', $resAfter->json('cards.traffic_regulation'));
        $this->assertStringContainsString('10時開始の通行止めコーン一斉設置', $resAfter->json('cards.now_tasks'));
        $this->assertStringContainsString('⚠️ 1 / 2名 (不足1名)', $resAfter->json('cards.now_tasks'));
        $this->assertStringContainsString('本日これ以降に開始予定のタスクはありません', $resAfter->json('cards.next_tasks'));

        // 3. スタッフが追加配置され、タスクが「進行中」に更新された場合、人員充足とステータスがリアルタイム反映される
        $secondStaff = Staff::factory()->create();
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $secondStaff->id,
            'attendance_status' => 'in_progress',
        ]);
        $assignment->update(['attendance_status' => 'in_progress']);
        $task->update(['status' => 'in_progress']);

        $resUpdated = $this->actingAs($user)->getJson(route('dashboard.realtime'));
        $resUpdated->assertStatus(200);
        $this->assertStringContainsString('👥 2 / 2名配置済', $resUpdated->json('cards.now_tasks'));
        $this->assertStringContainsString('進行中', $resUpdated->json('cards.now_tasks'));
    }
}
