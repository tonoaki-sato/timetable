<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Lane;
use App\Models\Location;
use App\Models\Staff;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentLogisticsController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 搬出入計画ダッシュボード画面の表示（4大ビュー＋パターン管理）
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $tab = $request->query('tab', 'flow');
        $typeFilter = $request->query('type');
        $laneFilter = $request->query('lane_id') ? (int) $request->query('lane_id') : null;
        $phaseFilter = $request->query('phase');

        $equipments = $this->logisticsService->getEquipmentsFlowData($typeFilter, $laneFilter);
        $checklistPlans = $this->logisticsService->getPhaseChecklistData($phaseFilter, $laneFilter, $typeFilter);
        $locationsData = $this->logisticsService->getLocationsStatusData();
        $onsiteNightPlans = $this->logisticsService->getOnsiteNightStoragePlans();
        $eventCompanyPlans = $this->logisticsService->getEventCompanyScheduleData();
        $patterns = $this->logisticsService->getPatternsData();

        $locations = Location::active()->ordered()->get();
        $lanes = Lane::active()->ordered()->get();
        $staffs = Staff::active()->orderBy('kana')->get();

        return view('logistics.index', compact(
            'tab',
            'typeFilter',
            'laneFilter',
            'phaseFilter',
            'equipments',
            'checklistPlans',
            'locationsData',
            'onsiteNightPlans',
            'eventCompanyPlans',
            'patterns',
            'locations',
            'lanes',
            'staffs'
        ));
    }

    /**
     * A4横 搬出入指示書兼チェックリスト印刷画面の表示
     */
    public function print(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

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
        $lanes = Lane::active()->ordered()->get();

        return view('logistics.print', compact(
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
            'lanes'
        ));
    }
}
