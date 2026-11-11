<?php

namespace App\Actions\Logistics;

use App\Models\Timetable;
use App\Services\EquipmentLogisticsService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DownloadEquipmentLogisticsPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected EquipmentLogisticsService $logisticsService,
    ) {}

    /**
     * 資材搬出入指示書兼現場チェックリストPDFダウンロードを実行
     */
    public function execute(Request $request, ?Timetable $timetable = null): Response
    {
        $viewData = $this->buildViewData($request, $timetable);
        $filename = 'equipment_logistics_plan_'.now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.equipment_logistics',
            $viewData,
            $filename,
            'portrait'
        );
    }

    /**
     * 搬出入指示書PDF出力用ビューデータを構築
     *
     * @return array<string, mixed>
     */
    public function buildViewData(Request $request, ?Timetable $timetable = null): array
    {
        $typeFilter = $request->query('type');
        $equipmentIds = $this->logisticsService->parseEquipmentIds($request->input('equipment_ids'));

        return [
            'timetable' => $timetable,
            'typeFilter' => $typeFilter,
            'equipmentIds' => $equipmentIds,
            'equipments' => $this->logisticsService->getEquipmentsFlowData($typeFilter, $timetable, $equipmentIds),
            'festivalDates' => $this->logisticsService->resolveFestivalDates(),
        ];
    }
}
