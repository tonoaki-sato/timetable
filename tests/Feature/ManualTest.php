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
        $response->assertSee('第3章 まつり年間工程・総合カレンダー & 会議・LINE告知');
        $response->assertSee('第4章 運行タイムテーブルマトリクス表示');
        $response->assertSee('第5章 運行日程・担当班編成・タスク計画・人員アサイン');
        $response->assertSee('第6章 スタッフ管理・名簿・シフト編成・カード印刷');
        $response->assertSee('第7章 資材・機材物流管理 & 拠点・会場管理');
        $response->assertSee('第8章 現場動態管理・マイシフト運行・リアルタイム監視');
        $response->assertSee('第9章 ユーザー管理・権限設定（管理者向け）');
        $response->assertSee('付録 よくある質問（FAQ）');
        $response->assertSee('02_dashboard.png');
        $response->assertSee('05_timetable_matrix.png');
        $response->assertSee('11_myshift_mobile.png');

        // 第1章・第3章の追加要素
        $response->assertSee('👑 班長 (担当レーン主担当)');
        $response->assertSee('幹事会プレゼン・合意形成スライド');
        $response->assertSee('ワンクリックLINE告知テキスト生成');

        // 第4章・第5章の追加要素
        $response->assertDontSee('運行準備ナビ');
        $response->assertSee('タスク計画');
        $response->assertSee('同一時間帯の重複アサイン自動検知（コンフリクト警告ダイアログ）');
        $response->assertSee('スタッフタイムライン（ガントチャート）による過密防止・過不足調整');
        $response->assertSee('人員配置表（A4横・班別タスク一覧）の印刷出力');

        // 第6章・第7章の追加要素
        $response->assertSee('前年度Excel名簿のCSV一括登録');
        $response->assertSee('Webセルフ登録 & スマート自動名寄せ');
        $response->assertSee('Webセルフ登録（/join）と本部公開設定・締切管理');
        $response->assertSee('登録案内文ワンクリックコピー');
        $response->assertSee('名札紛失時の旧QR即時失効 & 再発行');
        $response->assertSee('定番セットテンプレート適用');
        $response->assertSee('資材搬出入計画書・チェックリストのA4印刷');
        $response->assertSee('拠点管理 & 利用時間帯・交通規制スケジュール');

        // 第8章・第9章・FAQの追加要素
        $response->assertSee('「Now & Next」による現場自律移動');
        $response->assertSee('現場到着チェックイン（第1タスク自動連動）');
        $response->assertSee('応援要請 & トラブル報告（レーンリクエスト）');
        $response->assertSee('ボランティア現場心得 & 熱中症予防ルール');
        $response->assertSee('アクセストークン再発行');
    }

    /**
     * 刷新されたマニュアルに全機能一覧・シナリオ別操作手順・最新機能・帳票一覧が含まれること
     */
    public function test_refreshed_manual_displays_feature_catalog_scenarios_and_latest_features(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('manual.index'));

        $response->assertStatus(200);

        // 1. 全機能一覧・画面カタログ
        $response->assertSee('id="sec-feature-catalog"', false);
        $response->assertSee('全機能一覧・画面カタログ');
        $response->assertSee('組織管理');
        $response->assertSee('出展・出店者マスター');
        $response->assertSee('配置計画・配置一覧');
        $response->assertSee('白地図配置エディタ');
        $response->assertSee('搬出入パターン');
        $response->assertSee('搬出入タイムライン');
        $response->assertSee('横断クイック検索');
        $response->assertSee('Ctrl+K');
        $response->assertSee('現場要請通知ベル');
        $response->assertSee('運行日コンテキストバー');

        // 2. シナリオ別操作手順ガイド (全8シナリオ + フィルタUI)
        $response->assertSee('id="sec-scenarios"', false);
        $response->assertSee('シナリオ別操作手順ガイド');
        $response->assertSee('Scenario 01');
        $response->assertSee('Scenario 08');
        $response->assertSee('data-scenario-filter="all"', false);
        $response->assertSee('data-scenario-category="planning"', false);
        $response->assertSee('data-scenario-category="venue"', false);
        $response->assertSee('data-scenario-category="operation"', false);
        $response->assertSee('data-scenario-category="trouble"', false);

        // 3. 最新機能・帳票出力一覧 (区分A / 区分B)
        $response->assertSee('実行委員会の組織管理（部門・係・組織図・PDF出力）');
        $response->assertSee('共通タスク一括登録（全班・複数班共通業務）');
        $response->assertSee('白地図配置エディタの便利ツール＆ショートカット');
        $response->assertSee('20m衛生圏チェック');
        $response->assertSee('帳票・PDF・CSV・iCal 出力総覧（区分A / 区分B）');
        $response->assertSee('組織図 (PDF)');
        $response->assertSee('白地図配置図面 (A3 1/400)');

        // 4. 検索・FAQ操作UIの制御属性
        $response->assertSee('id="manualSearchClear"', false);
        $response->assertSee('id="manualSearchCount"', false);
        $response->assertSee('id="manualSearchEmpty"', false);
        $response->assertSee('data-action="toggle-accordions"', false);

        // 5. 内部識別コードが露出していないこと
        $response->assertDontSee('ZONE_A');
        $response->assertDontSee('AREA_KITAGAWA');
        $response->assertDontSee('KIDS_VILLAGE');
    }

    /**
     * マニュアル画面はCSP完全準拠でありインラインイベントハンドラを持たないこと
     */
    public function test_manual_page_strictly_complies_with_csp_without_inline_event_handlers(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('manual.index'));

        $response->assertStatus(200);

        // CSPヘッダーが付与されていること
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("script-src 'self'", $csp);

        // HTML本文にインラインイベントハンドラ・インラインスタイル・未保護スクリプトが含まれていないこと
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/href=["\']javascript:/i', $content);

        // 外部ヘルパースクリプトおよびデータ属性が使われていること
        $this->assertStringContainsString('js/manual-helper.js', $content);
        $this->assertStringContainsString('data-action="print"', $content);
        $this->assertStringContainsString('data-modal-image', $content);
        $this->assertStringContainsString('data-action="close-modal"', $content);
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
