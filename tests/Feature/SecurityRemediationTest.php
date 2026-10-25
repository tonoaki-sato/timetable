<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * アカウント無効化（is_active = false）されたユーザーは既存セッションであっても即座に強制ログアウトされること
     */
    public function test_inactive_user_with_active_session_is_immediately_logged_out(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // 有効時はダッシュボードにアクセスできる
        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // 管理者がユーザーを停止状態に更新
        $user->update(['is_active' => false]);

        // 次のリクエストで即座にログアウトされ、403 Forbidden が返却されること
        $responseAfterDeactivation = $this->get(route('dashboard'));
        $responseAfterDeactivation->assertStatus(403);
        $this->assertGuest();
    }

    /**
     * ログイン試行が5回失敗するとレートリミットによりロックされること
     */
    public function test_login_attempts_are_rate_limited_after_5_failures(): void
    {
        RateLimiter::clear('test@example.com|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors(['email' => 'ログイン情報が一致しません。']);
        }

        // 6回目の試行でレートリミットエラー（auth.throttle）
        $response6 = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response6->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('ログイン試行回数が多すぎます', $errorMessage);
    }

    /**
     * スタッフセルフ登録（POST /join）にレートリミットが適用されていること
     */
    public function test_staff_join_store_is_rate_limited(): void
    {
        // 10回リクエスト送信
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('staff.join.store'), [
                'name' => "テスト 花子{$i}",
                'kana' => "テスト ハナコ{$i}",
            ]);
            $response->assertRedirect(route('staff.join.completed'));
        }

        // 11回目のリクエストで 429 Too Many Requests
        $response11 = $this->post(route('staff.join.store'), [
            'name' => '超過 太郎',
            'kana' => 'チョウカ タロウ',
        ]);

        $response11->assertStatus(429);
    }

    /**
     * タスクとアサイン情報の親子関係が一致しない出欠ステータス更新は404で拒絶されること (IDOR防御)
     */
    public function test_cannot_update_attendance_for_assignment_belonging_to_different_task(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
        $this->actingAs($operator);

        $timetable = Timetable::factory()->create();
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id]);

        $taskA = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
        ]);
        $taskB = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
        ]);

        $staff = Staff::factory()->create();
        // assignment は taskB に属している
        $assignmentOfTaskB = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $taskB->id,
            'staff_id' => $staff->id,
            'attendance_status' => 'assigned',
        ]);

        // taskA の URL に対して taskB の assignment を指定して更新を試行
        $response = $this->patch(route('timetable-tasks.update-attendance', [
            'task' => $taskA->id,
            'assignment' => $assignmentOfTaskB->id,
        ]), [
            'attendance_status' => 'completed',
        ]);

        // 親子関係不一致のため 404
        $response->assertStatus(404);
        $assignmentOfTaskB->refresh();
        $this->assertSame('assigned', $assignmentOfTaskB->attendance_status);
    }

    /**
     * タイムテーブルマトリクス画面（/timetable）に未ログインユーザーはアクセスできないこと
     */
    public function test_unauthenticated_user_cannot_access_timetable_matrix(): void
    {
        $response = $this->get(route('timetable.index'));
        $response->assertRedirect(route('login'));
    }
}
