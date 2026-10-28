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
        $response->assertSee('第5章 タイムテーブル台帳・担当班（レーン）編成・人員アサイン');
        $response->assertSee('第6章 スタッフ管理・名簿・シフト編成・カード印刷');
        $response->assertSee('第7章 現場動態管理・マイシフト運行・リアルタイム監視');
        $response->assertSee('付録 よくある質問（FAQ）');
        $response->assertSee('02_dashboard.png');
        $response->assertSee('05_timetable_matrix.png');
        $response->assertSee('11_myshift_mobile.png');

        // 第1章・第5章の追加要素
        $response->assertSee('👑 班リーダー (現場責任者)');
        $response->assertSee('同一時間帯の重複アサイン自動検知（コンフリクト警告ダイアログ）');
        $response->assertSee('スタッフタイムライン（ガントチャート）による過密防止・過不足調整');

        // 第6章・第7章・FAQの追加要素
        $response->assertSee('前年度Excel名簿のCSV一括登録');
        $response->assertSee('Webセルフ登録 & スマート自動名寄せ');
        $response->assertSee('名札紛失時の旧QR即時失効 & 再発行');
        $response->assertSee('「Now & Next」による現場自律移動');
        $response->assertSee('現場到着チェックイン（第1タスク自動連動）');
        $response->assertSee('ボランティア現場心得 & 熱中症予防ルール');
        $response->assertSee('アクセストークン再発行');
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
