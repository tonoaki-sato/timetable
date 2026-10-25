<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('staff.index'))->assertRedirect(route('login'));
        $this->get(route('staff.create'))->assertRedirect(route('login'));
    }

    /**
     * 停止中ユーザーは403 Forbiddenとなること
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $this->actingAs($inactive)->get(route('staff.index'))->assertStatus(403);
    }

    /**
     * スタッフ一覧が表示され、検索・絞り込みができること
     */
    public function test_index_displays_staff_and_filters(): void
    {
        $this->actingAs($this->operator);

        $staffA = Staff::factory()->create(['name' => '佐々木 直人', 'affiliation' => '中央商店街']);
        $staffB = Staff::factory()->create(['name' => '高橋 美咲', 'affiliation' => '青葉大学']);

        // 一覧表示
        $response = $this->get(route('staff.index'));
        $response->assertStatus(200);
        $response->assertSee('佐々木 直人');
        $response->assertSee('高橋 美咲');

        // キーワード検索
        $searchResponse = $this->get(route('staff.index', ['search' => '佐々木']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('佐々木 直人');
        $searchResponse->assertDontSee('高橋 美咲');
    }

    /**
     * スタッフ新規登録ができること
     */
    public function test_can_create_staff(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('staff.store'), [
            'name' => '新規 太郎',
            'kana' => 'シンキ タロウ',
            'affiliation' => '一般公募',
            'line_display_name' => 'しんき@実行委員',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('staff', [
            'name' => '新規 太郎',
            'kana' => 'シンキ タロウ',
            'affiliation' => '一般公募',
            'line_display_name' => 'しんき@実行委員',
            'is_active' => true,
        ]);
    }

    /**
     * 氏名が未入力の場合バリデーションエラーとなること
     */
    public function test_validation_fails_when_name_missing(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('staff.store'), []);
        $response->assertSessionHasErrors('name');
    }

    /**
     * スタッフ情報を更新できること
     */
    public function test_can_update_staff(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create(['name' => '更新前 太郎', 'is_active' => true]);

        $response = $this->put(route('staff.update', $staff), [
            'name' => '更新後 次郎',
            'kana' => 'コウシンゴ ジロウ',
            'affiliation' => '北町青年団',
            'line_display_name' => 'こうしんご',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('staff.show', $staff));
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'name' => '更新後 次郎',
            'affiliation' => '北町青年団',
            'line_display_name' => 'こうしんご',
        ]);
    }

    /**
     * スタッフを削除できること
     */
    public function test_can_delete_staff(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create(['name' => '削除対象スタッフ']);

        $response = $this->delete(route('staff.destroy', $staff));

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
    }

    /**
     * スタッフ一覧にLINE情報が表示され、詳細画面で登録経路が表示されること
     */
    public function test_admin_staff_index_displays_line_info_and_show_displays_registered_via(): void
    {
        $this->actingAs($this->operator);

        $lineStaff = Staff::factory()->selfRegistered()->withLine('やまちゃん')->create([
            'name' => '山田 太郎',
        ]);

        $response = $this->get(route('staff.index'));
        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
        $response->assertSee('やまちゃん');

        $showResponse = $this->get(route('staff.show', $lineStaff));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('LINEセルフ登録');
    }

    /**
     * 本部管理者がスタッフのアクセストークンを再生成できること
     */
    public function test_admin_can_regenerate_staff_access_token(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create([
            'access_token' => 'old-token-1234567890123456789012',
        ]);

        $response = $this->post(route('staff.regenerate-token', $staff));
        $response->assertRedirect(route('staff.show', $staff));

        $staff->refresh();
        $this->assertNotSame('old-token-1234567890123456789012', $staff->access_token);
        $this->assertSame(32, strlen($staff->access_token));
    }

    /**
     * 未認証ユーザーはトークン再生成を実行できないこと
     */
    public function test_guest_cannot_regenerate_token(): void
    {
        $staff = Staff::factory()->create();
        $this->post(route('staff.regenerate-token', $staff))->assertRedirect(route('login'));
    }
}
