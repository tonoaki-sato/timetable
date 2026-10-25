<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffSecurityReissueTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * 公開セルフ照合ルート（/my/find）が完全に削除されており、404を返すこと
     */
    public function test_my_find_route_is_completely_removed_and_returns_404(): void
    {
        $responseGet = $this->get('/my/find');
        $responseGet->assertStatus(404);

        $responsePost = $this->post('/my/find', [
            'name' => '木村 拓也',
            'kana' => 'キムラ タクヤ',
        ]);
        $responsePost->assertStatus(404);
    }

    /**
     * トークン再生成を実行すると旧URLは即時無効化（404）され、新URLでアクセス可能になること
     */
    public function test_token_regeneration_invalidates_old_url_and_activates_new_url(): void
    {
        $staff = Staff::factory()->create([
            'name' => '佐々木 健太',
            'access_token' => 'old_access_token_12345678901234',
        ]);

        // 旧トークンでのマイシフトアクセスが有効であることを確認
        $this->get(route('staff.myshift', ['token' => 'old_access_token_12345678901234']))
            ->assertStatus(200)
            ->assertSee('佐々木 健太');

        // 本部管理者がトークン再生成を実行
        $response = $this->actingAs($this->adminUser)
            ->post(route('staff.regenerate-token', $staff));

        $response->assertRedirect(route('staff.show', $staff));
        $response->assertSessionHas('reissued_success', true);

        // 旧トークンでのアクセスが即座に404になること（古い名札の失効）
        $this->get(route('staff.myshift', ['token' => 'old_access_token_12345678901234']))
            ->assertStatus(404);

        // 新しいトークンでアクセス可能であることを確認
        $staff->refresh();
        $this->assertNotEquals('old_access_token_12345678901234', $staff->access_token);

        $this->get(route('staff.myshift', ['token' => $staff->access_token]))
            ->assertStatus(200)
            ->assertSee('佐々木 健太');
    }

    /**
     * トークン再生成によって再発行回数（reissue_count）が加算され、日時が記録されること
     */
    public function test_token_regeneration_increments_reissue_count_and_sets_timestamp(): void
    {
        $staff = Staff::factory()->create([
            'reissue_count' => 0,
            'token_reissued_at' => null,
        ]);

        $this->assertFalse($staff->is_reissued);

        // 1回目の再生成
        $this->actingAs($this->adminUser)
            ->post(route('staff.regenerate-token', $staff));

        $staff->refresh();
        $this->assertEquals(1, $staff->reissue_count);
        $this->assertNotNull($staff->token_reissued_at);
        $this->assertTrue($staff->is_reissued);

        // 2回目の再生成
        $this->actingAs($this->adminUser)
            ->post(route('staff.regenerate-token', $staff));

        $staff->refresh();
        $this->assertEquals(2, $staff->reissue_count);
    }

    /**
     * 再発行された名札印刷カードに「再発行 (Ver.X)」バッジが印字されること
     */
    public function test_reissued_badge_is_displayed_on_print_card(): void
    {
        // 1. 初回（未再発行）スタッフの名札印刷
        $normalStaff = Staff::factory()->create([
            'name' => '通常 スタッフ',
            'reissue_count' => 0,
        ]);

        $responseNormal = $this->actingAs($this->adminUser)
            ->get(route('staff.print-card', $normalStaff));

        $responseNormal->assertStatus(200);
        $responseNormal->assertDontSee('再発行 (Ver.');

        // 2. 再発行済みスタッフの名札印刷
        $reissuedStaff = Staff::factory()->reissued(1)->create([
            'name' => '再発行 スタッフ',
        ]);

        $responseReissued = $this->actingAs($this->adminUser)
            ->get(route('staff.print-card', $reissuedStaff));

        $responseReissued->assertStatus(200);
        $responseReissued->assertSee('再発行 (Ver.2)');
    }

    /**
     * スタッフ詳細画面に名札紛失・セキュリティ再発行ボタンや再発行履歴が表示されること
     */
    public function test_staff_show_screen_displays_reissue_button_and_status(): void
    {
        $staff = Staff::factory()->reissued(1)->create([
            'name' => '山田 太郎',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('staff.show', $staff));

        $response->assertStatus(200);
        $response->assertSee('名札紛失・セキュリティ再発行');
        $response->assertSee('再発行 1回目');
        $response->assertSee('URLコピー');
    }
}
