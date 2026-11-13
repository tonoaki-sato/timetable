<?php

namespace Tests\Feature;

use App\Actions\Organization\DownloadOrganizationStaffListPdfAction;
use App\Models\OrganizationUnit;
use App\Models\Staff;
use App\Models\User;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationStaffListPdfTest extends TestCase
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
    public function test_guest_is_redirected_to_login_when_accessing_staff_list_pdf(): void
    {
        $response = $this->get(route('organizations.staff-list-pdf'));
        $response->assertRedirect(route('login'));
    }

    /**
     * 組織管理一覧画面に「所属スタッフ一覧PDF」ボタンが表示されていること
     */
    public function test_organization_index_displays_staff_list_pdf_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('organizations.index'));

        $response->assertStatus(200);
        $response->assertSee('所属スタッフ一覧PDF');
        $response->assertSee(route('organizations.staff-list-pdf'), false);
    }

    /**
     * 認証済み管理者が正常にPDFをダウンロードでき、ヘッダーおよびバイナリが返却されること
     */
    public function test_authenticated_user_can_download_staff_list_pdf(): void
    {
        $unit = OrganizationUnit::factory()->create(['name' => '総務部']);
        $staff = Staff::factory()->create(['name' => '山田 太郎']);
        $unit->staff()->attach($staff->id, ['role' => '部長', 'display_order' => 10]);

        $response = $this->actingAs($this->admin)->get(route('organizations.staff-list-pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * 組織別所属スタッフ一覧のHTMLビューが仕様通りにレンダリングされること
     */
    public function test_staff_list_pdf_view_renders_correctly_with_hierarchy_indent_and_no_elbows(): void
    {
        // 4階層の組織構造を作成
        $root = OrganizationUnit::factory()->create([
            'name' => '実行委員長',
            'short_description' => "実行委員会の統括\n年間計画の策定",
            'display_order' => 10,
        ]);
        $l2 = OrganizationUnit::factory()->create([
            'name' => '渉外',
            'parent_id' => $root->id,
            'short_description' => '対外折衝窓口',
            'display_order' => 20,
        ]);
        $l3 = OrganizationUnit::factory()->create([
            'name' => '駐車場',
            'parent_id' => $l2->id,
            'short_description' => "駐車場管理\r\n車両誘導",
            'display_order' => 30,
        ]);
        $l4 = OrganizationUnit::factory()->create([
            'name' => '長島駐車場',
            'parent_id' => $l3->id,
            'short_description' => '長島第1・第2',
            'display_order' => 40,
        ]);

        $staff1 = Staff::factory()->create(['name' => '寺井 智之']);
        $staff2 = Staff::factory()->create(['name' => '萩原 繁夫']);
        $root->staff()->attach($staff1->id, ['role' => '委員長', 'display_order' => 10]);
        $l4->staff()->attach($staff2->id, ['role' => '現地責任者', 'display_order' => 10]);

        $action = app(DownloadOrganizationStaffListPdfAction::class);
        $viewData = $action->buildViewData();
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $html = view('pdf.organization_staff_list', $viewData)->render();

        // 1. タイトルとヘッダー列名が出力されていること
        $this->assertStringContainsString('保土ケ谷宿場まつり実行委員会 組織別所属スタッフ一覧', $html);
        $this->assertStringContainsString('<th class="col-name">名前</th>', $html);
        $this->assertStringContainsString('<th class="col-staff">所属スタッフ</th>', $html);
        $this->assertStringContainsString('<th class="col-description">詳細</th>', $html);

        // 2. 「全〇〇組織」が出力されていないこと
        $this->assertStringNotContainsString('全4組織', $html);
        $this->assertStringNotContainsString('全組織', $html);

        // 3. 階層バッジ（[第1階層] 等）やエルボー記号（└─）が出力されていないこと
        $this->assertStringNotContainsString('[第1階層]', $html);
        $this->assertStringNotContainsString('[第2階層]', $html);
        $this->assertStringNotContainsString('└─', $html);

        // 4. 全階層（第1階層〜第4階層）の名前が出力され、字下げインデントクラスが付与されていること
        $this->assertStringContainsString('indent-level-0', $html);
        $this->assertStringContainsString('実行委員長', $html);
        $this->assertStringContainsString('indent-level-1', $html);
        $this->assertStringContainsString('渉外', $html);
        $this->assertStringContainsString('indent-level-2', $html);
        $this->assertStringContainsString('駐車場', $html);
        $this->assertStringContainsString('indent-level-3', $html);
        $this->assertStringContainsString('長島駐車場', $html);

        // 5. 所属スタッフ名が出力されていること
        $this->assertStringContainsString('寺井 智之', $html);
        $this->assertStringContainsString('萩原 繁夫', $html);

        // 6. 詳細テキストの改行が除去されて空白で連結されていること
        $this->assertStringContainsString('実行委員会の統括 年間計画の策定', $html);
        $this->assertStringContainsString('駐車場管理 車両誘導', $html);
        $this->assertStringNotContainsString("統括\n年間計画", $html);

        // 7. ヘッダー行高さ（1行分: 16pt）およびデータ行高さ（2行分: 26pt）のスタイル定義が存在すること
        $this->assertStringContainsString('height: 16pt;', $html);
        $this->assertStringContainsString('height: 26pt;', $html);

        // 8. 他のPDFと同様の余白（18mm 12mm 12mm 12mm）および固定ヘッダー（page-header）が存在すること
        $this->assertStringContainsString('margin: 18mm 12mm 12mm 12mm;', $html);
        $this->assertStringContainsString('class="page-header"', $html);

        // 9. 動的列幅が各thクラスに適用され、No.と名前がテキスト幅に寄せられていること
        $this->assertStringContainsString('.staff-list-table th.col-no', $html);
        $this->assertStringContainsString('.staff-list-table th.col-name', $html);
        $this->assertStringContainsString('.staff-list-table th.col-staff', $html);
        $this->assertStringContainsString('.staff-list-table th.col-description', $html);
        $this->assertSame('4.5%', $viewData['columnWidths']['no']);
        $this->assertLessThanOrEqual(20.0, (float) rtrim($viewData['columnWidths']['name'], '%'));
        $this->assertStringContainsString('width: '.$viewData['columnWidths']['no'].';', $html);
        $this->assertStringContainsString('width: '.$viewData['columnWidths']['name'].';', $html);
        $this->assertStringContainsString('width: '.$viewData['columnWidths']['staff'].';', $html);
        $this->assertStringContainsString('width: '.$viewData['columnWidths']['description'].';', $html);

        // 10. DomPDFレンダリング後に@pageの余白（18mm/12mm）が0にリセットされず維持されていること
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $pageStyles = $dompdf->getCss()->get_page_styles();
        $this->assertGreaterThan(50.0, (float) $pageStyles['base']->margin_top);
        $this->assertGreaterThan(30.0, (float) $pageStyles['base']->margin_left);
    }
}
