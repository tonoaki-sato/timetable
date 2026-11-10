<?php

namespace App\Actions\Logistics;

use App\Models\Timetable;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Services\EquipmentLogisticsService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DownloadEquipmentLogisticsPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected EquipmentLogisticsService $logisticsService,
        protected LaneRepositoryInterface $laneRepository
    ) {}

    /**
     * 資材搬出入指示書兼現場チェックリストPDFダウンロードを実行
     */
    public function execute(Request $request, ?Timetable $timetable = null): Response
    {
        $format = $request->query('format', 'all');
        $typeFilter = $request->query('type');
        $laneFilter = $request->query('lane_id') ? (int) $request->query('lane_id') : null;
        $phaseFilter = $request->query('phase');

        $equipments = $this->logisticsService->getEquipmentsFlowData($typeFilter, $laneFilter, $timetable);
        $checklistPlans = $this->logisticsService->getPhaseChecklistData($phaseFilter, $laneFilter, $typeFilter, $timetable);
        $locationsData = $this->logisticsService->getLocationsStatusData($timetable);
        $onsiteNightPlans = $this->logisticsService->getOnsiteNightStoragePlans($timetable);
        $eventCompanyPlans = $this->logisticsService->getEventCompanyScheduleData($timetable);
        $festivalDates = $this->logisticsService->resolveFestivalDates();
        $lanes = $timetable
            ? $this->laneRepository->getOrderedWithLeadForTimetable($timetable)
            : $this->laneRepository->getAllOrdered();

        $selectedLane = $laneFilter ? $this->laneRepository->findById($laneFilter) : null;
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
