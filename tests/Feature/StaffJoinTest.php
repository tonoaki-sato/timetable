<?php

namespace Tests\Feature;

use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffJoinTest extends TestCase
{
    use RefreshDatabase;

    /**
     * LIFF未設定の開発環境でもセルフ登録画面が正常に表示されること
     */
    public function test_join_screen_can_be_rendered_without_liff_config(): void
    {
        // LINE_LIFF_ID が未設定（空）の状態
        config(['services.line.liff_id' => '']);

        $response = $this->get(route('staff.join'));
        $response->assertStatus(200);
        $response->assertSee('スタッフ・ボランティア登録');
        $response->assertSee('お名前（氏名）');
        $response->assertSee('フリガナ（全角カナ）');
        $response->assertSee('staff-join-liff.js');
    }

    /**
     * LINE連携なし（氏名・フリガナのみ）の最小入力でセルフ登録できること
     */
    public function test_staff_can_register_with_minimal_input_offline(): void
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
            'line_user_id' => null,
            'line_display_name' => null,
        ]);

        $staff = Staff::where('name', '神田 幸助')->first();
        $this->assertNotNull($staff);
        $this->assertSame(32, strlen($staff->access_token));
        $this->assertFalse($staff->is_checked_in);
    }

    /**
     * LIFF経由を想定したダミーLINE情報を含むセルフ登録ができること (外部通信なし)
     */
    public function test_staff_can_register_with_simulated_line_data(): void
    {
        $response = $this->post(route('staff.join.store'), [
            'name' => '青山 葵',
            'kana' => 'アオヤマ アオイ',
            'line_user_id' => 'U1234567890abcdef1234567890abcdef',
            'line_display_name' => 'あおい@青大',
            'line_picture_url' => 'https://example.com/avatar.jpg',
        ]);

        $response->assertRedirect(route('staff.join.completed'));

        $this->assertDatabaseHas('staff', [
            'name' => '青山 葵',
            'kana' => 'アオヤマ アオイ',
            'line_user_id' => 'U1234567890abcdef1234567890abcdef',
            'line_display_name' => 'あおい@青大',
            'line_picture_url' => 'https://example.com/avatar.jpg',
            'registered_via' => 'self',
        ]);
    }

    /**
     * 非LINE環境での手動LINE表示名入力での登録ができること
     */
    public function test_staff_can_register_with_manual_line_name(): void
    {
        $response = $this->post(route('staff.join.store'), [
            'name' => '松田 健',
            'kana' => 'マツダ ケン',
            'manual_line_display_name' => 'けんちゃん',
        ]);

        $response->assertRedirect(route('staff.join.completed'));

        $this->assertDatabaseHas('staff', [
            'name' => '松田 健',
            'kana' => 'マツダ ケン',
            'line_display_name' => 'けんちゃん',
            'line_user_id' => null,
            'registered_via' => 'self',
        ]);
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
}
