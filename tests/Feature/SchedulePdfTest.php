<?php

namespace Tests\Feature;

use App\Actions\Schedule\DownloadSchedulesPdfAction;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\User;
use App\Services\PdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulePdfTest extends TestCase
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
    public function test_guest_is_redirected_to_login_when_accessing_schedules_pdf(): void
    {
        $response = $this->get(route('schedules.pdf'));
        $response->assertRedirect(route('login'));
    }

    /**
     * スケジュール一覧画面に「年間スケジュール一覧PDF」ボタンが表示されていること
     */
    public function test_schedules_index_displays_annual_schedule_pdf_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('schedules.index'));

        $response->assertStatus(200);
        $response->assertSee('年間スケジュール一覧PDF');
        $response->assertSee('id="btn-download-schedule-pdf"', false);
        $response->assertSee(route('schedules.pdf'), false);
    }

    /**
     * 認証済みユーザーが正常にPDFをダウンロードでき、ヘッダーおよびバイナリが返却されること
     */
    public function test_authenticated_user_can_download_schedules_pdf(): void
    {
        Schedule::factory()->create([
            'title' => '第1回幹事会',
            'start_date' => now()->addDays(5)->toDateString(),
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'location' => 'コミュニティルーム',
            'category' => 'committee',
        ]);

        $response = $this->actingAs($this->admin)->get(route('schedules.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * スケジュール一覧PDFのHTMLビューが仕様通りの5列およびデータをレンダリングすること
     */
    public function test_schedules_pdf_view_renders_correctly_with_specified_five_columns_and_data(): void
    {
        $staff = Staff::factory()->create(['name' => '寺井 智之']);
        $schedule = Schedule::factory()->create([
            'title' => '第1回幹事会（年間日程案策定）',
            'start_date' => '2027-01-10',
            'end_date' => null,
            'start_time' => '18:00:00',
            'end_time' => '20:00:00',
            'location' => 'コミュニティルーム会議室',
            'staff_id' => $staff->id,
            'category' => 'committee',
            'description' => "年間日程案の策定\n前年度運営課題の整理",
        ]);

        $action = app(DownloadSchedulesPdfAction::class);
        $viewData = $action->buildViewData();
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $html = view('pdf.schedules', $viewData)->render();

        // 1. タイトルと指定5列ヘッダーが出力されていること
        $this->assertStringContainsString('保土ケ谷宿場まつり実行委員会 年間スケジュール一覧', $html);
        $this->assertStringContainsString('<th class="col-datetime">日時・期間</th>', $html);
        $this->assertStringContainsString('<th class="col-name">名前</th>', $html);
        $this->assertStringContainsString('<th class="col-location">場所</th>', $html);
        $this->assertStringContainsString('<th class="col-staff">担当</th>', $html);
        $this->assertStringContainsString('<th class="col-description">詳細</th>', $html);

        // 2. スケジュールデータが出力されていること
        $this->assertStringContainsString('2027/01/10', $html);
        $this->assertStringContainsString('18:00 〜 20:00', $html);
        $this->assertStringContainsString('第1回幹事会（年間日程案策定）', $html);
        $this->assertStringContainsString('コミュニティルーム会議室', $html);
        $this->assertStringContainsString('寺井 智之', $html);
        $this->assertStringContainsString("年間日程案の策定<br />\n前年度運営課題の整理", $html);

        // 3. A4縦のページ設定および固定ヘッダーが含まれていること
        $this->assertStringContainsString('size: A4 portrait;', $html);
        $this->assertStringContainsString('margin: 18mm 12mm 12mm 12mm;', $html);
        $this->assertStringContainsString('class="page-header"', $html);
    }

    /**
     * 表示設定（タブ・区分トグル）がPDF出力条件に反映されること
     */
    public function test_schedules_pdf_filters_by_tab_and_category(): void
    {
        // 過去の会議
        $pastMeeting = Schedule::factory()->create([
            'title' => '過去の反省会',
            'start_date' => now()->subDays(10)->toDateString(),
            'category' => 'committee',
        ]);

        // 今後の許認可予定
        $futurePermit = Schedule::factory()->create([
            'title' => '道路使用許可申請本提出',
            'start_date' => now()->addDays(10)->toDateString(),
            'category' => 'permit',
        ]);

        // 今後の説明会予定
        $futureBriefing = Schedule::factory()->create([
            'title' => '出店者説明会',
            'start_date' => now()->addDays(15)->toDateString(),
            'category' => 'briefing',
        ]);

        $action = app(DownloadSchedulesPdfAction::class);

        // 1. デフォルト (upcoming): 今後の予定のみ含まれること
        $defaultData = $action->buildViewData(tab: 'upcoming');
        $defaultTitles = $defaultData['schedules']->pluck('title')->toArray();
        $this->assertNotContains('過去の反省会', $defaultTitles);
        $this->assertContains('道路使用許可申請本提出', $defaultTitles);
        $this->assertContains('出店者説明会', $defaultTitles);

        // 2. 過去タブ (past): 過去の予定のみ含まれること
        $pastData = $action->buildViewData(tab: 'past');
        $pastTitles = $pastData['schedules']->pluck('title')->toArray();
        $this->assertContains('過去の反省会', $pastTitles);
        $this->assertNotContains('道路使用許可申請本提出', $pastTitles);
        $this->assertNotContains('出店者説明会', $pastTitles);

        // 3. 区分絞り込み (category=permit): 指定区分のみ含まれること
        $permitData = $action->buildViewData(category: 'permit', tab: 'upcoming');
        $permitTitles = $permitData['schedules']->pluck('title')->toArray();
        $this->assertContains('道路使用許可申請本提出', $permitTitles);
        $this->assertNotContains('出店者説明会', $permitTitles);
        $this->assertStringContainsString('申請・協議', $permitData['subtitle']);

        // 4. 複数区分絞り込み (category=permit,briefing)
        $multiData = $action->buildViewData(category: 'permit,briefing', tab: 'upcoming');
        $multiTitles = $multiData['schedules']->pluck('title')->toArray();
        $this->assertContains('道路使用許可申請本提出', $multiTitles);
        $this->assertContains('出店者説明会', $multiTitles);
    }

    /**
     * DomPDFレンダリング後に@pageの余白が0にリセットされず維持されていること
     */
    public function test_dompdf_retains_page_margins_for_portrait(): void
    {
        $action = app(DownloadSchedulesPdfAction::class);
        $viewData = $action->buildViewData();
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $html = view('pdf.schedules', $viewData)->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $pageStyles = $dompdf->getCss()->get_page_styles();
        $this->assertGreaterThan(50.0, (float) $pageStyles['base']->margin_top);
        $this->assertGreaterThan(30.0, (float) $pageStyles['base']->margin_left);
    }
}
