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
     * 複数スロットにまたがるタスクが開始スロットと継続スロット（cell-task-continue）で正しく描画されること
     */
    public function test_matrix_pdf_renders_tasks_with_proper_flow_and_continuation(): void
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

        // 7:30〜9:00 のタスク (3スロット分: 07:30開始, 08:00継続, 08:30継続)
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
        $response->assertSee('cell-task-continue', false);
        $response->assertSee('↓ 事前準備');
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
     * 進行表PDFに適切な余白（@page margin: 18mm 12mm 10mm 12mm）が設定され、
     * 固定ヘッダー（position: fixed）が全ページ表示用として定義され、日付列が削除されていること
     */
    public function test_matrix_pdf_has_proper_margins_fixed_header_and_omits_date_column(): void
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

        // 余白・改ページ・固定ヘッダーCSS設定
        $response->assertSee('margin: 18mm 12mm 10mm 12mm;', false);
        $response->assertSee('position: fixed;', false);
        $response->assertSee('top: -13mm;', false);
        $response->assertSee('page-break-inside: avoid;', false);
        $response->assertSee('display: table-header-group;', false);

        // 日付列が削除されていること
        $response->assertDontSee('.col-date', false);
        $response->assertDontSee('.cell-date', false);
        $response->assertDontSee('<th class="col-date th-control">日付</th>', false);
        $response->assertDontSee('<td class="cell-date">', false);
    }

    /**
     * 進行表PDFの管理系列列（時間・道路・まつり）の幅がギリギリまで狭小化され、
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

        // 日付列は存在しない
        $response->assertDontSee('.col-date', false);

        // 狭小化された各列幅の検証（パーセント指定: 5.0%, 5.5%, 5.0%）
        $response->assertSee('.col-time {', false);
        $response->assertSee('width: 5.0%;', false);

        $response->assertSee('.col-road {', false);
        $response->assertSee('width: 5.5%;', false);

        $response->assertSee('.col-festival {', false);
        $response->assertSee('width: 5.0%;', false);

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
     * 実際のDomPDFレンダリングにおいて、ルートフレームのページ余白（18mm 12mm 10mm 12mm）が保持され、
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

        // ルート要素のマージンが 0 0 0 0 に上書きされず、18mm 12mm 10mm 12mm (約51.0pt 34.0pt 28.3pt 34.0pt) が保持されていること
        $rootMargin = $dompdf->getTree()->get_root()->get_style()->margin;
        $this->assertNotSame('0 0 0 0', $rootMargin);
        $this->assertStringContainsString('34.01', $rootMargin);
        $this->assertStringContainsString('51.02', $rootMargin);
    }

    /**
     * 複数ページに渡る進行表PDF出力において、全ページに同一ヘッダーが表示され、
     * 2ページ目の最右端列（キッズ村等）の枠線が欠落せず描画されること
     */
    public function test_matrix_pdf_renders_fixed_header_on_all_pages_and_prevents_rightmost_border_disappearance(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '第35回保土ケ谷宿場まつり（1日目）',
            'target_date' => '2027-10-10',
            'start_time' => '07:00:00',
            'end_time' => '20:00:00',
        ]);

        $lanes = [];
        $names = ['カラーコーン', '給水設備', '衛生', '巡回', 'キッズ村'];
        foreach ($names as $idx => $name) {
            $lanes[] = Lane::factory()->create([
                'timetable_id' => $timetable->id,
                'name' => $name,
                'display_order' => ($idx + 1) * 10,
            ]);
        }

        // 11:00〜17:00（複数ページをまたぐ長時間タスク）
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lanes[1]->id, // 給水設備
            'title' => '定期巡回',
            'start_time' => '11:00:00',
            'end_time' => '17:00:00',
            'required_people_count' => 2,
            'is_at_least' => true,
        ]);

        $action = app(DownloadTimetableMatrixPdfAction::class);
        $laneIds = collect($lanes)->pluck('id')->all();
        $viewData = $action->buildViewData($timetable, $laneIds);
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

        $canvas = $dompdf->getCanvas();
        $pageCount = $canvas->get_page_count();
        $this->assertGreaterThanOrEqual(2, $pageCount);

        // CPDFオブジェクトを解析し、全ページにタイトルヘッダーが出現し、2ページ目の最右端列の枠線が存在することを検証
        $cpdf = $canvas->get_cpdf();
        $needle = mb_convert_encoding('進行表', 'UTF-16BE', 'UTF-8');

        $pageIndex = 1;
        foreach ($cpdf->objects as $obj) {
            if (isset($obj['t']) && $obj['t'] === 'contents') {
                $c = $obj['c'];
                // 各ページにヘッダー（進行表）が存在すること
                $this->assertStringContainsString($needle, $c, "Page {$pageIndex} must contain the header title");

                // 各ページの行（時間スロット）数と最右端列（キッズ村）の垂直線が欠落せず描画されていること
                preg_match_all('/\((\d{2}:\d{2})\)/', $c, $timeMatches);
                preg_match_all('/([0-9\.]+)\s+([0-9\.]+)\s+m\s+([0-9\.]+)\s+([0-9\.]+)\s+l\s+S/', $c, $matches, PREG_SET_ORDER);
                $rightmostLines = 0;
                foreach ($matches as $m) {
                    if (abs(floatval($m[1]) - floatval($m[3])) < 0.1 && floatval($m[1]) > 800) {
                        $rightmostLines++;
                    }
                }

                $timeCount = count($timeMatches[1]);
                $this->assertGreaterThan(0, $timeCount, "Page {$pageIndex} must contain time slots");
                $this->assertGreaterThanOrEqual($timeCount, $rightmostLines, "Page {$pageIndex} rightmost border lines must be fully drawn for all rows");

                $pageIndex++;
            }
        }
    }
}
