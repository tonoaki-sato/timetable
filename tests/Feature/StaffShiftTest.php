<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;
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
     * URL紛失照合ルート（/my/find）は完全に削除され 404 を返すこと
     */
    public function test_my_find_route_returns_404(): void
    {
        $responseGet = $this->get('/my/find');
        $responseGet->assertStatus(404);

        $responsePost = $this->post('/my/find', [
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
        ]);
        $responsePost->assertStatus(404);
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

    /**
     * 上部カードと手動更新ボタンが削除され、自動同期表示と1行タイムラインコンテナが表示されること
     */
    public function test_my_shift_displays_one_line_layout_without_top_cards_and_refresh_button(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '朝礼と準備作業',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $this->staff->id,
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $response->assertStatus(200);
        // 上部カードの旧テキスト・独立要素が存在しないこと
        $response->assertDontSee('NOW (現在進行中)');
        $response->assertDontSee('NEXT (次の予定)');
        // 手動更新ボタンが存在しないこと
        $response->assertDontSee('id="refreshBtn"', false);
        // リアルタイム自動同期表示とタイムラインコンテナが存在すること
        $response->assertSee('リアルタイム同期中');
        $response->assertSee('id="myshiftTimelineContainer"', false);
        $response->assertSee('朝礼と準備作業');
    }

    /**
     * 該当タスク行にNOW進行中バッジ（ハイライト枠線）およびNEXT次の予定バッジが表示されること
     */
    public function test_my_shift_displays_now_and_next_badges_in_task_rows(): void
    {
        $targetDate = $this->timetable->target_date->format('Y-m-d');
        Carbon::setTestNow(Carbon::parse($targetDate.' 12:00:00'));
        $now = now();
        $currentTime = $now->format('H:i:s');

        // 現在進行中のタスク
        $currentTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '現在実施中の警備業務',
            'start_time' => $now->copy()->subMinutes(15)->format('H:i:s'),
            'end_time' => $now->copy()->addMinutes(45)->format('H:i:s'),
        ]);
        $currentAssignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $currentTask->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'assigned',
        ]);

        // 次の予定タスク
        $nextTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '次に行う清掃業務',
            'start_time' => $now->copy()->addMinutes(60)->format('H:i:s'),
            'end_time' => $now->copy()->addMinutes(120)->format('H:i:s'),
        ]);
        $nextAssignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $nextTask->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $response->assertStatus(200);
        // 行内バッジの確認
        $response->assertSee('NOW 進行中');
        $response->assertSee('NEXT 次の予定');
        // 現在タスクのハイライト枠線の確認
        $response->assertSee('border-indigo-400');
        // 現在タスクの行内着手ボタンの確認
        $response->assertSee('着手');
        $response->assertSee('完了');

        Carbon::setTestNow();
    }

    /**
     * タスク行に場所および班長名が表示され、アコーディオンにタスク詳細が表示されること
     */
    public function test_my_shift_displays_location_and_lane_lead_in_row_and_details_in_accordion(): void
    {
        $laneLead = Staff::factory()->create([
            'name' => '佐藤 班長',
            'is_active' => true,
        ]);

        $laneWithLead = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '設営班',
            'lead_staff_id' => $laneLead->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $laneWithLead->id,
            'title' => 'テント設営と安全確認',
            'location' => '中央広場ステージ前',
            'content' => '大型パイプテント4張りの組み立てとペグ打ち固定作業。',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $this->staff->id,
            'role_description' => 'テント骨組み組み立て',
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $response->assertStatus(200);

        // 1行表示内に場所と班長名が表示されていること
        $response->assertSee('中央広場ステージ前');
        $response->assertSee('佐藤 班長');
        $response->assertSee('設営班');

        // アコーディオン内に作業詳細（content）が表示されていること
        $response->assertSee('大型パイプテント4張りの組み立てとペグ打ち固定作業。');

        // アコーディオン内に旧フォーマットの項目が存在しないこと
        $response->assertDontSee('<span class="text-indigo-400 font-bold shrink-0">役割:</span>', false);
        $response->assertDontSee('<span class="text-indigo-400 font-bold shrink-0">場所:</span>', false);
    }

    /**
     * 複数運行日が存在する場合の初期選択優先順位（本日 > 最短未来日 > 直近過去日）が正しく機能すること
     */
    public function test_default_timetable_selection_prioritizes_today_then_closest_future_then_latest_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        try {
            $pastTimetable = Timetable::factory()->create([
                'target_date' => '2026-10-10',
                'name' => '過去イベント',
            ]);
            $pastLane = Lane::factory()->create(['timetable_id' => $pastTimetable->id, 'name' => '過去班']);
            $pastTask = TimetableTask::factory()->create(['timetable_id' => $pastTimetable->id, 'lane_id' => $pastLane->id, 'title' => '過去のみタスク']);
            TaskStaffAssignment::factory()->create(['timetable_task_id' => $pastTask->id, 'staff_id' => $this->staff->id]);

            $farFutureTimetable = Timetable::factory()->create([
                'target_date' => '2027-10-10',
                'name' => '来年イベント',
            ]);
            $farLane = Lane::factory()->create(['timetable_id' => $farFutureTimetable->id, 'name' => '来年班']);
            $farTask = TimetableTask::factory()->create(['timetable_id' => $farFutureTimetable->id, 'lane_id' => $farLane->id, 'title' => '来年のタスク']);
            TaskStaffAssignment::factory()->create(['timetable_task_id' => $farTask->id, 'staff_id' => $this->staff->id]);

            $nearFutureTimetable = Timetable::factory()->create([
                'target_date' => '2026-12-01',
                'name' => '来月イベント',
            ]);
            $nearLane = Lane::factory()->create(['timetable_id' => $nearFutureTimetable->id, 'name' => '来月班']);
            $nearTask = TimetableTask::factory()->create(['timetable_id' => $nearFutureTimetable->id, 'lane_id' => $nearLane->id, 'title' => '来月のタスク']);
            TaskStaffAssignment::factory()->create(['timetable_task_id' => $nearTask->id, 'staff_id' => $this->staff->id]);

            // 1. 本日がなく過去・未来がある場合は最短未来日が初期選択されること
            $futurePriorityResponse = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));
            $futurePriorityResponse->assertStatus(200);
            $futurePriorityResponse->assertSee('来月のタスク');
            $futurePriorityResponse->assertDontSee('過去のみタスク');
            $futurePriorityResponse->assertDontSee('来年のタスク');

            // 2. 本日のタスクが追加された場合は本日（$this->timetable）が最優先で初期選択されること
            $todayTask = TimetableTask::factory()->create([
                'timetable_id' => $this->timetable->id,
                'lane_id' => $this->lane->id,
                'title' => '本日のタスク',
            ]);
            TaskStaffAssignment::factory()->create([
                'timetable_task_id' => $todayTask->id,
                'staff_id' => $this->staff->id,
            ]);

            $todayPriorityResponse = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));
            $todayPriorityResponse->assertStatus(200);
            $todayPriorityResponse->assertSee('本日のタスク');
            $todayPriorityResponse->assertDontSee('来月のタスク');
            $todayPriorityResponse->assertDontSee('過去のみタスク');

            // 3. 過去のみ存在する場合は最も新しい過去日が初期選択されること
            $pastOnlyStaff = Staff::factory()->create(['is_active' => true]);
            $olderPastTimetable = Timetable::factory()->create([
                'target_date' => '2025-10-10',
                'name' => '一昨年イベント',
            ]);
            $olderLane = Lane::factory()->create(['timetable_id' => $olderPastTimetable->id, 'name' => '旧班']);
            $olderTask = TimetableTask::factory()->create(['timetable_id' => $olderPastTimetable->id, 'lane_id' => $olderLane->id, 'title' => '古い過去タスク']);
            TaskStaffAssignment::factory()->create(['timetable_task_id' => $olderTask->id, 'staff_id' => $pastOnlyStaff->id]);

            $latestPastTask = TimetableTask::factory()->create(['timetable_id' => $pastTimetable->id, 'lane_id' => $pastLane->id, 'title' => '直近の過去タスク']);
            TaskStaffAssignment::factory()->create(['timetable_task_id' => $latestPastTask->id, 'staff_id' => $pastOnlyStaff->id]);

            $pastPriorityResponse = $this->get(route('staff.myshift', ['token' => $pastOnlyStaff->access_token]));
            $pastPriorityResponse->assertStatus(200);
            $pastPriorityResponse->assertSee('直近の過去タスク');
            $pastPriorityResponse->assertDontSee('古い過去タスク');
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * クエリパラメータ timetable_id で運行日切り替えおよび全日程表示ができること
     */
    public function test_can_switch_timetables_and_view_all_with_query_parameter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        try {
            $pastTimetable = Timetable::factory()->create([
                'target_date' => '2026-10-11',
                'name' => '宿場まつり 1日目',
            ]);
            $pastLane = Lane::factory()->create([
                'timetable_id' => $pastTimetable->id,
                'name' => '巡回班',
                'lead_staff_id' => $this->staff->id,
            ]);
            $pastTask = TimetableTask::factory()->create([
                'timetable_id' => $pastTimetable->id,
                'lane_id' => $pastLane->id,
                'title' => '過去の巡回タスク',
            ]);
            TaskStaffAssignment::factory()->create([
                'timetable_task_id' => $pastTask->id,
                'staff_id' => $this->staff->id,
            ]);

            $futureTimetable = Timetable::factory()->create([
                'target_date' => '2027-10-10',
                'name' => '次回宿場まつり',
            ]);
            $futureLane = Lane::factory()->create([
                'timetable_id' => $futureTimetable->id,
                'name' => 'ステカン班',
            ]);
            $futureTask = TimetableTask::factory()->create([
                'timetable_id' => $futureTimetable->id,
                'lane_id' => $futureLane->id,
                'title' => '次回の看板設置',
            ]);
            TaskStaffAssignment::factory()->create([
                'timetable_task_id' => $futureTask->id,
                'staff_id' => $this->staff->id,
            ]);

            // 過去の運行日を指定表示
            $pastResponse = $this->get(route('staff.myshift', [
                'token' => $this->staff->access_token,
                'timetable_id' => $pastTimetable->id,
            ]));
            $pastResponse->assertStatus(200);
            $pastResponse->assertSee('過去の巡回タスク');
            $pastResponse->assertDontSee('次回の看板設置');
            $pastResponse->assertSee('data-main-tab="leader"', false);

            // 全日程表示 (timetable_id=all)
            $allResponse = $this->get(route('staff.myshift', [
                'token' => $this->staff->access_token,
                'timetable_id' => 'all',
            ]));
            $allResponse->assertStatus(200);
            $allResponse->assertSee('過去の巡回タスク');
            $allResponse->assertSee('次回の看板設置');
            $allResponse->assertSee('全運行日表示');
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * 担当班フィルター・備考・連携メンバー表示・複数班長の班タブが出力されCSPに違反しないこと
     */
    public function test_my_shift_displays_lane_filters_notes_coworkers_and_leader_lane_tabs(): void
    {
        $secondLane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '清掃誘導班',
            'lead_staff_id' => $this->staff->id,
        ]);
        $this->lane->update(['lead_staff_id' => $this->staff->id]);

        $coWorker = Staff::factory()->create([
            'name' => '高橋 連帯',
            'is_active' => true,
        ]);

        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '給水機材搬入',
            'content' => 'タンク20本を中央へ搬入します。',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task1->id,
            'staff_id' => $this->staff->id,
            'role_description' => '台車運搬',
            'notes' => '手袋は本部テント前で受け取ってください。',
            'attendance_status' => 'completed',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task1->id,
            'staff_id' => $coWorker->id,
            'role_description' => '積み下ろし補助',
        ]);

        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $secondLane->id,
            'title' => '会場周辺巡回清掃',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task2->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));

        $response->assertStatus(200);
        // 担当班フィルターと進捗率（1/2 = 50%）
        $response->assertSee('data-lane-filter="'.$this->lane->id.'"', false);
        $response->assertSee('data-lane-filter="'.$secondLane->id.'"', false);
        $response->assertSee('data-progress-percent="50"', false);

        // アコーディオン内の備考と連携メンバー名
        $response->assertSee('手袋は本部テント前で受け取ってください。');
        $response->assertSee('高橋 連帯');

        // 複数班長の班切り替えタブと班長コンソールパネル
        $response->assertSee('data-leader-lane-tab="'.$this->lane->id.'"', false);
        $response->assertSee('data-leader-lane-tab="'.$secondLane->id.'"', false);
        $response->assertSee('data-leader-lane-panel="'.$this->lane->id.'"', false);
        $response->assertSee('data-leader-lane-panel="'.$secondLane->id.'"', false);

        // インラインスタイルやイベントハンドラが存在しないこと
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
    }

    /**
     * マイシフト関連エンドポイントにレートリミットが適用されていること
     */
    public function test_shift_endpoints_are_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $response = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));
            $response->assertStatus(200);
        }

        $limitedResponse = $this->get(route('staff.myshift', ['token' => $this->staff->access_token]));
        $limitedResponse->assertStatus(429);
    }
}
