<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーはログイン画面へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_when_accessing_presentation(): void
    {
        $response = $this->get(route('presentation.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * ログイン済みユーザーは幹事会プレゼン画面を閲覧できること
     */
    public function test_authenticated_user_can_view_presentation_page(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('presentation.index'));

        $response->assertStatus(200);
        $response->assertSee('まつり実行委員会 幹事会向けプレゼンテーション');
        $response->assertSee('当日の負担を半減させる');
        $response->assertSee('情報一元管理');
        $response->assertSee('幹事の皆様に教えていただきたい「3つの情報」');
        $response->assertSee('発表者用トークスクリプト');
        $response->assertSee('exitFullscreenBtn');
        $response->assertSee('全画面解除');

        // CSP準拠：インラインイベントハンドラやstyle属性が存在しないこと
        $response->assertDontSee('onclick=');
        $response->assertDontSee('onchange=');
        $response->assertDontSee('style=');

        // 正しい画像アセットが参照されていること（404だった10_staff_card_printではなく09_staff_print_cards）
        $response->assertSee('09_staff_print_cards.png');
        $response->assertDontSee('10_staff_card_print.png');
    }

    /**
     * ログイン後のヘッダーナビゲーションにプレゼンリンクが存在すること
     */
    public function test_presentation_link_is_present_in_navigation_menu(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('presentation.index'));
        $response->assertSee('プレゼン');
    }
}
