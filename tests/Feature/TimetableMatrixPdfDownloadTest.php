<?php

namespace Tests\Feature;

use App\Actions\Timetable\DownloadTimetableMatrixPdfAction;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Services\PdfService;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableMatrixPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    /**
     * 未認証ユーザーは進行表PDFダウンロード時にログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_user_cannot_download_matrix_pdf(): void
    {
        $timetable = Timetable::factory()->create();

        $this->get(route('timetables.print-matrix', $timetable))
            ->assertRedirect(route('login'));
    }

    /**
     * オペレーターが選択中運行日の進行表PDFをダウンロードできること
     */
    public function test_operator_can_download_matrix_pdf_for_selected_timetable(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '19:30:00',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'display_order' => 10,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => 'ゴミ箱配置',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 4,
        ]);

        $response = $this->get(route('timetables.print-matrix', $timetable));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('timetable_matrix_', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * 画面で選択された表示班（lane_ids）のみがPDF内に出力され、除外された班は出力されないこと
     */
    public function test_matrix_pdf_respects_lane_ids_filter(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
        ]);

        $laneA = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'display_order' => 10,
        ]);

        $laneB = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'display_order' => 20,
        ]);

        $laneC = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '除外班テスト',
            'display_order' => 30,
        ]);

        // laneA, laneB のみを指定してプレビュー確認
        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'lane_ids' => "{$laneA->id},{$laneB->id}",
            'preview' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('衛生');
        $response->assertSee('カラーコーン');
        $response->assertDontSee('除外班テスト');
    }

    /**
     * 通行止めおよび開催中ステータスが正しくレンダリングされること
     */
    public function test_matrix_pdf_renders_road_and_festival_status_correctly(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '12:00:00',
            'road_periods' => [
                [
                    'label' => '通行止め',
                    'status' => 'closed',
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                ],
            ],
            'festival_periods' => [
                [
                    'label' => '開催中',
                    'status' => 'active',
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                ],
            ],
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('通行止め');
        $response->assertSee('開催中');
        $response->assertSee('cell-road-closed', false);
        $response->assertSee('cell-festival-active', false);
    }

    /**
     * 複数スロットにまたがるタスクが適切な rowspan で描画されること
     */
    public function test_matrix_pdf_renders_tasks_with_proper_rowspan(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '10:00:00',
        ]);

        $leadStaff = Staff::factory()->create(['name' => '有江 喜一郎']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'lead_staff_id' => $leadStaff->id,
            'display_order' => 10,
        ]);

        // 7:30〜9:00 のタスク (3スロット分: rowspan="3")
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '事前準備',
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'required_people_count' => 4,
            'is_at_least' => true,
            'content' => "① 名札設置\n② 袋設置",
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('有江さん');
        $response->assertSee('事前準備');
        $response->assertSee('【4名以上】');
        $response->assertSee('① 名札設置');
        $response->assertSee('② 袋設置');
        $response->assertSee('rowspan="3"', false);
    }

    /**
     * Web画面準拠の新PDFデザイン要素（充足バッジ、スタッフ名バッジ、場所ピン、ヘッダー班長名）が正しく描画されること
     */
    public function test_matrix_pdf_renders_web_design_elements_correctly(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $leadStaff = Staff::factory()->create(['name' => 'Jouko Karvonen']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'icon' => '▲',
            'lead_staff_id' => $leadStaff->id,
            'display_order' => 10,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '全体ミーティング',
            'location' => '本陣',
            'start_time' => '07:00:00',
            'end_time' => '07:30:00',
            'required_people_count' => 1,
            'is_at_least' => true,
        ]);

        $task->assignments()->create([
            'staff_id' => $leadStaff->id,
            'status' => 'confirmed',
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);

        // ヘッダー要素
        $response->assertSee('カラーコーン');
        $response->assertSee('Joukoさん');
        $response->assertSee('▲');

        // タスク情報
        $response->assertSee('全体ミーティング');
        $response->assertSee('【1名以上】');
        $response->assertSee('本陣');

        // 充足バッジ
        $response->assertSee('status-badge-satisfied', false);
        $response->assertSee('✔ 充足 (1)');

        // アサインされたスタッフ名
        $response->assertSee('staff-pill', false);
        $response->assertSee('Jouko Karvonen');

        // デザイン用クラス
        $response->assertSee('matrix-wrapper', false);
        $response->assertSee('th-control', false);
        $response->assertSee('th-lane', false);
    }

    /**
     * 進行表PDFに適切な余白（@page margin: 10mm 12mm等）が設定され、
     * 改ページ時にも日付データが欠落しないよう全スロット行に日付セルが出力されていること
     */
    public function test_matrix_pdf_has_proper_margins_and_renders_date_cell_for_every_slot(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '保土ケ谷宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00', // 5スロット (07:00, 07:30, 08:00, 08:30, 09:00)
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);

        // 余白・改ページCSS設定
        $response->assertSee('margin: 10mm 12mm;', false);
        $response->assertSee('margin-bottom: 12px;', false);
        $response->assertSee('page-break-inside: avoid;', false);
        $response->assertSee('display: table-header-group;', false);

        // 全称セレクタ * による @page マージンリセットの上書きがないこと
        $response->assertDontSee('*, body, table', false);
        $response->assertSee('body, table, th, td', false);

        // 日付セルが全スロット行に出力され、全体rowspanによる欠落原因が存在しないこと
        $response->assertDontSee('rowspan="5" class="cell-date"', false);
        $content = $response->getContent();
        // 5スロット分、すべての日付セルに「1日目」が出力されていること
        $this->assertSame(5, substr_count($content, '<td class="cell-date">'));
    }

    /**
     * 進行表PDFの管理系列列（日付・時間・道路・まつり）の幅がギリギリまで狭小化され、
     * white-space: nowrap が適用されていること（DomPDFのtable-layout: fixedで均等割付されないパーセント指定）
     */
    public function test_matrix_pdf_narrows_control_column_widths(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);

        // 狭小化された各列幅の検証（パーセント指定）
        $response->assertSee('.col-date {', false);
        $response->assertSee('width: 3.5%;', false);

        $response->assertSee('.col-time {', false);
        $response->assertSee('width: 4.2%;', false);

        $response->assertSee('.col-road {', false);
        $response->assertSee('width: 4.8%;', false);

        $response->assertSee('.col-festival {', false);
        $response->assertSee('width: 4.2%;', false);

        // 折り返し防止の検証
        $response->assertSee('white-space: nowrap;', false);
    }

    /**
     * 進行表画面（/timetable）のヘッダーが共通 x-page-header であり、タイトルが「{タイムテーブル名} 進行表」、
     * バッジが運行日のみ、説明文が「進行表」、パンくずが「運行情報一覧」となっていること
     */
    public function test_matrix_page_has_unified_page_header_and_breadcrumb(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '19:30:00',
            'status' => 'active',
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));

        $response->assertStatus(200);

        // タイトル
        $response->assertSee('宿場まつり 1日目 進行表');

        // パンくずリスト
        $response->assertSee('運行情報一覧');
        $response->assertDontSee('運行情報管理');

        // バッジ: 運行日のみ
        $response->assertSee('2026年10月11日');

        // 説明文: 「当日タイムテーブル」を含まず「進行表」と表記
        $response->assertSee('「宿場まつり 1日目」の進行表および担当班タスク配置です。');
        $response->assertDontSee('当日タイムテーブル（進行表）のリアルタイム進行状況');

        // ボタン
        $response->assertSee('進行表PDF');
        $response->assertSee('btn-download-matrix-pdf', false);

        // 既存の黒背景ヘッダー（旧ヘッダー枠）が存在しないこと
        $response->assertDontSee('border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5', false);
    }

    /**
     * 実際のDomPDFレンダリングにおいて、ルートフレームのページ余白（10mm 12mm）が保持され、
     * 管理系列列が全列均等割付けにならず狭小化されること
     */
    public function test_matrix_pdf_dompdf_renders_with_page_margins_and_narrow_control_columns(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $action = app(DownloadTimetableMatrixPdfAction::class);
        $viewData = $action->buildViewData($timetable);
        $viewData['japaneseFontPath'] = app(PdfService::class)->getJapaneseFontPath();

        $html = view('pdf.timetable_matrix', $viewData)->render();
        $html = preg_replace('/[\x{10000}-\x{10FFFF}\x{FE00}-\x{FE0F}]/u', '', $html) ?? $html;

        $dompdf = new Dompdf([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'JapaneseFont',
        ]);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->loadHtml($html);
        $dompdf->render();

        // ルート要素のマージンが 0 0 0 0 に上書きされず、10mm 12mm (約28.3pt 34.0pt) が保持されていること
        $rootMargin = $dompdf->getTree()->get_root()->get_style()->margin;
        $this->assertNotSame('0 0 0 0', $rootMargin);
        $this->assertStringContainsString('34.01', $rootMargin);
        $this->assertStringContainsString('28.34', $rootMargin);
    }
}
