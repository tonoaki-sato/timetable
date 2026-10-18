<?php

namespace Tests\Feature;

use App\Models\AnnualSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualScheduleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはログイン画面にリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get(route('annual-schedules.index'));
        $response->assertRedirect(route('login'));

        $responseCreate = $this->get(route('annual-schedules.create'));
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
        $response = $this->get(route('annual-schedules.index'));
        $response->assertStatus(403);

        $inactiveOperator = User::factory()->create([
            'role' => 'operator',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveOperator);
        $response = $this->get(route('annual-schedules.index'));
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

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin);

        // 1. 一覧表示
        $this->get(route('annual-schedules.index'))->assertStatus(200);

        // 2. 新規作成フォーム表示
        $this->get(route('annual-schedules.create'))->assertStatus(200);

        // 3. 詳細画面表示
        $this->get(route('annual-schedules.show', $schedule))->assertStatus(200);

        // 4. 編集画面表示
        $this->get(route('annual-schedules.edit', $schedule))->assertStatus(200);

        // 5. 新規登録
        $storeResponse = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => '管理者登録スケジュール',
            'category' => 'committee',
            'start_date' => '2026-05-01',
            'status' => 'planned',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('annual_schedules', ['title' => '管理者登録スケジュール']);

        // 6. 更新
        $updateResponse = $this->put(route('annual-schedules.update', $schedule), [
            'target_year' => 2026,
            'title' => '管理者更新後タイトル',
            'category' => 'operation',
            'start_date' => '2026-06-01',
            'status' => 'in_progress',
        ]);
        $updateResponse->assertRedirect(route('annual-schedules.show', $schedule));
        $this->assertDatabaseHas('annual_schedules', ['title' => '管理者更新後タイトル']);

        // 7. 削除
        $deleteResponse = $this->delete(route('annual-schedules.destroy', $schedule));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('annual_schedules', ['id' => $schedule->id]);
    }

    /**
     * operator も admin と同様に一覧・詳細・新規登録・編集・削除の全機能を利用できること
     */
    public function test_operator_is_authorized_for_all_crud_actions(): void
    {
        $operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'created_by' => $operator->id,
        ]);

        $this->actingAs($operator);

        // 1. 一覧表示
        $this->get(route('annual-schedules.index'))->assertStatus(200);

        // 2. 新規作成フォーム表示
        $this->get(route('annual-schedules.create'))->assertStatus(200);

        // 3. 詳細画面表示
        $this->get(route('annual-schedules.show', $schedule))->assertStatus(200);

        // 4. 編集画面表示
        $this->get(route('annual-schedules.edit', $schedule))->assertStatus(200);

        // 5. 新規登録
        $storeResponse = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => 'オペレーター登録スケジュール',
            'category' => 'briefing',
            'start_date' => '2026-07-15',
            'status' => 'planned',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('annual_schedules', ['title' => 'オペレーター登録スケジュール']);

        // 6. 更新
        $updateResponse = $this->put(route('annual-schedules.update', $schedule), [
            'target_year' => 2026,
            'title' => 'オペレーター更新後タイトル',
            'category' => 'permit',
            'start_date' => '2026-08-01',
            'status' => 'completed',
        ]);
        $updateResponse->assertRedirect(route('annual-schedules.show', $schedule));
        $this->assertDatabaseHas('annual_schedules', ['title' => 'オペレーター更新後タイトル']);

        // 7. 削除
        $deleteResponse = $this->delete(route('annual-schedules.destroy', $schedule));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('annual_schedules', ['id' => $schedule->id]);
    }
}
