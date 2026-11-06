<?php

namespace App\Actions\Logistics;

use App\Models\Lane;
use App\Services\EquipmentLogisticsService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DownloadEquipmentLogisticsPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 資材搬出入指示書兼現場チェックリストPDFダウンロードを実行
     */
    public function execute(Request $request): Response
    {
        $format = $request->query('format', 'all');
        $typeFilter = $request->query('type');
        $laneFilter = $request->query('lane_id') ? (int) $request->query('lane_id') : null;
        $phaseFilter = $request->query('phase');

        $equipments = $this->logisticsService->getEquipmentsFlowData($typeFilter, $laneFilter);
        $checklistPlans = $this->logisticsService->getPhaseChecklistData($phaseFilter, $laneFilter, $typeFilter);
        $locationsData = $this->logisticsService->getLocationsStatusData();
        $onsiteNightPlans = $this->logisticsService->getOnsiteNightStoragePlans();
        $eventCompanyPlans = $this->logisticsService->getEventCompanyScheduleData();
        $festivalDates = $this->logisticsService->resolveFestivalDates();
        $lanes = Lane::ordered()->get();

        $selectedLane = $laneFilter ? Lane::find($laneFilter) : null;
        $laneSuffix = $selectedLane ? '_'.preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $selectedLane->name) : '';
        $filename = "equipment_logistics_plan{$laneSuffix}_".now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.equipment_logistics',
            compact(
                'format',
                'typeFilter',
                'laneFilter',
                'phaseFilter',
                'equipments',
                'checklistPlans',
                'locationsData',
                'onsiteNightPlans',
                'eventCompanyPlans',
                'festivalDates',
                'lanes',
                'selectedLane'
            ),
            $filename,
            'landscape'
        );
    }
}
