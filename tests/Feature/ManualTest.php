<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーはログイン画面へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_when_accessing_manual(): void
    {
        $response = $this->get(route('manual.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * ログイン済みユーザーはマニュアル画面を閲覧できること
     */
    public function test_authenticated_user_can_view_manual_page(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('manual.index'));

        $response->assertStatus(200);
        $response->assertSee('システム操作マニュアル');
        $response->assertSee('第1章 システム概要');
        $response->assertSee('第2章 本部ダッシュボードの見方と進捗管理');
        $response->assertSee('第4章 運行タイムテーブルマトリクス表示');
        $response->assertSee('第7章 現場スタッフ向けマイシフト');
        $response->assertSee('付録 よくある質問（FAQ）');
        $response->assertSee('02_dashboard.png');
        $response->assertSee('05_timetable_matrix.png');
        $response->assertSee('11_myshift_mobile.png');
    }

    /**
     * ログイン後のヘッダーナビゲーションにマニュアルリンクが存在すること
     */
    public function test_manual_link_is_present_in_navigation_menu(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('manual.index'));
        $response->assertSee('マニュアル');
    }
}
