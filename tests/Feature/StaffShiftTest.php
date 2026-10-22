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
}
