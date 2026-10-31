<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffJoinTest extends TestCase
{
    use RefreshDatabase;

    /**
     * セルフ登録画面が正常に表示されること
     */
    public function test_join_screen_can_be_rendered(): void
    {
        $response = $this->get(route('staff.join'));
        $response->assertStatus(200);
        $response->assertSee('スタッフ・ボランティア登録');
        $response->assertSee('お名前（氏名）');
        $response->assertSee('フリガナ（全角カナ）');
    }

    /**
     * 氏名・フリガナの入力でセルフ登録できること
     */
    public function test_staff_can_register_via_join(): void
    {
        $response = $this->post(route('staff.join.store'), [
            'name' => '神田 幸助',
            'kana' => 'カンダ コウスケ',
        ]);

        $response->assertRedirect(route('staff.join.completed'));

        $this->assertDatabaseHas('staff', [
            'name' => '神田 幸助',
            'kana' => 'カンダ コウスケ',
            'registered_via' => 'self',
            'is_active' => true,
        ]);

        $staff = Staff::where('name', '神田 幸助')->first();
        $this->assertNotNull($staff);
        $this->assertSame(32, strlen($staff->access_token));
        $this->assertFalse($staff->is_checked_in);
    }

    /**
     * 氏名・フリガナが未入力の場合バリデーションエラーとなること
     */
    public function test_registration_requires_name_and_kana(): void
    {
        $response = $this->post(route('staff.join.store'), []);
        $response->assertSessionHasErrors(['name', 'kana']);
    }

    /**
     * 氏名・フリガナの文字数上限バリデーション
     */
    public function test_registration_validates_max_length(): void
    {
        $response = $this->post(route('staff.join.store'), [
            'name' => str_repeat('あ', 256),
            'kana' => str_repeat('ア', 256),
        ]);

        $response->assertSessionHasErrors(['name', 'kana']);
    }

    /**
     * 登録完了画面で専用URLや案内が表示されること
     */
    public function test_completed_screen_displays_token_url_and_guidance(): void
    {
        $staff = Staff::factory()->selfRegistered()->create([
            'name' => '斉藤 翔',
            'kana' => 'サイトウ ショウ',
        ]);

        $response = $this->withSession(['registered_staff_id' => $staff->id])
            ->get(route('staff.join.completed'));

        $response->assertStatus(200);
        $response->assertSee('斉藤 翔');
        $response->assertSee('私のタイムテーブルを見る');
        $response->assertSee($staff->access_token);
    }

    /**
     * 本部先行登録されたスタッフが後から/joinで登録した際、新規作成されず自動統合されること
     */
    public function test_pre_registered_staff_is_automatically_bound_when_registering_via_join(): void
    {
        $existingStaff = Staff::factory()->create([
            'name' => '鈴木 一郎',
            'kana' => 'スズキ イチロウ',
            'registered_via' => 'admin',
        ]);

        $initialCount = Staff::count();

        $response = $this->post(route('staff.join.store'), [
            'name' => '鈴木 一郎',
            'kana' => 'スズキ イチロウ',
        ]);

        $response->assertRedirect(route('staff.join.completed'));
        $this->assertEquals($initialCount, Staff::count(), '新規レコードが作成されず既存レコードが再利用されること');

        $existingStaff->refresh();
        $this->assertNotNull($existingStaff->last_accessed_at);
    }

    /**
     * 有効なstaff_token Cookieを保持している場合、セルフ登録画面から完了画面へリダイレクトされること（二重登録防止）
     */
    public function test_join_screen_redirects_to_completed_when_staff_token_cookie_present(): void
    {
        $staff = Staff::factory()->selfRegistered()->create([
            'name' => '佐藤 太郎',
            'kana' => 'サトウ タロウ',
            'is_active' => true,
        ]);

        $response = $this->withCookie('staff_token', $staff->access_token)
            ->get(route('staff.join'));

        $response->assertRedirect(route('staff.join.completed'));
        $response->assertSessionHas('info', 'すでにスタッフ登録が完了しています。');
    }

    /**
     * 有効なstaff_token Cookieを保持している状態でPOST送信した場合、新規登録されず完了画面へリダイレクトされること
     */
    public function test_store_does_not_duplicate_staff_when_cookie_present(): void
    {
        $staff = Staff::factory()->selfRegistered()->create([
            'name' => '田中 次郎',
            'kana' => 'タナカ ジロウ',
            'is_active' => true,
        ]);

        $initialCount = Staff::count();

        $response = $this->withCookie('staff_token', $staff->access_token)
            ->post(route('staff.join.store'), [
                'name' => '偽名 三郎',
                'kana' => 'ギメイ サブロウ',
            ]);

        $response->assertRedirect(route('staff.join.completed'));
        $this->assertEquals($initialCount, Staff::count(), '二重登録が阻止されレコードが増加しないこと');
        $this->assertDatabaseMissing('staff', ['name' => '偽名 三郎']);
    }

    /**
     * 無効なCookie（存在しないトークン）の場合は通常通り登録フォームが表示されること
     */
    public function test_invalid_cookie_does_not_prevent_registration(): void
    {
        $response = $this->withCookie('staff_token', 'non-existent-token-123456789012')
            ->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('スタッフ・ボランティア登録');
    }

    /**
     * 非アクティブなスタッフのCookieの場合は通常通り登録フォームが表示されること
     */
    public function test_inactive_staff_cookie_does_not_prevent_registration(): void
    {
        $inactiveStaff = Staff::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->withCookie('staff_token', $inactiveStaff->access_token)
            ->get(route('staff.join'));

        $response->assertStatus(200);
        $response->assertSee('スタッフ・ボランティア登録');
    }
}
