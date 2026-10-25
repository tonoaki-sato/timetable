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
        $response->assertSee('まつり運行の');
        $response->assertSee('見える化');
        $response->assertSee('より適切な指示と安心の運営');
        $response->assertSee('当日の私の混乱状況');
        $response->assertSee('幹事の皆様に教えていただきたい「3つの情報」');
        $response->assertSee('私（佐藤）が代行');
        $response->assertSee('発表者用トークスクリプト');
        $response->assertSee('exitFullscreenBtn');
        $response->assertSee('全画面解除');

        // 電話・無線に関する記述が削除されていること
        $response->assertDontSee('電話');
        $response->assertDontSee('無線');

        // CSP準拠：インラインイベントハンドラやstyle属性が存在しないこと
        $response->assertDontSee('onclick=');
        $response->assertDontSee('onchange=');
        $response->assertDontSee('style=');

        // 正しい画像アセットが参照されていること（404だった10_staff_card_printではなく09_staff_print_cards）
        $response->assertSee('09_staff_print_cards.png');
        $response->assertDontSee('10_staff_card_print.png');

        // 待機場所（本陣受付裏）および多種多様な備品（カラーコーン、ニッパー、交通整理用棒等）が反映されていること
        $response->assertSee('本陣受付裏');
        $response->assertSee('カラーコーン');
        $response->assertSee('ニッパー');
        $response->assertSee('交通整理用の棒');

        // リーダーに原因を帰するような表現が含まれていないこと
        $response->assertDontSee('各リーダーの頭の中にしか');
        $response->assertDontSee('頭の中にしか');
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
