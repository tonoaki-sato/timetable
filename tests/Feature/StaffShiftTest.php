<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffShiftTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;

    private Timetable $timetable;

    private Lane $lane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = Staff::factory()->create([
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
            'affiliation' => '本部運営委員会',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'target_date' => now()->format('Y-m-d'),
            'name' => 'まつり本番 1日目',
        ]);

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '給水設備班',
        ]);
    }

    /**
     * 有効なトークンでマイシフトを閲覧できること
     */
    public function test_can_access_my_shift_with_valid_token(): void
    {
        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $response->assertStatus(200);
        $response->assertSee('木村 拓也');
        $response->assertSee('マイタイムテーブル');
        $response->assertCookie('staff_token', $this->staff->access_token);
    }

    /**
     * アクセス時に最終確認日時（last_accessed_at）が更新されること
     */
    public function test_my_shift_updates_last_accessed_at(): void
    {
        $this->assertNull($this->staff->last_accessed_at);

        $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $this->staff->refresh();
        $this->assertNotNull($this->staff->last_accessed_at);
    }

    /**
     * 自分にアサインされたタスクのみが表示され、他者のタスクは混ざらないこと
     */
    public function test_my_shift_only_shows_assigned_tasks(): void
    {
        $otherStaff = Staff::factory()->create(['name' => '他スタッフ']);

        // 自分のタスク
        $myTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '給水所A タンク設置',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $myTask->id,
            'staff_id' => $this->staff->id,
            'role_description' => 'ホース接続担当',
        ]);

        // 他者のタスク
        $otherTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '給水所B バルブ点検（他スタッフ専任）',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $otherTask->id,
            'staff_id' => $otherStaff->id,
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $response->assertStatus(200);
        $response->assertSee('給水所A タンク設置');
        $response->assertSee('ホース接続担当');
        $response->assertSee('給水設備班');
        $response->assertDontSee('給水所B バルブ点検（他スタッフ専任）');
    }

    /**
     * 無効なトークンでアクセスした場合404となること
     */
    public function test_access_with_invalid_token_returns_404(): void
    {
        $response = $this->get(route('staff.myshift', ['token' => 'invalid-dummy-token-string']));
        $response->assertStatus(404);
    }

    /**
     * 停止中スタッフはアクセス不可となること
     */
    public function test_access_with_inactive_staff_token_is_denied(): void
    {
        $this->staff->update(['is_active' => false]);

        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));
        $response->assertStatus(404);
    }

    /**
     * 現場到着チェックインが正常に記録されること
     */
    public function test_staff_can_check_in_on_site(): void
    {
        $this->assertFalse($this->staff->is_checked_in);

        // JSONリクエストでのチェックイン
        $response = $this->postJson(route('staff.myshift.checkin', ['token' => $this->staff->access_token]));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->staff->refresh();
        $this->assertTrue($this->staff->is_checked_in);
        $this->assertNotNull($this->staff->checked_in_at);
    }

    /**
     * 複数回チェックインしても初回到着日時が保持されること（冪等性）
     */
    public function test_repeated_check_in_preserves_initial_timestamp(): void
    {
        $initialTime = now()->subMinutes(30);
        $this->staff->update(['checked_in_at' => $initialTime]);

        $this->postJson(route('staff.myshift.checkin', ['token' => $this->staff->access_token]));

        $this->staff->refresh();
        $this->assertSame($initialTime->format('Y-m-d H:i:s'), $this->staff->checked_in_at->format('Y-m-d H:i:s'));
    }

    /**
     * 氏名・フリガナ照合画面が正常に表示されること
     */
    public function test_find_screen_can_be_rendered(): void
    {
        $response = $this->get(route('staff.myshift.find'));
        $response->assertStatus(200);
        $response->assertSee('タイムテーブル再検索');
    }

    /**
     * 氏名＋フリガナの完全一致でマイシフト画面へリダイレクトされること
     */
    public function test_can_recover_url_by_matching_name_and_kana(): void
    {
        $response = $this->post(route('staff.myshift.find.post'), [
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
        ]);

        $response->assertRedirect(route('staff.myshift', ['token' => $this->staff->access_token]));
    }

    /**
     * 同姓同名が複数ヒットした場合に候補選択画面が表示されること
     */
    public function test_find_with_multiple_matches_shows_selection(): void
    {
        Staff::factory()->create([
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
            'affiliation' => '青葉大学',
        ]);

        $response = $this->post(route('staff.myshift.find.post'), [
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
        ]);

        $response->assertStatus(200);
        $response->assertSee('同姓同名のスタッフが複数見つかりました');
    }

    /**
     * 同姓同名が複数いる場合でも、所属を指定すれば1名に絞り込まれて直接リダイレクトされること
     */
    public function test_find_with_affiliation_narrows_down_to_single_staff(): void
    {
        $targetStaff = Staff::factory()->create([
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
            'affiliation' => '中央商店街振興組合',
        ]);

        $response = $this->post(route('staff.myshift.find.post'), [
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
            'affiliation' => '商店街',
        ]);

        $response->assertRedirect(route('staff.myshift', ['token' => $targetStaff->access_token]));
    }

    /**
     * 一致するスタッフがいない場合エラーメッセージが表示されること
     */
    public function test_find_with_no_match_returns_error(): void
    {
        $response = $this->post(route('staff.myshift.find.post'), [
            'name' => '存在しない名前',
            'kana' => 'ソンザイシナイ',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * マイシフト画面からワンタップでLINE連携ができること
     */
    public function test_staff_can_bind_line_profile_from_myshift_screen(): void
    {
        $this->assertNull($this->staff->line_user_id);

        $response = $this->postJson(route('staff.myshift.line-bind', ['token' => $this->staff->access_token]), [
            'line_user_id' => 'U1234567890abcdef1234567890abcdef',
            'line_display_name' => '拓也LINE',
            'line_picture_url' => 'https://example.com/pic.jpg',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'staff' => [
                    'line_display_name' => '拓也LINE',
                ],
            ]);

        $this->staff->refresh();
        $this->assertSame('U1234567890abcdef1234567890abcdef', $this->staff->line_user_id);
        $this->assertSame('拓也LINE', $this->staff->line_display_name);
        $this->assertSame('https://example.com/pic.jpg', $this->staff->line_picture_url);
    }

    /**
     * 既に使用されているLINE User IDは連携できず422エラーになること
     */
    public function test_staff_cannot_bind_already_taken_line_user_id(): void
    {
        Staff::factory()->create([
            'line_user_id' => 'U1234567890abcdef1234567890abcdef',
        ]);

        $response = $this->postJson(route('staff.myshift.line-bind', ['token' => $this->staff->access_token]), [
            'line_user_id' => 'U1234567890abcdef1234567890abcdef',
            'line_display_name' => '拓也LINE',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['line_user_id']);
    }

    /**
     * 現場到着チェックイン時、当日運行日の最も早いタスクの出欠が自動連動更新されること
     */
    public function test_checkin_automatically_updates_first_task_attendance_status(): void
    {
        // 1つ目のタスク (09:00 - 10:00)
        $firstTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        $firstAssignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $firstTask->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'assigned',
        ]);

        // 2つ目のタスク (11:00 - 12:00)
        $secondTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
        ]);
        $secondAssignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $secondTask->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->postJson(route('staff.myshift.checkin', ['token' => $this->staff->access_token]));
        $response->assertStatus(200);

        $firstAssignment->refresh();
        $secondAssignment->refresh();

        $this->assertSame('checked_in', $firstAssignment->attendance_status);
        $this->assertSame('assigned', $secondAssignment->attendance_status);
    }

    /**
     * スタッフがマイシフト画面から担当タスクの進捗ステータスを更新できること
     */
    public function test_staff_can_update_own_task_progress_status(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
        ]);
        $assignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'checked_in',
        ]);

        // 着手 (in_progress)
        $response = $this->postJson(route('staff.myshift.assignment-status', [
            'token' => $this->staff->access_token,
            'assignment' => $assignment->id,
        ]), [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'in_progress',
                'status_label' => '着手中',
            ]);

        $assignment->refresh();
        $this->assertSame('in_progress', $assignment->attendance_status);

        // 完了 (completed)
        $response2 = $this->postJson(route('staff.myshift.assignment-status', [
            'token' => $this->staff->access_token,
            'assignment' => $assignment->id,
        ]), [
            'status' => 'completed',
        ]);

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'completed',
                'status_label' => '完了',
            ]);

        $assignment->refresh();
        $this->assertSame('completed', $assignment->attendance_status);
    }

    /**
     * 他スタッフのタスクアサインを更新しようとすると403 Forbiddenとなること
     */
    public function test_staff_cannot_update_other_staff_task_progress(): void
    {
        $otherStaff = Staff::factory()->create();
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
        ]);
        $otherAssignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $otherStaff->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->postJson(route('staff.myshift.assignment-status', [
            'token' => $this->staff->access_token,
            'assignment' => $otherAssignment->id,
        ]), [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(403);
    }
}
