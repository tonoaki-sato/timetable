<?php

namespace App\Actions\Lane;

use App\DTOs\Lane\LaneListPrintDataDTO;
use App\DTOs\Lane\LanePrintRowDTO;
use App\Models\Lane;
use App\Models\Timetable;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Services\PdfService;
use Dompdf\Frame;
use Dompdf\Frame\FrameTreeIterator;
use Dompdf\FrameDecorator\TableCell;
use Illuminate\Http\Response;

/**
 * 担当班一覧 A4縦PDFダウンロードを実行するアクション
 */
class DownloadLanesPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected LaneRepositoryInterface $laneRepository
    ) {}

    /**
     * 担当班一覧PDFダウンロードを実行
     */
    public function execute(Timetable $timetable): Response
    {
        $printData = $this->buildPrintData($timetable);

        $sanitizedName = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $timetable->name) ?? 'timetable';
        $filename = "lanes_{$sanitizedName}_".now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.lanes',
            [
                'printData' => $printData,
                'title' => $printData->title,
            ],
            $filename,
            'portrait'
        );
    }

    /**
     * 担当班一覧PDF用の表示データDTOを構築
     */
    public function buildPrintData(Timetable $timetable): LaneListPrintDataDTO
    {
        $lanes = $this->laneRepository->getForTimetablePrint($timetable);

        $rows = [];

        foreach ($lanes as $index => $lane) {
            $hasLead = $lane->leadStaff !== null;

            $colorKey = array_key_exists((string) $lane->color, Lane::COLOR_PALETTE_MAP)
                ? (string) $lane->color
                : 'slate';

            $rows[] = new LanePrintRowDTO(
                orderNumber: $index + 1,
                name: $lane->name,
                colorKey: $colorKey,
                leadName: $lane->leadStaff?->name ?? '未設定',
                leadAffiliation: (string) ($lane->leadStaff?->affiliation ?? ''),
                hasLead: $hasLead,
                description: (string) ($lane->description ?? ''),
            );
        }

        $dateSuffix = $timetable->target_date ? ' ('.$timetable->target_date->format('Y/m/d').')' : '';
        $title = "【担当班一覧】{$timetable->name}{$dateSuffix}";
        $generatedAt = now()->format('Y/m/d H:i');

        $initialPrintData = new LaneListPrintDataDTO(
            timetable: $timetable,
            title: $title,
            generatedAt: $generatedAt,
            totalLanes: $lanes->count(),
            rows: $rows,
        );

        $uniformRowHeightPt = $this->measureUniformRowHeightPt($initialPrintData);

        if ($uniformRowHeightPt === null) {
            return $initialPrintData;
        }

        return new LaneListPrintDataDTO(
            timetable: $timetable,
            title: $title,
            generatedAt: $generatedAt,
            totalLanes: $lanes->count(),
            rows: $rows,
            uniformRowHeightPt: $uniformRowHeightPt,
        );
    }

    /**
     * Dompdfの実描画レイアウトから最も背の高い担当班行のコンテンツ高さを計測し、統一高さ(pt)を算出
     */
    protected function measureUniformRowHeightPt(LaneListPrintDataDTO $printData): ?float
    {
        if ($printData->rows === []) {
            return null;
        }

        $maxContentHeightPt = 0.0;

        $this->pdfService->renderDompdf(
            'pdf.lanes',
            [
                'printData' => $printData,
                'title' => $printData->title,
            ],
            'portrait',
            [
                [
                    'event' => 'begin_page_render',
                    'f' => function (Frame $pageFrame) use (&$maxContentHeightPt): void {
                        foreach (new FrameTreeIterator($pageFrame) as $frame) {
                            if (! $frame instanceof TableCell) {
                                continue;
                            }

                            $parent = $frame->get_parent();
                            if ($parent === null) {
                                continue;
                            }

                            $parentClass = (string) $parent->get_node()->getAttribute('class');
                            if (! str_contains($parentClass, 'lane-row')) {
                                continue;
                            }

                            $cellContentHeight = $frame->get_content_height();
                            if ($cellContentHeight > $maxContentHeightPt) {
                                $maxContentHeightPt = $cellContentHeight;
                            }
                        }
                    },
                ],
            ]
        );

        if ($maxContentHeightPt <= 0.0) {
            return null;
        }

        return ceil($maxContentHeightPt * 100) / 100;
    }
}
