<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonTimetableTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_create_common_tasks_for_all_lanes_with_auto_assigned_members(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $leadStaff1 = Staff::factory()->create();
        $fixedStaff1 = Staff::factory()->create();
        $leadStaff2 = Staff::factory()->create();

        $lane1 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン班',
            'lead_staff_id' => $leadStaff1->id,
            'display_order' => 10,
        ]);
        $lane1->fixedStaff()->attach($fixedStaff1->id);

        $lane2 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '給水・トイレ班',
            'lead_staff_id' => $leadStaff2->id,
            'display_order' => 20,
        ]);

        $lane3 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '総務班',
            'lead_staff_id' => null,
            'display_order' => 30,
        ]);

        $response = $this->actingAs($admin)->post(route('timetable-tasks.common.store'), [
            '_form' => 'common_task',
            'timetable_id' => $timetable->id,
            'lane_ids' => [$lane1->id, $lane2->id, $lane3->id],
            'title' => '全体ミーティング',
            'start_time' => '08:00',
            'end_time' => '08:30',
            'required_people_count' => 0,
            'is_at_least' => '0',
            'is_critical' => '1',
            'status' => 'not_started',
            'location' => '本部テント前',
            'content' => '当日の安全確認とスケジュール共有を行います。',
            'auto_assign_lead' => '1',
        ]);

        $response->assertRedirect(route('timetables.tasks.index', $timetable));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('timetable_tasks', 3);

        foreach ([$lane1, $lane2, $lane3] as $lane) {
            $this->assertDatabaseHas('timetable_tasks', [
                'timetable_id' => $timetable->id,
                'lane_id' => $lane->id,
                'title' => '全体ミーティング',
                'start_time' => '08:00',
                'end_time' => '08:30',
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => true,
                'status' => 'not_started',
                'location' => '本部テント前',
                'content' => '当日の安全確認とスケジュール共有を行います。',
            ]);
        }

        $task1 = TimetableTask::where('lane_id', $lane1->id)->firstOrFail();
        $task2 = TimetableTask::where('lane_id', $lane2->id)->firstOrFail();
        $task3 = TimetableTask::where('lane_id', $lane3->id)->firstOrFail();

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $leadStaff1->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $fixedStaff1->id,
            'role_description' => '固定スタッフ',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task2->id,
            'staff_id' => $leadStaff2->id,
            'role_description' => '班長',
        ]);
        $this->assertSame(0, TaskStaffAssignment::where('timetable_task_id', $task3->id)->count());
    }

    public function test_operator_can_bulk_create_common_tasks_for_selected_lanes_without_auto_assign(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $operator->id]);
        $leadStaff = Staff::factory()->create();

        $lane1 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'lead_staff_id' => $leadStaff->id,
            'display_order' => 10,
        ]);
        $lane2 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'display_order' => 20,
        ]);
        $unselectedLane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'display_order' => 30,
        ]);

        $response = $this->actingAs($operator)->post(route('timetable-tasks.common.store'), [
            '_form' => 'common_task',
            'timetable_id' => $timetable->id,
            'lane_ids' => [$lane1->id, $lane2->id],
            'title' => '合同設営ブリーフィング',
            'start_time' => '09:00',
            'end_time' => '09:30',
            'required_people_count' => 2,
            'is_at_least' => '1',
            'is_critical' => '0',
            'status' => 'not_started',
            'auto_assign_lead' => '0',
        ]);

        $response->assertRedirect(route('timetables.tasks.index', $timetable));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('timetable_tasks', 2);
        $this->assertDatabaseHas('timetable_tasks', [
            'lane_id' => $lane1->id,
            'title' => '合同設営ブリーフィング',
            'is_at_least' => true,
        ]);
        $this->assertDatabaseHas('timetable_tasks', [
            'lane_id' => $lane2->id,
            'title' => '合同設営ブリーフィング',
        ]);
        $this->assertDatabaseMissing('timetable_tasks', [
            'lane_id' => $unselectedLane->id,
        ]);
        $this->assertDatabaseCount('task_staff_assignments', 0);
    }

    public function test_blocks_registration_when_any_selected_lane_has_time_overlap_and_reports_lane_and_task_names(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $lane1 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン班',
            'display_order' => 10,
        ]);
        $lane2 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '給水・トイレ班',
            'display_order' => 20,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane2->id,
            'title' => '資材事前準備',
            'start_time' => '08:15:00',
            'end_time' => '09:00:00',
            'status' => 'not_started',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('timetables.tasks.index', $timetable))
            ->post(route('timetable-tasks.common.store'), [
                '_form' => 'common_task',
                'timetable_id' => $timetable->id,
                'lane_ids' => [$lane1->id, $lane2->id],
                'title' => '全体ミーティング',
                'start_time' => '08:00',
                'end_time' => '08:30',
                'required_people_count' => 0,
                'status' => 'not_started',
                'auto_assign_lead' => '1',
            ]);

        $response->assertRedirect(route('timetables.tasks.index', $timetable));
        $response->assertSessionHasErrors('lane_ids');

        // 重複がないカラーコーン班を含め、1件も新規作成されていないこと
        $this->assertDatabaseCount('timetable_tasks', 1);
        $this->assertDatabaseMissing('timetable_tasks', [
            'lane_id' => $lane1->id,
            'title' => '全体ミーティング',
        ]);

        $errors = session('errors')->get('lane_ids');
        $joined = implode("\n", $errors);
        $this->assertStringContainsString('給水・トイレ班', $joined);
        $this->assertStringContainsString('資材事前準備', $joined);
    }

    public function test_allows_registration_when_existing_overlapping_task_is_cancelled(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '警備班',
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '中止された作業',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($admin)->post(route('timetable-tasks.common.store'), [
            'timetable_id' => $timetable->id,
            'lane_ids' => [$lane->id],
            'title' => '全体朝礼',
            'start_time' => '08:00',
            'end_time' => '08:30',
            'required_people_count' => 0,
            'status' => 'not_started',
        ]);

        $response->assertRedirect(route('timetables.tasks.index', $timetable));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('timetable_tasks', [
            'lane_id' => $lane->id,
            'title' => '全体朝礼',
            'status' => 'not_started',
        ]);
    }

    public function test_rejects_lanes_belonging_to_different_timetable(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $otherTimetable = Timetable::factory()->create(['created_by' => $admin->id]);

        $ownLane = Lane::factory()->create(['timetable_id' => $timetable->id]);
        $otherLane = Lane::factory()->create(['timetable_id' => $otherTimetable->id]);

        $response = $this->actingAs($admin)
            ->from(route('timetables.tasks.index', $timetable))
            ->post(route('timetable-tasks.common.store'), [
                '_form' => 'common_task',
                'timetable_id' => $timetable->id,
                'lane_ids' => [$ownLane->id, $otherLane->id],
                'title' => '不正横断テスト',
                'start_time' => '08:00',
                'end_time' => '08:30',
                'required_people_count' => 0,
            ]);

        $response->assertRedirect(route('timetables.tasks.index', $timetable));
        $response->assertSessionHasErrors('lane_ids.1');
        $this->assertDatabaseCount('timetable_tasks', 0);
    }

    public function test_unauthorized_user_cannot_store_common_tasks(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create();
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id]);

        $response = $this->actingAs($viewer)->post(route('timetable-tasks.common.store'), [
            'timetable_id' => $timetable->id,
            'lane_ids' => [$lane->id],
            'title' => '全体ミーティング',
            'start_time' => '08:00',
            'end_time' => '08:30',
            'required_people_count' => 0,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('timetable_tasks', 0);
    }

    public function test_tasks_index_renders_common_task_modal_cleanly_without_inline_styles_or_handlers(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $staff = Staff::factory()->create();

        $lane1 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン班',
            'lead_staff_id' => $staff->id,
            'display_order' => 10,
        ]);
        $lane2 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '給水・トイレ班',
            'display_order' => 20,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane1->id,
            'title' => '初期設営タスク',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        TaskStaffAssignment::create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
            'role_description' => '班長',
            'attendance_status' => 'assigned',
        ]);

        // 管理者: 一括登録ボタン・モーダル・担当班・既存タスクがレンダリングされ、厳格CSP違反がないこと
        $adminResponse = $this->actingAs($admin)->get(route('timetables.tasks.index', $timetable));
        $adminResponse->assertOk();
        $adminResponse->assertSee('共通タスク一括登録');
        $adminResponse->assertSee('id="task-common-create-modal"', false);
        $adminResponse->assertSee('カラーコーン班');
        $adminResponse->assertSee('給水・トイレ班');
        $adminResponse->assertSee('初期設営タスク');

        $html = $adminResponse->getContent();
        $this->assertIsString($html);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $html);

        // 閲覧ユーザー: 作成ボタンおよび一括登録モーダルが出力されないこと
        $viewerResponse = $this->actingAs($viewer)->get(route('timetables.tasks.index', $timetable));
        $viewerResponse->assertOk();
        $viewerResponse->assertDontSee('data-action="open-common-task-create"', false);
        $viewerResponse->assertDontSee('id="task-common-create-modal"', false);
    }

    public function test_validation_error_reopens_common_modal_with_old_input_and_error_banner(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '誘導班',
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '事前点検',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => 'not_started',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('timetables.tasks.index', $timetable))
            ->followingRedirects()
            ->post(route('timetable-tasks.common.store'), [
                '_form' => 'common_task',
                'timetable_id' => $timetable->id,
                'lane_ids' => [$lane->id],
                'title' => '入力保持テストミーティング',
                'start_time' => '08:30',
                'end_time' => '09:30',
                'required_people_count' => 0,
            ]);

        $response->assertOk();
        $response->assertSee('data-reopen-on-error="1"', false);
        $response->assertSee('id="common-modal-server-errors"', false);
        $response->assertSee('入力保持テストミーティング');
        $response->assertSee('誘導班');
        $response->assertSee('事前点検');
    }
}
