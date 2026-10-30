<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentPrintController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 印刷用帳票画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $format = $request->query('format', 'all');

        $equipments = $this->logisticsService->getEquipmentsFlowData();
        $checklistPlans = $this->logisticsService->getPhaseChecklistData();
        $locationsData = $this->logisticsService->getLocationsStatusData();
        $eventCompanyPlans = $this->logisticsService->getEventCompanyScheduleData();
        $festivalDates = $this->logisticsService->resolveFestivalDates();

        return view('equipments.print', compact(
            'format',
            'equipments',
            'checklistPlans',
            'locationsData',
            'eventCompanyPlans',
            'festivalDates'
        ));
    }
}
