<?php

namespace Tests\Feature;

use App\Models\FeatureSetting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeatureSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    /**
     * feature_settingsテーブルのcreated_atおよびupdated_atがdatetime型で定義されていること
     */
    public function test_feature_settings_table_uses_datetime_for_created_at_and_updated_at(): void
    {
        $this->assertTrue(Schema::hasTable('feature_settings'));
        $this->assertSame('datetime', Schema::getColumnType('feature_settings', 'created_at'));
        $this->assertSame('datetime', Schema::getColumnType('feature_settings', 'updated_at'));
    }

    /**
     * 手動でis_enabled=falseの場合、セルフ登録画面で停止案内が表示されること
     */
    public function test_join_screen_shows_closed_when_manually_disabled(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => false,
            ]
        );

        $response = $this->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('現在、セルフ登録を受け付けておりません');
        $response->assertSee('停止中（手動）');
        $response->assertDontSee('登録を完了してタイムテーブルを確認');
    }

    /**
     * 現在時刻がopened_atより前の場合、開始前案内が表示されること
     */
    public function test_join_screen_shows_closed_when_before_opened_at(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => true,
                'opened_at' => now()->addDays(2),
                'closed_at' => now()->addDays(5),
            ]
        );

        $response = $this->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('現在、セルフ登録を受け付けておりません');
        $response->assertSee('開始前');
        $response->assertDontSee('登録を完了してタイムテーブルを確認');
    }

    /**
     * 現在時刻がclosed_atより後の場合、受付終了案内が表示されること
     */
    public function test_join_screen_shows_closed_when_after_closed_at(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => true,
                'opened_at' => now()->subDays(5),
                'closed_at' => now()->subDay(),
            ]
        );

        $response = $this->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('現在、セルフ登録を受け付けておりません');
        $response->assertSee('受付終了');
        $response->assertDontSee('登録を完了してタイムテーブルを確認');
    }

    /**
     * 受付期間内の場合、正常に入力フォームが表示されること
     */
    public function test_join_screen_accessible_within_valid_period(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => true,
                'opened_at' => now()->subDay(),
                'closed_at' => now()->addDays(2),
            ]
        );

        $response = $this->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('スタッフ・ボランティア登録');
        $response->assertSee('登録を完了してタイムテーブルを確認');
    }

    /**
     * カスタム案内メッセージ（notice_message）が設定されている場合、案内画面に表示されること
     */
    public function test_custom_notice_message_is_displayed_when_closed(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => false,
                'notice_message' => '定員に達したため受付を締め切りました。多数のご応募ありがとうございました。',
            ]
        );

        $response = $this->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('定員に達したため受付を締め切りました。多数のご応募ありがとうございました。');
    }

    /**
     * 受付停止時にPOST送信された場合、登録が拒否されること
     */
    public function test_store_is_blocked_when_registration_closed(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => false,
            ]
        );

        $initialCount = Staff::count();

        $response = $this->post(route('staff.join.store'), [
            'name' => '山田 太郎',
            'kana' => 'ヤマダ タロウ',
        ]);

        $response->assertRedirect(route('staff.join'));
        $response->assertSessionHas('error');
        $this->assertEquals($initialCount, Staff::count());
    }

    /**
     * 受付停止中であっても、登録済みスタッフはCookie経由で完了画面を閲覧できること
     */
    public function test_registered_staff_can_view_completed_even_when_registration_closed(): void
    {
        FeatureSetting::updateOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            [
                'name' => 'スタッフセルフ登録',
                'is_enabled' => false,
            ]
        );

        $staff = Staff::factory()->selfRegistered()->create([
            'name' => '登録済 太郎',
            'is_active' => true,
        ]);

        $response = $this->withCookie('staff_token', $staff->access_token)
            ->get(route('staff.join.completed'));

        $response->assertStatus(200);
        $response->assertSee('登録済 太郎');
    }

    /**
     * 管理者・オペレーターが機能設定（日時・メッセージ・フラグ）を更新できること
     */
    public function test_authorized_user_can_update_feature_settings(): void
    {
        $this->actingAs($this->operator);

        $response = $this->put(route('feature-settings.update', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION), [
            'is_enabled' => '1',
            'opened_at' => now()->format('Y-m-d H:i:s'),
            'closed_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notice_message' => '今週末までの限定受付です。',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $feature = FeatureSetting::where('feature_key', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION)->first();
        $this->assertNotNull($feature);
        $this->assertTrue($feature->is_enabled);
        $this->assertSame('今週末までの限定受付です。', $feature->notice_message);
    }

    /**
     * closed_atがopened_atより前の場合バリデーションエラーとなること
     */
    public function test_validation_rejects_closed_at_before_opened_at(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('feature-settings.update', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION), [
            'is_enabled' => '1',
            'opened_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'closed_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHasErrors(['closed_at']);
    }

    /**
     * 管理者・オペレーターがワンクリックで即時トグルできること
     */
    public function test_authorized_user_can_toggle_feature(): void
    {
        $this->actingAs($this->operator);

        $feature = FeatureSetting::firstOrCreate(
            ['feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION],
            ['name' => 'スタッフセルフ登録', 'is_enabled' => true]
        );
        $this->assertTrue($feature->is_enabled);

        // トグル実行 -> OFF
        $response = $this->patch(route('feature-settings.toggle', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $feature->refresh();
        $this->assertFalse($feature->is_enabled);

        // 再度トグル実行 -> ON
        $this->patch(route('feature-settings.toggle', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION));
        $feature->refresh();
        $this->assertTrue($feature->is_enabled);
    }

    /**
     * 権限のない一般ユーザーや未認証ユーザーは設定更新・トグルできないこと
     */
    public function test_unauthorized_user_cannot_update_or_toggle_feature(): void
    {
        // 未ログイン
        $response = $this->put(route('feature-settings.update', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION), [
            'is_enabled' => '1',
        ]);
        $response->assertRedirect(route('login'));

        // 一般ユーザー
        $this->actingAs($this->regularUser);

        $putResponse = $this->put(route('feature-settings.update', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION), [
            'is_enabled' => '1',
        ]);
        $putResponse->assertStatus(403);

        $patchResponse = $this->patch(route('feature-settings.toggle', FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION));
        $patchResponse->assertStatus(403);
    }

    /**
     * スタッフ一覧画面にセルフ登録受付ステータスカードが表示されること
     */
    public function test_staff_index_displays_self_registration_status(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('staff.index'));

        $response->assertStatus(200);
        $response->assertSee('Webセルフ登録機能');
        $response->assertSee('即時停止');
    }
}
