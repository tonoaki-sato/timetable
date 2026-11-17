<?php

namespace Tests\Feature;

use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteBaseMapViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '2026年本祭 1日目',
            'target_date' => '2026-10-18',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * 未ログインユーザーはログイン画面にリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertRedirect(route('login'));
    }

    /**
     * 白地図エディタ画面が正常に表示され、必要なDOM要素と専用Viteスクリプトが含まれる
     */
    public function test_editor_screen_renders_successfully_with_vite_script(): void
    {
        // 既存の白地図レコードを作成
        $siteBaseMap = SiteBaseMap::create([
            'timetable_id' => $this->timetable->id,
            'name' => '商店街平面図 260813版 A3 1/400',
            'cad_version' => '260813',
            'paper_size' => 'A3',
            'scale_ratio' => 26.000,
            'width_meters' => 32.4,
            'height_meters' => 45.8,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);

        // タイトルや白地図メタ情報
        $response->assertSee('白地図配置エディタ');
        $response->assertSee('A3 1/400 CADベクター図面');
        $response->assertSee('図面配置サマリー');

        // 主要なDOMコンテナ・ツールバーボタン
        $response->assertSee('id="site-layout-editor-app"', false);
        $response->assertSee('id="site-map-canvas-container"', false);
        $response->assertSee('id="site-map-viewport"', false);
        $response->assertSee('id="btn-zoom-in"', false);
        $response->assertSee('id="btn-zoom-out"', false);
        $response->assertSee('id="btn-zoom-fit"', false);

        // 画面専用 Vite JavaScript のアセット読み込み検証（規約遵守）
        $response->assertSee('site-layout-editor', false);

        // 厳格な CSP 検証: インライン script タグや onclick ハンドラが存在しないこと
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/onclick\s*=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/onload\s*=/i', $content);
        // 通常のインラインスクリプトブロック（<script>...</script>でsrcを持たないもの）がないことを確認
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*src=)[^>]*>/i', $content);
    }

    /**
     * 白地図未登録の場合はデフォルト白地図が自動生成される
     */
    public function test_editor_creates_default_base_map_if_none_exists(): void
    {
        $this->assertDatabaseCount('site_base_maps', 0);

        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);
        $this->assertDatabaseCount('site_base_maps', 1);

        $this->assertDatabaseHas('site_base_maps', [
            'timetable_id' => $this->timetable->id,
            'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
            'is_active' => true,
        ]);
    }
}
