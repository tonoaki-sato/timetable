<?php

namespace Tests\Feature;

use App\Models\EquipmentLogisticsPattern;
use App\Models\Location;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentPatternManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    private User $viewer;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $timetable = Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目']);
        $this->location = Location::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '番所地下倉庫',
        ]);
    }

    /**
     * 未ログインユーザーはログイン画面にリダイレクトされること
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('equipment-patterns.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * 搬出入パターン一覧画面が正常に描画され、timetables/1/tasks 準拠の構造と要素が含まれること
     */
    public function test_equipment_patterns_index_renders_cleanly_with_accordions_and_csp(): void
    {
        $this->actingAs($this->operator);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => 'カラーコーン標準動態',
            'target_type' => 'committee',
            'description' => '前日倉庫出しから当日撤収までの標準ステップ',
        ]);

        $pattern->steps()->createMany([
            [
                'display_order' => 10,
                'day_phase' => 'pre_day',
                'action_type' => 'storage_out',
                'title' => '前日地下倉庫出し',
                'from_location_id' => $this->location->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'notes' => '台車を使用して番所前へ移動',
            ],
            [
                'display_order' => 20,
                'day_phase' => 'day1_evening',
                'action_type' => 'return_storage',
                'title' => '1日目夕方倉庫返還',
                'from_location_id' => null,
                'to_location_id' => $this->location->id,
                'operator_type' => 'committee',
                'witness_required' => true,
                'notes' => '数量点検のうえ施錠',
            ],
        ]);

        $response = $this->get(route('equipment-patterns.index'));
        $response->assertStatus(200);

        // ヘッダー & ナビゲーション検証
        $response->assertSee('搬出入パターン一覧');
        $response->assertSee('全 1 パターン');
        $response->assertSee('新規パターン作成');
        $response->assertSee(route('equipments.index'));
        $response->assertSee(route('logistics.index'));

        // セクションヘッダー & アコーディオン一括トグル
        $response->assertSee('登録済み搬出入パターン一覧 (ステップ構成)');
        $response->assertSee('すべて折りたたむ');
        $response->assertSee('data-action="toggle-accordions"', false);
        $response->assertSee('data-target="#pattern-accordions"', false);

        // パターンアコーディオンとテーブル
        $response->assertSee('カラーコーン標準動態');
        $response->assertSee('実行委員会管理（自前）');
        $response->assertDontSee('2 ステップ');
        $response->assertSee('data-action="open-pattern-detail"', false);
        $response->assertSee('data-action="open-step-create"', false);
        $response->assertSee('data-action="open-step-edit"', false);
        $response->assertSee('前日地下倉庫出し');
        $response->assertSee('1日目夕方倉庫返還');
        $response->assertSee('番所地下倉庫');
        $response->assertSee('必須');
        $response->assertDontSee('注意事項・申し送り');

        // モーダルコンポーネント & スクリプト
        $response->assertSee('id="pattern-create-modal"', false);
        $response->assertDontSee('id="pattern-edit-modal"', false);
        $response->assertSee('id="pattern-detail-modal"', false);
        $response->assertSee('id="pattern-step-create-modal"', false);
        $response->assertSee('id="pattern-step-edit-modal"', false);
        $response->assertSee('id="tpl-pattern-step-row"', false);
        $response->assertSee('equipment-patterns');

        // CSP準拠検証（Blade内にインライン style="..." や onclick="..." が出力されていないこと）
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content, 'CSP違反: インラインスタイル属性が含まれています。');
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content, 'CSP違反: インラインイベントハンドラ属性が含まれています。');
    }

    /**
     * 新規搬出入パターンの作成が正常に動作すること
     */
    public function test_can_create_equipment_pattern(): void
    {
        $this->actingAs($this->operator);

        $payload = [
            'name' => 'テント一式設営パターン',
            'target_type' => 'rental',
            'description' => '業者レンタルテントの標準動態',
            'steps' => [
                [
                    'display_order' => 10,
                    'day_phase' => 'pre_day',
                    'action_type' => 'vendor_receive',
                    'title' => '業者納品立会',
                    'from_location_id' => null,
                    'to_location_id' => $this->location->id,
                    'operator_type' => 'committee',
                    'witness_required' => 1,
                    'notes' => '破損確認',
                ],
                [
                    'display_order' => 20,
                    'day_phase' => 'day1_morning',
                    'action_type' => 'setup',
                    'title' => '朝設営・ウェイト固定',
                    'from_location_id' => $this->location->id,
                    'to_location_id' => null,
                    'operator_type' => 'committee',
                    'witness_required' => 0,
                    'notes' => '風速確認',
                ],
            ],
        ];

        $response = $this->post(route('equipment-patterns.store'), $payload);
        $response->assertRedirect(route('equipment-patterns.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('equipment_logistics_patterns', [
            'name' => 'テント一式設営パターン',
            'target_type' => 'rental',
        ]);

        $pattern = EquipmentLogisticsPattern::where('name', 'テント一式設営パターン')->first();
        $this->assertNotNull($pattern);
        $this->assertSame(2, $pattern->steps()->count());
        $this->assertSame('業者納品立会', $pattern->steps()->first()?->title);
    }

    /**
     * 既存搬出入パターンの更新が正常に動作すること
     */
    public function test_can_update_equipment_pattern(): void
    {
        $this->actingAs($this->admin);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => '更新前パターン名',
            'target_type' => 'committee',
            'description' => '変更前説明',
        ]);
        $pattern->steps()->create([
            'display_order' => 10,
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '旧ステップ',
            'operator_type' => 'committee',
        ]);

        $updatePayload = [
            'name' => '更新後パターン名（新）',
            'target_type' => 'personal',
            'description' => '変更後の説明文',
            'steps' => [
                [
                    'display_order' => 10,
                    'day_phase' => 'day2_evening',
                    'action_type' => 'return_storage',
                    'title' => '新ステップ撤収',
                    'from_location_id' => null,
                    'to_location_id' => $this->location->id,
                    'operator_type' => 'committee',
                    'witness_required' => 1,
                    'notes' => '完了確認',
                ],
            ],
        ];

        $response = $this->put(route('equipment-patterns.update', $pattern), $updatePayload);
        $response->assertRedirect(route('equipment-patterns.index'));
        $response->assertSessionHas('status');

        $pattern->refresh();
        $this->assertSame('更新後パターン名（新）', $pattern->name);
        $this->assertSame('personal', $pattern->target_type);
        $this->assertSame('変更後の説明文', $pattern->description);
        $this->assertSame(1, $pattern->steps()->count());
        $this->assertSame('新ステップ撤収', $pattern->steps()->first()?->title);
    }

    /**
     * 搬出入パターンの削除が正常に動作すること
     */
    public function test_can_delete_equipment_pattern(): void
    {
        $this->actingAs($this->operator);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => '削除対象パターン',
            'target_type' => 'committee',
        ]);
        $pattern->steps()->create([
            'display_order' => 10,
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '削除ステップ',
            'operator_type' => 'committee',
        ]);

        $response = $this->delete(route('equipment-patterns.destroy', $pattern));
        $response->assertRedirect(route('equipment-patterns.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('equipment_logistics_patterns', ['id' => $pattern->id]);
        $this->assertDatabaseMissing('equipment_logistics_pattern_steps', ['pattern_id' => $pattern->id]);
    }

    /**
     * 一般閲覧者（viewer）はパターンの作成・更新・削除が禁止（403）されること
     */
    public function test_viewer_cannot_modify_patterns(): void
    {
        $this->actingAs($this->viewer);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => '保護パターン',
            'target_type' => 'committee',
        ]);

        // 作成禁止
        $this->post(route('equipment-patterns.store'), [
            'name' => '不正作成',
            'target_type' => 'committee',
            'steps' => [['title' => 'step', 'day_phase' => 'pre_day', 'action_type' => 'storage_out']],
        ])->assertForbidden();

        // 更新禁止
        $this->put(route('equipment-patterns.update', $pattern), [
            'name' => '不正更新',
            'target_type' => 'committee',
            'steps' => [['title' => 'step', 'day_phase' => 'pre_day', 'action_type' => 'storage_out']],
        ])->assertForbidden();

        // 削除禁止
        $this->delete(route('equipment-patterns.destroy', $pattern))->assertForbidden();
    }

    /**
     * パターンにステップを新規追加できること
     */
    public function test_can_add_step_to_pattern(): void
    {
        $this->actingAs($this->operator);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => 'ステップ追加テストパターン',
            'target_type' => 'committee',
        ]);

        $payload = [
            'day_phase' => 'day1_morning',
            'action_type' => 'setup',
            'title' => '朝の配置作業',
            'from_location_id' => $this->location->id,
            'to_location_id' => null,
            'operator_type' => 'committee',
            'witness_required' => 1,
            'display_order' => 10,
        ];

        $response = $this->post(route('equipment-patterns.steps.store', $pattern), $payload);
        $response->assertRedirect(route('equipment-patterns.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('equipment_logistics_pattern_steps', [
            'pattern_id' => $pattern->id,
            'title' => '朝の配置作業',
            'day_phase' => 'day1_morning',
            'action_type' => 'setup',
            'witness_required' => true,
        ]);
    }

    /**
     * パターンのステップを更新できること
     */
    public function test_can_update_pattern_step(): void
    {
        $this->actingAs($this->operator);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => 'ステップ更新テストパターン',
            'target_type' => 'committee',
        ]);
        $step = $pattern->steps()->create([
            'display_order' => 10,
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '更新前ステップ',
            'operator_type' => 'committee',
        ]);

        $updatePayload = [
            'day_phase' => 'day2_evening',
            'action_type' => 'return_storage',
            'title' => '更新後ステップ（撤収）',
            'from_location_id' => null,
            'to_location_id' => $this->location->id,
            'operator_type' => 'event_company',
            'witness_required' => 0,
            'display_order' => 20,
        ];

        $response = $this->put(route('equipment-patterns.steps.update', [$pattern, $step]), $updatePayload);
        $response->assertRedirect(route('equipment-patterns.index'));
        $response->assertSessionHas('status');

        $step->refresh();
        $this->assertSame('更新後ステップ（撤収）', $step->title);
        $this->assertSame('day2_evening', $step->day_phase);
        $this->assertSame('return_storage', $step->action_type);
        $this->assertSame('event_company', $step->operator_type);
        $this->assertFalse($step->witness_required);
        $this->assertSame(20, $step->display_order);
    }

    /**
     * パターンのステップを削除できること
     */
    public function test_can_delete_pattern_step(): void
    {
        $this->actingAs($this->operator);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => 'ステップ削除テストパターン',
            'target_type' => 'committee',
        ]);
        $step = $pattern->steps()->create([
            'display_order' => 10,
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '削除対象ステップ',
            'operator_type' => 'committee',
        ]);

        $response = $this->delete(route('equipment-patterns.steps.destroy', [$pattern, $step]));
        $response->assertRedirect(route('equipment-patterns.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('equipment_logistics_pattern_steps', ['id' => $step->id]);
    }

    /**
     * 一般閲覧者（viewer）はステップの追加・更新・削除が禁止されること
     */
    public function test_viewer_cannot_modify_pattern_steps(): void
    {
        $this->actingAs($this->viewer);

        $pattern = EquipmentLogisticsPattern::create([
            'name' => '閲覧専用パターン',
            'target_type' => 'committee',
        ]);
        $step = $pattern->steps()->create([
            'display_order' => 10,
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '閲覧ステップ',
            'operator_type' => 'committee',
        ]);

        $stepPayload = [
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
            'title' => '不正追加ステップ',
            'operator_type' => 'committee',
        ];

        // 追加禁止
        $this->post(route('equipment-patterns.steps.store', $pattern), $stepPayload)->assertForbidden();

        // 更新禁止
        $this->put(route('equipment-patterns.steps.update', [$pattern, $step]), $stepPayload)->assertForbidden();

        // 削除禁止
        $this->delete(route('equipment-patterns.steps.destroy', [$pattern, $step]))->assertForbidden();
    }
}
