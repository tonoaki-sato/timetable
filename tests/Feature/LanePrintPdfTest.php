<?php

namespace Tests\Feature;

use App\Actions\Lane\DownloadLanesPdfAction;
use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Services\PdfService;
use Dompdf\Frame;
use Dompdf\Frame\FrameTreeIterator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableRow as TableRowFrameDecorator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanePrintPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);
    }

    /**
     * オペレーターおよび閲覧専用ユーザーが担当班一覧をA4縦PDFでダウンロードできること
     */
    public function test_operator_and_viewer_can_download_lanes_pdf(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '本祭 運行計画',
            'target_date' => '2027-10-17',
        ]);

        $leadStaff = Staff::factory()->create([
            'name' => '佐藤 班長',
            'affiliation' => '祭礼本部',
        ]);
        $fixedStaff = Staff::factory()->create([
            'name' => '鈴木 固定',
            'affiliation' => '青年会',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'color' => 'amber',
            'lead_staff_id' => $leadStaff->id,
            'description' => '路上規制バーおよびコーンの搬入・設置・撤去を担当',
            'display_order' => 10,
        ]);
        $lane->fixedStaff()->attach([$leadStaff->id, $fixedStaff->id]);

        foreach ([$this->operator, $this->viewer] as $user) {
            $response = $this->actingAs($user)
                ->get(route('timetables.lanes.print', $timetable));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/pdf');
            $contentDisposition = (string) $response->headers->get('Content-Disposition');
            $this->assertStringContainsString('lanes_', $contentDisposition);
            $this->assertStringContainsString('.pdf', $contentDisposition);
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    /**
     * 担当班一覧PDFデータ構築およびテンプレート描画に余白・4列構成（専属固定スタッフ・登録タスク数除外）・行高さ統一が反映されること
     */
    public function test_print_data_and_pdf_template_render_margins_without_fixed_staff_and_with_uniform_row_height(): void
    {
        $timetable = Timetable::factory()->create([
            'name' => '宵宮 運行計画',
            'target_date' => '2027-10-16',
        ]);

        $leadStaff = Staff::factory()->create([
            'name' => '高橋 リーダー',
            'affiliation' => '町内会A',
        ]);
        $fixedStaff = Staff::factory()->create([
            'name' => '田中 固定除外対象',
            'affiliation' => 'ボランティアB',
        ]);

        $lane1 = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '本陣',
            'color' => 'indigo',
            'lead_staff_id' => $leadStaff->id,
            'description' => "本部運営および総合案内\n緊急連絡・アナウンス対応\n来賓応接を含む全体統括",
            'display_order' => 10,
        ]);
        $lane1->fixedStaff()->attach([$leadStaff->id, $fixedStaff->id]);

        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'color' => 'emerald',
            'lead_staff_id' => null,
            'description' => '清掃',
            'display_order' => 20,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane1->id,
            'title' => '朝礼準備',
        ]);

        /** @var DownloadLanesPdfAction $action */
        $action = app(DownloadLanesPdfAction::class);
        $printData = $action->buildPrintData($timetable);

        $this->assertSame(2, $printData->totalLanes);
        $this->assertNotNull($printData->uniformRowHeightPt);
        $this->assertGreaterThan(25.0, $printData->uniformRowHeightPt);
        $this->assertCount(2, $printData->rows);

        $firstRow = $printData->rows[0];
        $this->assertSame(1, $firstRow->orderNumber);
        $this->assertSame('本陣', $firstRow->name);
        $this->assertTrue($firstRow->hasLead);
        $this->assertSame('高橋 リーダー', $firstRow->leadName);
        $this->assertSame('町内会A', $firstRow->leadAffiliation);

        $secondRow = $printData->rows[1];
        $this->assertSame(2, $secondRow->orderNumber);
        $this->assertFalse($secondRow->hasLead);
        $this->assertSame('未設定', $secondRow->leadName);

        /** @var PdfService $pdfService */
        $pdfService = app(PdfService::class);
        $renderedHtml = view('pdf.lanes', [
            'printData' => $printData,
            'title' => $printData->title,
            'japaneseFontPath' => $pdfService->getJapaneseFontPath(),
        ])->render();

        $this->assertStringContainsString('size: A4 portrait;', $renderedHtml);
        $this->assertStringContainsString('margin: 12mm;', $renderedHtml);
        $this->assertStringNotContainsString('*, body,', $renderedHtml);
        $this->assertStringContainsString('まつり運行 担当班一覧表', $renderedHtml);
        $this->assertStringContainsString('宵宮 運行計画', $renderedHtml);
        $this->assertStringContainsString('本陣', $renderedHtml);
        $this->assertStringContainsString('高橋 リーダー', $renderedHtml);
        $this->assertStringContainsString('本部運営および総合案内', $renderedHtml);
        $this->assertStringContainsString('衛生', $renderedHtml);
        $this->assertStringContainsString('未設定', $renderedHtml);
        $this->assertStringContainsString('総班数:', $renderedHtml);
        $this->assertStringContainsString('2班', $renderedHtml);
        $this->assertStringNotContainsString('班長任命', $renderedHtml);
        $this->assertStringNotContainsString('専属固定スタッフ', $renderedHtml);
        $this->assertStringNotContainsString('専属固定:', $renderedHtml);
        $this->assertStringNotContainsString('田中 固定除外対象', $renderedHtml);
        $this->assertStringNotContainsString('登録タスク数', $renderedHtml);
        $this->assertStringNotContainsString('タスク数', $renderedHtml);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $renderedHtml);

        $rowHeights = [];
        $firstTableX = null;
        $firstTableY = null;
        $headerMetaY = null;
        $headerStatsY = null;

        $pdfService->renderDompdf(
            'pdf.lanes',
            [
                'printData' => $printData,
                'title' => $printData->title,
            ],
            'portrait',
            [
                [
                    'event' => 'begin_page_render',
                    'f' => function (Frame $pageFrame) use (&$rowHeights, &$firstTableX, &$firstTableY, &$headerMetaY, &$headerStatsY): void {
                        foreach (new FrameTreeIterator($pageFrame) as $frame) {
                            $nodeClass = method_exists($frame->get_node(), 'getAttribute')
                                ? (string) $frame->get_node()->getAttribute('class')
                                : '';

                            if (str_contains($nodeClass, 'header-meta') && $headerMetaY === null) {
                                $metaPos = $frame->get_position();
                                $headerMetaY = (float) $metaPos[1];
                            }

                            if (str_contains($nodeClass, 'header-stats') && $headerStatsY === null) {
                                $statsPos = $frame->get_position();
                                $headerStatsY = (float) $statsPos[1];
                            }

                            if ($frame instanceof TableFrameDecorator && $firstTableX === null) {
                                $position = $frame->get_position();
                                $firstTableX = (float) $position[0];
                                $firstTableY = (float) $position[1];
                            }

                            if (! $frame instanceof TableRowFrameDecorator) {
                                continue;
                            }

                            $rowClass = (string) $frame->get_node()->getAttribute('class');
                            if (! str_contains($rowClass, 'lane-row')) {
                                continue;
                            }

                            $table = TableFrameDecorator::find_parent_table($frame);
                            $this->assertNotNull($table);
                            $rowHeights[] = $table->get_cellmap()->get_frame_height($frame);
                        }
                    },
                ],
            ]
        );

        // 12mm ≒ 34.02pt の余白が実際に効いていること
        $this->assertNotNull($firstTableX);
        $this->assertNotNull($firstTableY);
        $this->assertGreaterThan(33.0, $firstTableX);
        $this->assertGreaterThan(33.0, $firstTableY);

        // 総班数バッジがメタ情報行より下の段に配置されていること
        $this->assertNotNull($headerMetaY);
        $this->assertNotNull($headerStatsY);
        $this->assertGreaterThan($headerMetaY, $headerStatsY);

        // すべてのデータ行の高さが最も高い行に一致していること
        $this->assertCount(2, $rowHeights);
        $this->assertEqualsWithDelta($rowHeights[0], $rowHeights[1], 0.05);
    }

    /**
     * 担当班が0件のタイムテーブルでも正常にPDFダウンロードできること
     */
    public function test_can_download_lanes_pdf_when_no_lanes_exist(): void
    {
        $timetable = Timetable::factory()->create(['name' => '空の運行日']);

        $response = $this->actingAs($this->operator)
            ->get(route('timetables.lanes.print', $timetable));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * 未ログインユーザーおよび停止済みユーザーは担当班一覧PDFをダウンロードできないこと
     */
    public function test_guest_and_inactive_user_cannot_download_lanes_pdf(): void
    {
        $timetable = Timetable::factory()->create();

        $this->get(route('timetables.lanes.print', $timetable))
            ->assertRedirect(route('login'));

        $inactiveUser = User::factory()->create([
            'role' => 'operator',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveUser)
            ->get(route('timetables.lanes.print', $timetable))
            ->assertStatus(403);
    }

    /**
     * 担当班一覧画面にPDFダウンロードボタンが表示され、データ登録済み状態でも厳格CSPに準拠していること
     */
    public function test_lanes_index_displays_pdf_download_button_and_complies_with_strict_csp(): void
    {
        $timetable = Timetable::factory()->create(['name' => 'CSP検証用運行日']);
        $leadStaff = Staff::factory()->create(['name' => '伊藤 班長']);
        $fixedStaff = Staff::factory()->create(['name' => '渡辺 固定']);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '巡回',
            'lead_staff_id' => $leadStaff->id,
            'description' => '会場警備・動線監視',
        ]);
        $lane->fixedStaff()->attach([$leadStaff->id, $fixedStaff->id]);
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '会場見回り',
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('timetables.lanes.index', $timetable));

        $response->assertStatus(200);
        $response->assertSee(route('timetables.lanes.print', $timetable));
        $response->assertSee('担当班一覧PDF');
        $response->assertSee('巡回');
        $response->assertSee('伊藤 班長');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);

        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $content);
    }
}
