<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableAssignmentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Timetable $timetable;

    protected Lane $lane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->timetable = Timetable::factory()->create([
            'target_date' => '2026-10-25',
            'status' => 'active',
        ]);
        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '本部巡回班',
        ]);
    }

    /**
     * 未ログインユーザーはログイン画面へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('timetables.assignments', $this->timetable));
        $response->assertRedirect(route('login'));
    }

    /**
     * ログインユーザーは人員アサインワークスペース画面を正常に閲覧できること
     */
    public function test_authenticated_user_can_view_assignment_workspace(): void
    {
        $staff1 = Staff::factory()->create(['name' => '佐藤 太郎', 'is_active' => true]);
        $staff2 = Staff::factory()->create(['name' => '鈴木 一郎', 'is_active' => true]);

        // 不足タスク
        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '東口警戒巡回',
            'required_people_count' => 3,
        ]);

        // 充足タスク
        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '朝礼ミーティング',
            'required_people_count' => 1,
        ]);
        $task2->assignments()->create([
            'staff_id' => $staff1->id,
            'role_description' => '班長',
            'attendance_status' => 'assigned',
        ]);

        $response = $this->actingAs($this->user)->get(route('timetables.assignments', $this->timetable));

        $response->assertOk();
        $response->assertViewIs('timetables.assignments.index');
        $response->assertSee('人員配置');
        $response->assertDontSee('Step 4 人員アサイン');
        $response->assertSee('東口警戒巡回');
        $response->assertSee('朝礼ミーティング');
        $response->assertSee('本部巡回班');

        // ビューデータのサマリー検証
        $response->assertViewHas('totalTasks', 2);
        $response->assertViewHas('satisfiedTasks', 1);
        $response->assertViewHas('shortageTasks', 1);
        $response->assertViewHas('totalRequired', 4);
        $response->assertViewHas('totalAssigned', 1);

        // 未充足タスクが初期選択されていること
        $response->assertViewHas('initialTaskId', $task1->id);
    }

    /**
     * クエリパラメータで特定のtask_idを指定した場合、そのタスクが初期選択されること
     */
    public function test_initial_task_can_be_specified_via_query_param(): void
    {
        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => 'タスク1',
            'required_people_count' => 2,
        ]);
        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => 'タスク2',
            'required_people_count' => 2,
        ]);

        $response = $this->actingAs($this->user)->get(route('timetables.assignments', [
            $this->timetable,
            'task_id' => $task2->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('initialTaskId', $task2->id);
    }

    /**
     * 停止中のユーザーはアクセスできないこと（403）
     */
    public function test_inactive_user_cannot_access_assignment_workspace(): void
    {
        $inactiveUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveUser)->get(route('timetables.assignments', $this->timetable));
        $response->assertForbidden();
    }
}
