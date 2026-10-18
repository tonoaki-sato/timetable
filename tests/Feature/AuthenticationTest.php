<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * トップページが正常に表示されること
     */
    public function test_welcome_page_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('まつり総合管理システム');
        $response->assertSee(route('login'));
    }

    /**
     * ログイン画面が正常に表示されること
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('運行本部 ログイン認証');
    }

    /**
     * 有効なアカウントで正しくログインできること
     */
    public function test_active_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->last_login_at);
    }

    /**
     * 誤ったパスワードでは認証されないこと
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * 停止中のユーザー（is_active = false）はログインできないこと
     */
    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * ログアウト処理が正常に完了し、セッションが無効化されること
     */
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * 未認証ユーザーがダッシュボードへアクセスした場合はログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    /**
     * 認証済みユーザーがダッシュボードに正常にアクセスできること
     */
    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'テスト管理者',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('本部運行総合ダッシュボード');
        $response->assertSee('テスト管理者');
    }
}
