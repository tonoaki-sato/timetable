<?php

namespace Tests\Feature;

use App\Models\AnnualSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * 年間スケジュール一覧が表示され、年度・ステータス等で絞り込みできること
     */
    public function test_index_displays_schedules_and_filters(): void
    {
        $this->actingAs($this->user);

        $schedule2026 = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '2026年本祭パレード',
            'category' => 'operation',
            'status' => 'planned',
        ]);

        $schedule2025 = AnnualSchedule::factory()->create([
            'target_year' => 2025,
            'title' => '2025年旧年度イベント',
            'category' => 'committee',
            'status' => 'completed',
        ]);

        // 2026年度のみ表示されること
        $response = $this->get(route('annual-schedules.index', ['year' => 2026]));
        $response->assertStatus(200);
        $response->assertSee('2026年本祭パレード');
        $response->assertDontSee('2025年旧年度イベント');

        // カテゴリ絞り込み
        $catResponse = $this->get(route('annual-schedules.index', ['year' => 2026, 'category' => 'committee']));
        $catResponse->assertStatus(200);
        $catResponse->assertDontSee('2026年本祭パレード');

        // キーワード検索
        $searchResponse = $this->get(route('annual-schedules.index', ['year' => 2026, 'search' => 'パレード']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('2026年本祭パレード');
    }

    /**
     * 正しいデータで新規登録できること
     */
    public function test_can_create_annual_schedule(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => '安全祈願祭・例大祭',
            'category' => 'ceremony',
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-18',
            'location' => '神社境内',
            'status' => 'planned',
            'description' => '関係者一同参列のこと。',
        ]);

        $response->assertRedirect(route('annual-schedules.index', ['year' => 2026]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('annual_schedules', [
            'title' => '安全祈願祭・例大祭',
            'target_year' => 2026,
            'category' => 'ceremony',
            'location' => '神社境内',
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * バリデーションエラーが正しく機能すること
     */
    public function test_validation_rules_for_creating_schedule(): void
    {
        $this->actingAs($this->user);

        // 必須項目未入力
        $response = $this->post(route('annual-schedules.store'), []);
        $response->assertSessionHasErrors(['target_year', 'title', 'category', 'start_date', 'status']);

        // 終了日が開始日より前の場合
        $invalidDateResponse = $this->post(route('annual-schedules.store'), [
            'target_year' => 2026,
            'title' => '日付逆転テスト',
            'category' => 'committee',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-10',
            'status' => 'planned',
        ]);
        $invalidDateResponse->assertSessionHasErrors('end_date');
    }

    /**
     * スケジュールを正常に更新できること
     */
    public function test_can_update_annual_schedule(): void
    {
        $this->actingAs($this->user);

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '変更前タイトル',
            'category' => 'committee',
            'start_date' => '2026-05-10',
            'status' => 'planned',
        ]);

        $response = $this->put(route('annual-schedules.update', $schedule), [
            'target_year' => 2026,
            'title' => '変更後新タイトル',
            'category' => 'operation',
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-16',
            'location' => '市民会館2F',
            'status' => 'completed',
            'description' => '議事録確認済み',
        ]);

        $response->assertRedirect(route('annual-schedules.show', $schedule));
        $response->assertSessionHas('success');

        $schedule->refresh();
        $this->assertSame('変更後新タイトル', $schedule->title);
        $this->assertSame('operation', $schedule->category);
        $this->assertSame('completed', $schedule->status);
        $this->assertSame('市民会館2F', $schedule->location);
    }

    /**
     * スケジュールを正常に削除できること
     */
    public function test_can_delete_annual_schedule(): void
    {
        $this->actingAs($this->user);

        $schedule = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '削除対象行事',
        ]);

        $response = $this->delete(route('annual-schedules.destroy', $schedule));

        $response->assertRedirect(route('annual-schedules.index', ['year' => 2026]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('annual_schedules', [
            'id' => $schedule->id,
        ]);
    }
}
