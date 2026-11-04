<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはログイン画面にリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get(route('schedules.index'));
        $response->assertRedirect(route('login'));

        $responseCreate = $this->get(route('schedules.create'));
        $responseCreate->assertRedirect(route('login'));
    }

    /**
     * 停止中ユーザー（is_active = false）はアクセスを拒否されること（403）
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveAdmin);
        $response = $this->get(route('schedules.index'));
        $response->assertStatus(403);

        $inactiveOperator = User::factory()->create([
            'role' => 'operator',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveOperator);
        $response = $this->get(route('schedules.index'));
        $response->assertStatus(403);
    }

    /**
     * admin は一覧・詳細・新規登録・編集・削除の全機能を利用できること
     */
    public function test_admin_is_authorized_for_all_crud_actions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create([
            'title' => '管理者向けテスト予定',
        ]);

        $this->actingAs($admin);

        // index
        $this->get(route('schedules.index'))->assertStatus(200);

        // show
        $this->get(route('schedules.show', $schedule))->assertStatus(200);

        // create
        $this->get(route('schedules.create'))->assertStatus(200);

        // store
        $this->post(route('schedules.store'), [
            'title' => '新規予定',
            'category' => 'other',
            'start_date' => '2027-01-01',
            'status' => 'planned',
        ])->assertRedirect();

        // edit
        $this->get(route('schedules.edit', $schedule))->assertStatus(200);

        // update
        $this->put(route('schedules.update', $schedule), [
            'title' => '更新予定',
            'category' => 'other',
            'start_date' => '2027-01-01',
            'status' => 'planned',
        ])->assertRedirect();

        // destroy
        $this->delete(route('schedules.destroy', $schedule))->assertRedirect();
    }

    /**
     * operator は一覧・詳細・新規登録・編集・削除の全機能を利用できること
     */
    public function test_operator_is_authorized_for_all_crud_actions(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create([
            'title' => 'オペレーター向けテスト予定',
        ]);

        $this->actingAs($operator);

        $this->get(route('schedules.index'))->assertStatus(200);
        $this->get(route('schedules.show', $schedule))->assertStatus(200);
        $this->get(route('schedules.create'))->assertStatus(200);

        $this->post(route('schedules.store'), [
            'title' => 'オペレーター作成予定',
            'category' => 'operation',
            'start_date' => '2027-05-01',
            'status' => 'planned',
        ])->assertRedirect();

        $this->get(route('schedules.edit', $schedule))->assertStatus(200);

        $this->put(route('schedules.update', $schedule), [
            'title' => 'オペレーター更新予定',
            'category' => 'operation',
            'start_date' => '2027-05-01',
            'status' => 'planned',
        ])->assertRedirect();

        $this->delete(route('schedules.destroy', $schedule))->assertRedirect();
    }

    /**
     * viewer は閲覧のみ可能で、作成・更新・削除は 403 となること
     */
    public function test_viewer_is_read_only(): void
    {
        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create([
            'title' => '閲覧者向けテスト予定',
        ]);

        $this->actingAs($viewer);

        // 閲覧は可能
        $this->get(route('schedules.index'))->assertStatus(200);
        $this->get(route('schedules.show', $schedule))->assertStatus(200);

        // 作成・編集・削除は拒否
        $this->get(route('schedules.create'))->assertStatus(403);

        $this->post(route('schedules.store'), [
            'title' => '閲覧者作成試行',
            'category' => 'other',
            'start_date' => '2027-01-01',
            'status' => 'planned',
        ])->assertStatus(403);

        $this->get(route('schedules.edit', $schedule))->assertStatus(403);

        $this->put(route('schedules.update', $schedule), [
            'title' => '閲覧者更新試行',
            'category' => 'other',
            'start_date' => '2027-01-01',
            'status' => 'planned',
        ])->assertStatus(403);

        $this->delete(route('schedules.destroy', $schedule))->assertStatus(403);
    }
}
