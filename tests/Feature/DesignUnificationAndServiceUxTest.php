<?php

namespace Tests\Feature;

use App\Models\FeatureSetting;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignUnificationAndServiceUxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'target_date' => '2026-10-18',
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * 一覧画面でルートパンくず（「ホーム」・ダッシュボードリンク）が重複出力されないこと
     */
    public function test_index_views_do_not_duplicate_root_breadcrumb(): void
    {
        $routes = [
            route('vendors.index'),
            route('equipments.index'),
            route('organizations.index'),
            route('equipment-patterns.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertStatus(200);

            $html = (string) $response->getContent();
            $this->assertSame(1, substr_count($html, '<span>🏮</span><span>ホーム</span>'), "Duplicate root breadcrumb on {$url}");
            $this->assertSame(1, substr_count($html, 'ホーム'), "Unexpected extra ホーム occurrence on {$url}");
        }
    }

    /**
     * グローバルレイアウトと子ビュー間でフラッシュメッセージが二重描画されないこと
     */
    public function test_flash_messages_are_rendered_only_once_per_page(): void
    {
        $routes = [
            route('vendors.index'),
            route('users.index'),
            route('equipments.index'),
            route('timetables.locations.index', $this->timetable),
            route('timetables.lanes.index', $this->timetable),
            route('organizations.index'),
            route('timetables.layouts.booths', $this->timetable),
        ];

        $message = '保存しました（重複テスト）';

        foreach ($routes as $url) {
            $response = $this->actingAs($this->admin)
                ->withSession(['success' => $message])
                ->get($url);

            $response->assertStatus(200);
            $html = (string) $response->getContent();
            $this->assertSame(1, substr_count($html, $message), "Duplicate flash message rendered on {$url}");
        }
    }

    /**
     * 本番相当環境ではログイン画面のテスト用初期アカウント案内が非表示になり、パスワード表示トグルが存在すること
     */
    public function test_login_screen_hides_demo_accounts_in_production_and_renders_password_toggle(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertDontSee('テスト用初期アカウント');
        $response->assertDontSee('operator@example.com');
        $response->assertDontSee('共通パスワード');
        $response->assertSee('data-toggle-password="password"', false);
    }

    /**
     * トップページが運行日データとスタッフセルフ登録受付状況に応じて動的に描画されること
     */
    public function test_welcome_page_displays_dynamic_event_date_and_conditional_self_registration_cta(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => true,
                'opened_at' => null,
                'closed_at' => null,
            ]
        );

        $openResponse = $this->get(route('home'));
        $openResponse->assertStatus(200);
        $openResponse->assertSee('2026年10月18日');
        $openResponse->assertSee(route('staff.join'), false);

        FeatureSetting::where('feature_key', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION)
            ->update(['is_enabled' => false]);

        $closedResponse = $this->get(route('home'));
        $closedResponse->assertStatus(200);
        $closedResponse->assertSee('2026年10月18日');
        $closedResponse->assertDontSee(route('staff.join'), false);
    }

    /**
     * 公開画面・モバイル画面でピンチズーム制限がなく、ヘッドメタ情報（favicon）が設定されていること
     */
    public function test_public_and_mobile_views_allow_zoom_and_include_favicon(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => true,
                'opened_at' => null,
                'closed_at' => null,
            ]
        );

        $staff = Staff::factory()->create(['is_active' => true]);

        foreach ([route('staff.join'), route('staff.myshift', ['token' => $staff->access_token])] as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertDontSee('user-scalable=no', false);
            $response->assertDontSee('maximum-scale=1.0', false);
            $response->assertSee('favicon.svg', false);
        }
    }

    /**
     * 未定義URLアクセス時に日本語404エラーページが返却され、CSP違反（インラインstyle/script/on*）を含まないこと
     */
    public function test_unknown_route_renders_csp_compliant_japanese_404_page(): void
    {
        $response = $this->get('/non-existent-route-for-404-test');

        $response->assertStatus(404);
        $response->assertSee('ページが見つかりません');
        $response->assertSee('favicon.svg', false);

        $html = (string) $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $html);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html);
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|nonce=))[^>]*>/i', $html);
    }

    /**
     * 主要一覧画面がヘッダー主ボタン案Aと赤系セマンティックカラー統一（red-*）に準拠していること
     */
    public function test_index_views_use_unified_header_actions_and_semantic_red_colors(): void
    {
        $routes = [
            route('vendors.index'),
            route('staff.index'),
            route('timetables.index'),
            route('schedules.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertStatus(200);
            $response->assertSee('hover:bg-indigo-600', false);
            $response->assertDontSee('rose-', false);
        }
    }
}
