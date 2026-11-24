<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaneTimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable;

    private TimetableLane $lane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '班別タイムライン検証日程',
        ]);

        $masterLane = Lane::factory()->create([
            'name' => '本部警備班',
        ]);

        $this->lane = TimetableLane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $masterLane->id,
        ]);
    }

    /**
     * 班別タイムラインへのアクセス制御とインデックスリダイレクトが正常に動作すること
     */
    public function test_lane_timeline_auth_and_index_redirect(): void
    {
        $this->get(route('lane-timeline.index'))->assertRedirect(route('login'));
        $this->get(route('timetables.lane-timeline', $this->timetable))->assertRedirect(route('login'));

        $this->actingAs($this->operator);
        $this->get(route('lane-timeline.index'))
            ->assertRedirect(route('timetables.lane-timeline', $this->timetable));
    }

    /**
     * 班別タイムラインに定常/スポットタスク2層、Viteスクリプト、各種モーダルDOMが正しくレンダリングされること
     */
    public function test_lane_timeline_renders_dual_tracks_assets_and_modals(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create(['name' => '担当班テスト 太郎', 'is_active' => true]);

        $routineTask = TimetableTask::factory()->routine()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '本部常駐',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'required_people_count' => 2,
        ]);
        $spotTask = TimetableTask::factory()->spot()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => 'セレモニー誘導',
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'required_people_count' => 3,
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $routineTask->id,
            'staff_id' => $staff->id,
        ]);

        $response = $this->get(route('timetables.lane-timeline', $this->timetable));
        $response->assertStatus(200);
        $response->assertSee('班別タイムライン');
        $response->assertSee('本部警備班');
        $response->assertSee('本部常駐');
        $response->assertSee('セレモニー誘導');
        $response->assertSee('lane-timeline-');
        $response->assertSee('id="task-create-modal"', false);
        $response->assertSee('id="task-edit-modal"', false);
        $response->assertSee('id="quickAssignModal"', false);
        $response->assertSee('id="laneBarActionModal"', false);
        $response->assertSee('data-left-zone="routine"', false);
        $response->assertSee('data-left-zone="spot"', false);
    }

    /**
     * 班別タイムラインからのJSON経由でのタスク作成・更新・削除が正常に動作すること
     */
    public function test_lane_timeline_json_crud_endpoints_work(): void
    {
        $this->actingAs($this->operator);

        $storeRes = $this->postJson(route('timetable-tasks.store'), [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '機材搬入スポット',
            'task_type' => 'spot',
            'start_time' => '08:30',
            'end_time' => '09:30',
            'required_people_count' => 2,
            'is_at_least' => 0,
        ]);
        $storeRes->assertStatus(201)->assertJson(['success' => true]);
        $taskId = $storeRes->json('task.id');

        $updateRes = $this->putJson(route('timetable-tasks.update', $taskId), [
            'timetable_lane_id' => $this->lane->id,
            'title' => '機材搬入スポット（時間変更）',
            'task_type' => 'spot',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'required_people_count' => 2,
            'is_at_least' => 0,
        ]);
        $updateRes->assertStatus(200)->assertJson(['success' => true]);
        $updatedTask = TimetableTask::findOrFail($taskId);
        $this->assertSame('09:00', substr((string) $updatedTask->start_time, 0, 5));
        $this->assertSame('10:30', substr((string) $updatedTask->end_time, 0, 5));

        $deleteRes = $this->deleteJson(route('timetable-tasks.destroy', $taskId));
        $deleteRes->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('timetable_tasks', ['id' => $taskId]);
    }
}
