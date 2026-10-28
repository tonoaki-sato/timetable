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
}
