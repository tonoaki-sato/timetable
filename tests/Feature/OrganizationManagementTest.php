<?php

namespace Tests\Feature;

use App\Models\OrganizationUnit;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

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
    }

    /**
     * 未ログインユーザーはログイン画面にリダイレクトされること
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('organizations.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * 組織管理一覧画面が正常に表示され、共通ラッパー、新規組織追加ボタン、JSスクリプトが出力されること
     */
    public function test_organization_index_displays_empty_state_when_no_units_exist(): void
    {
        $response = $this->actingAs($this->admin)->get(route('organizations.index'));

        $response->assertStatus(200);
        $response->assertSee('組織管理');
        $response->assertSee('max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5', false);
        $response->assertSee('登録されている組織・係がありません');
        $response->assertSee('新規組織追加');
        $response->assertSee('id="btn-open-create-modal"', false);
        $response->assertSee('PDF印刷');
        $response->assertSee('保土ケ谷宿場まつり実行委員会 組織図');
        // Viteにより organizations.js がスクリプトタグとして出力されていること
        $response->assertSee('organizations', false);
        $response->assertSee('<script', false);
    }

    /**
     * 組織データが存在する場合、テーブルに名前・説明・担当が表示されること
     */
    public function test_organization_index_displays_units_with_staff_names_only(): void
    {
        $unit = OrganizationUnit::factory()->create([
            'name' => '設営・設備・企画',
            'short_description' => 'overcom（施工委託）',
            'description' => '会場テント・電気設備・看板の設置・管理・撤去',
            'display_order' => 10,
        ]);

        $staff1 = Staff::factory()->create(['name' => '岡本 睦良']);
        $staff2 = Staff::factory()->create(['name' => '松木 新太郎']);

        $unit->staff()->attach($staff1->id, ['role' => '主担当', 'display_order' => 10]);
        $unit->staff()->attach($staff2->id, ['role' => '現場施工管理', 'display_order' => 20]);

        $response = $this->actingAs($this->admin)->get(route('organizations.index'));

        $response->assertStatus(200);
        $response->assertSee('設営・設備・企画');
        $response->assertSee('overcom（施工委託）');
        // 担当列に名前のみが表示され、役割（主担当等）は表示されないこと
        $response->assertSee('岡本 睦良');
        $response->assertSee('松木 新太郎');
        $response->assertDontSee('主担当');
        $response->assertDontSee('現場施工管理');
    }

    /**
     * 新規組織ユニットを正常に作成できること（担当スタッフ含む）
     */
    public function test_can_create_organization_unit_with_staff(): void
    {
        $staff1 = Staff::factory()->create(['name' => '佐藤 俊二']);
        $staff2 = Staff::factory()->create(['name' => '原 瞳']);

        $postData = [
            'name' => '総務',
            'short_description' => '本部運営・後方支援・警備',
            'description' => '総合案内、会議運営、警備計画',
            'staff_ids' => [$staff1->id, $staff2->id],
        ];

        $response = $this->actingAs($this->admin)->post(route('organizations.store'), $postData);

        $response->assertRedirect(route('organizations.index'));
        $this->assertDatabaseHas('organization_units', [
            'name' => '総務',
            'short_description' => '本部運営・後方支援・警備',
        ]);

        $unit = OrganizationUnit::where('name', '総務')->first();
        $this->assertNotNull($unit);
        $this->assertCount(2, $unit->staff);
    }

    /**
     * 組織詳細データをJSONで取得できること
     */
    public function test_can_get_organization_details_as_json(): void
    {
        $parent = OrganizationUnit::factory()->create(['name' => '総務']);
        $unit = OrganizationUnit::factory()->create([
            'parent_id' => $parent->id,
            'name' => '問い合わせ対応',
            'short_description' => 'お客様対応窓口',
            'description' => '代表電話・総合受付対応',
        ]);
        $staff = Staff::factory()->create(['name' => '佐藤 俊二']);
        $unit->staff()->attach($staff->id, ['role' => '窓口担当', 'display_order' => 10]);

        $response = $this->actingAs($this->admin)->getJson(route('organizations.show', $unit));

        $response->assertStatus(200);
        $response->assertJsonPath('unit.name', '問い合わせ対応');
        $response->assertJsonPath('parent_name', '総務');
        $response->assertJsonPath('staff.0.name', '佐藤 俊二');
    }

    /**
     * 組織ユニットを正常に更新できること（スタッフのDnD順序保存含む）
     */
    public function test_can_update_organization_unit_and_staff_order(): void
    {
        $unit = OrganizationUnit::factory()->create(['name' => '初期名称']);
        $staff1 = Staff::factory()->create(['name' => 'スタッフA']);
        $staff2 = Staff::factory()->create(['name' => 'スタッフB']);
        $unit->staff()->attach($staff1->id, ['display_order' => 10]);
        $unit->staff()->attach($staff2->id, ['display_order' => 20]);

        // スタッフの並び順を B -> A に入れ替えて更新
        $updateData = [
            'name' => '更新後名称',
            'short_description' => '更新後説明',
            'description' => '詳細説明更新',
            'staff_ids' => [$staff2->id, $staff1->id],
        ];

        $response = $this->actingAs($this->admin)->put(route('organizations.update', $unit), $updateData);

        $response->assertRedirect(route('organizations.index'));
        $this->assertDatabaseHas('organization_units', [
            'id' => $unit->id,
            'name' => '更新後名称',
            'short_description' => '更新後説明',
        ]);

        $unit->refresh();
        $staffOrdered = $unit->staff()->get();
        $this->assertEquals($staff2->id, $staffOrdered->first()->id);
        $this->assertEquals($staff1->id, $staffOrdered->last()->id);
    }

    /**
     * 組織ユニットを削除でき、配下の子組織もカスケード削除されること
     */
    public function test_can_delete_organization_unit_and_cascade_children(): void
    {
        $parent = OrganizationUnit::factory()->create(['name' => '親組織']);
        $child = OrganizationUnit::factory()->create([
            'parent_id' => $parent->id,
            'name' => '子組織',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('organizations.destroy', $parent));

        $response->assertRedirect(route('organizations.index'));
        $this->assertDatabaseMissing('organization_units', ['id' => $parent->id]);
        $this->assertDatabaseMissing('organization_units', ['id' => $child->id]);
    }

    /**
     * ドラッグ＆ドロップによる表示順の一括更新APIが動作すること
     */
    public function test_can_reorder_organizations_via_api(): void
    {
        $unit1 = OrganizationUnit::factory()->create(['display_order' => 10]);
        $unit2 = OrganizationUnit::factory()->create(['display_order' => 20]);
        $unit3 = OrganizationUnit::factory()->create(['display_order' => 30]);

        $response = $this->actingAs($this->admin)->postJson(route('organizations.reorder'), [
            'ids' => [$unit3->id, $unit1->id, $unit2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertEquals(10, $unit3->fresh()->display_order);
        $this->assertEquals(20, $unit1->fresh()->display_order);
        $this->assertEquals(30, $unit2->fresh()->display_order);
    }

    /**
     * 組織図印刷プレビュー画面が「保土ケ谷宿場まつり実行委員会 組織図」のタイトルで正常に描画されること
     */
    public function test_organization_print_view_displays_correct_title_and_layout(): void
    {
        $root = OrganizationUnit::factory()->create(['name' => '総務']);
        $child = OrganizationUnit::factory()->create([
            'parent_id' => $root->id,
            'name' => '設営・設備',
            'short_description' => 'overcom',
        ]);

        $response = $this->actingAs($this->admin)->get(route('organizations.print'));

        $response->assertStatus(200);
        $response->assertSee('保土ケ谷宿場まつり実行委員会 組織図');
        $response->assertSee('総務');
        $response->assertSee('設営・設備');
        $response->assertSee('overcom');
    }

    /**
     * 組織図ビューで第4階層以下が正しく描画され、末端組織が横並びグループ化されること
     */
    public function test_organization_tree_renders_fourth_level_and_groups_leaf_units(): void
    {
        // 第1階層: 総務
        $level1 = OrganizationUnit::factory()->create(['name' => '総務', 'display_order' => 10]);
        // 第2階層: 調査
        $level2 = OrganizationUnit::factory()->create(['name' => '調査', 'parent_id' => $level1->id, 'display_order' => 10]);
        // 第3階層: 来場者数
        $level3 = OrganizationUnit::factory()->create(['name' => '来場者数', 'parent_id' => $level2->id, 'display_order' => 10]);
        // 第4階層: アンケート
        $level4 = OrganizationUnit::factory()->create(['name' => 'アンケート', 'parent_id' => $level3->id, 'display_order' => 10]);

        // 広報配下（非末端組織と末端組織の組み合わせ）
        $kouhou = OrganizationUnit::factory()->create(['name' => '広報', 'display_order' => 20]);
        $it = OrganizationUnit::factory()->create(['name' => 'ＩＴ部', 'parent_id' => $kouhou->id, 'display_order' => 10]);
        $hp = OrganizationUnit::factory()->create(['name' => 'ホームページ', 'parent_id' => $it->id, 'display_order' => 10]);
        $chirashi = OrganizationUnit::factory()->create(['name' => '新聞・チラシ', 'parent_id' => $kouhou->id, 'display_order' => 20]);
        $poster = OrganizationUnit::factory()->create(['name' => 'ポスター', 'parent_id' => $kouhou->id, 'display_order' => 30]);

        // 1. 組織図ビュー（index画面）の検証
        $response = $this->actingAs($this->admin)->get(route('organizations.index'));
        $response->assertStatus(200);

        // 第4階層「アンケート」が表示されていること
        $response->assertSee('総務');
        $response->assertSee('調査');
        $response->assertSee('来場者数');
        $response->assertSee('アンケート');

        // 広報および末端組織グループの検証
        $response->assertSee('広報');
        $response->assertSee('ＩＴ部');
        $response->assertSee('ホームページ');
        $response->assertSee('新聞・チラシ');
        $response->assertSee('ポスター');
        $response->assertSee('tree-leaf-group', false);

        // 2. 印刷画面でも第4階層「アンケート」が表示されていること
        $printResponse = $this->actingAs($this->admin)->get(route('organizations.print'));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('アンケート');
        $printResponse->assertSee('ホームページ');
        $printResponse->assertSee('新聞・チラシ');
    }

    /**
     * 一覧画面で親組織名が非表示になり、階層順に字下げ記号が表示されること
     */
    public function test_organization_index_displays_hierarchical_indent_and_hides_parent_name(): void
    {
        $parent = OrganizationUnit::factory()->create(['name' => '親組織部', 'display_order' => 10]);
        $child = OrganizationUnit::factory()->create(['name' => '子組織係', 'parent_id' => $parent->id, 'display_order' => 20]);
        $grandchild = OrganizationUnit::factory()->create(['name' => '孫組織班', 'parent_id' => $child->id, 'display_order' => 30]);

        $response = $this->actingAs($this->admin)->get(route('organizations.index'));

        $response->assertStatus(200);
        // 名前列の下の「親: 親組織名」が表示されないこと
        $response->assertDontSee('親: 親組織部');
        $response->assertDontSee('親: 子組織係');

        // 字下げ記号「└─」が表示されていること
        $response->assertSee('└─', false);
    }

    /**
     * モーダルの親組織選択肢が階層順に字下げ（└─）されて出力されること
     */
    public function test_organization_modal_displays_hierarchical_options_with_indent(): void
    {
        $parent = OrganizationUnit::factory()->create(['name' => '総務部', 'display_order' => 10]);
        $child = OrganizationUnit::factory()->create(['name' => '本陣係', 'parent_id' => $parent->id, 'display_order' => 20]);

        $response = $this->actingAs($this->admin)->get(route('organizations.index'));

        $response->assertStatus(200);
        // 親組織セレクトボックスに「　└─ 本陣係」のoptionが存在すること
        $response->assertSee('　└─ 本陣係');
    }
}
