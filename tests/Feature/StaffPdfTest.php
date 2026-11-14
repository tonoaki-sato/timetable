<?php

namespace Tests\Feature;

use App\Actions\Staff\DownloadStaffListPdfAction;
use App\Models\Staff;
use App\Models\User;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * 未ログインユーザーはログイン画面にリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_when_accessing_staff_pdf(): void
    {
        $response = $this->get(route('staff.pdf'));
        $response->assertRedirect(route('login'));
    }

    /**
     * スタッフ一覧画面に「スタッフ一覧PDF」ボタンが表示されていること
     */
    public function test_staff_index_displays_staff_list_pdf_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('staff.index'));

        $response->assertStatus(200);
        $response->assertSee('スタッフ一覧PDF');
        $response->assertSee('id="btn-download-staff-pdf"', false);
        $response->assertSee(route('staff.pdf'), false);
    }

    /**
     * 認証済みユーザーが正常にPDFをダウンロードでき、ヘッダーおよびバイナリが返却されること
     */
    public function test_authenticated_user_can_download_staff_pdf(): void
    {
        Staff::factory()->create([
            'name' => '保土ケ谷 太郎',
            'kana' => 'ホドガヤ タロウ',
            'affiliation' => '保土ケ谷町内会',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('staff.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * スタッフ一覧PDFのHTMLビューが仕様通りのA4縦・2段組およびデータをレンダリングすること
     */
    public function test_staff_pdf_view_renders_correctly_with_specified_two_column_layout_and_data(): void
    {
        Staff::factory()->create([
            'name' => '相田 健二',
            'kana' => 'アイダ ケンジ',
            'affiliation' => '保土ケ谷町内会',
            'is_active' => true,
        ]);
        Staff::factory()->create([
            'name' => '渡辺 健太',
            'kana' => 'ワタナベ ケンタ',
            'affiliation' => '神輿保存会',
            'is_active' => true,
        ]);

        $action = app(DownloadStaffListPdfAction::class);
        $viewData = $action->buildViewData();
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $html = view('pdf.staff_list', $viewData)->render();

        // 1. タイトル、見出し（氏名、フリガナ、所属団体）が出力されていること
        $this->assertStringContainsString('スタッフ一覧', $html);
        $this->assertStringContainsString('<th class="col-name">氏名</th>', $html);
        $this->assertStringContainsString('<th class="col-kana">フリガナ</th>', $html);
        $this->assertStringContainsString('<th class="col-affiliation">所属団体</th>', $html);
        $this->assertStringContainsString('<th class="col-divider"></th>', $html);

        // 2. スタッフデータが出力されていること
        $this->assertStringContainsString('相田 健二', $html);
        $this->assertStringContainsString('アイダ ケンジ', $html);
        $this->assertStringContainsString('保土ケ谷町内会', $html);
        $this->assertStringContainsString('渡辺 健太', $html);
        $this->assertStringContainsString('ワタナベ ケンタ', $html);
        $this->assertStringContainsString('神輿保存会', $html);

        // 3. A4縦のページ設定および固定ヘッダーが含まれていること
        $this->assertStringContainsString('size: A4 portrait;', $html);
        $this->assertStringContainsString('margin: 20mm 10mm 12mm 10mm;', $html);
        $this->assertStringContainsString('class="page-header"', $html);
        $this->assertStringContainsString('border-bottom: 1.5pt solid #0f172a;', $html);
    }

    /**
     * フリーワード検索および稼働状態の絞り込みがPDFデータに反映されること
     */
    public function test_staff_pdf_filters_by_search_and_status(): void
    {
        // 稼働中スタッフA
        Staff::factory()->create([
            'name' => '神輿 太郎',
            'kana' => 'ミコシ タロウ',
            'affiliation' => '神輿保存会',
            'is_active' => true,
        ]);

        // 稼働中スタッフB
        Staff::factory()->create([
            'name' => '交通 次郎',
            'kana' => 'コウツウ ジロウ',
            'affiliation' => '交通安全協会',
            'is_active' => true,
        ]);

        // 停止中スタッフC
        Staff::factory()->create([
            'name' => '神輿 三郎',
            'kana' => 'ミコシ サブロウ',
            'affiliation' => '神輿保存会',
            'is_active' => false,
        ]);

        $action = app(DownloadStaffListPdfAction::class);

        // 1. 絞り込みなし: 3名全員が含まれること
        $allData = $action->buildViewData();
        $this->assertSame(3, $allData['totalCount']);
        $this->assertSame('全スタッフ', $allData['filterText']);

        // 2. 稼働状態絞り込み (status=1): 稼働中の2名のみ含まれること
        $activeData = $action->buildViewData(status: '1');
        $this->assertSame(2, $activeData['totalCount']);
        $this->assertStringContainsString('状態: 稼働可能', $activeData['filterText']);
        $page = $activeData['pages']->first();
        $staffNames = $page['left']->pluck('name')->merge($page['right']->pluck('name'))->toArray();
        $this->assertContains('神輿 太郎', $staffNames);
        $this->assertContains('交通 次郎', $staffNames);
        $this->assertNotContains('神輿 三郎', $staffNames);

        // 3. 検索絞り込み (search=神輿): 神輿関連の2名のみ含まれること
        $searchData = $action->buildViewData(search: '神輿');
        $this->assertSame(2, $searchData['totalCount']);
        $this->assertStringContainsString('検索: 「神輿」', $searchData['filterText']);
        $page = $searchData['pages']->first();
        $staffNames = $page['left']->pluck('name')->merge($page['right']->pluck('name'))->toArray();
        $this->assertContains('神輿 太郎', $staffNames);
        $this->assertContains('神輿 三郎', $staffNames);
        $this->assertNotContains('交通 次郎', $staffNames);

        // 4. 複合絞り込み (search=神輿, status=1): 稼働中の「神輿 太郎」のみ含まれること
        $combinedData = $action->buildViewData(search: '神輿', status: '1');
        $this->assertSame(1, $combinedData['totalCount']);
        $this->assertStringContainsString('検索: 「神輿」', $combinedData['filterText']);
        $this->assertStringContainsString('状態: 稼働可能', $combinedData['filterText']);
        $page = $combinedData['pages']->first();
        $staffNames = $page['left']->pluck('name')->merge($page['right']->pluck('name'))->toArray();
        $this->assertContains('神輿 太郎', $staffNames);
        $this->assertNotContains('神輿 三郎', $staffNames);
        $this->assertNotContains('交通 次郎', $staffNames);
    }

    /**
     * 該当データが0件の場合でも空表示メッセージが出力されること
     */
    public function test_staff_pdf_renders_empty_state_when_no_staff_match(): void
    {
        $action = app(DownloadStaffListPdfAction::class);
        $viewData = $action->buildViewData(search: '存在しないスタッフ名');
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $this->assertSame(0, $viewData['totalCount']);

        $html = view('pdf.staff_list', $viewData)->render();
        $this->assertStringContainsString('該当するスタッフは見つかりませんでした。', $html);
    }

    /**
     * DomPDFレンダリング後に@pageの余白が0にリセットされず維持されていること（A4縦）
     */
    public function test_dompdf_retains_page_margins_for_staff_list(): void
    {
        Staff::factory()->create([
            'name' => '保土ケ谷 太郎',
            'kana' => 'ホドガヤ タロウ',
            'affiliation' => '保土ケ谷町内会',
        ]);

        $action = app(DownloadStaffListPdfAction::class);
        $viewData = $action->buildViewData();
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $html = view('pdf.staff_list', $viewData)->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $pageStyles = $dompdf->getCss()->get_page_styles();
        $this->assertGreaterThan(50.0, (float) $pageStyles['base']->margin_top);
        $this->assertGreaterThan(25.0, (float) $pageStyles['base']->margin_left);
    }
}
