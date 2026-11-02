<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
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
     * 搬出入パターン管理画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $tab = $request->query('tab', 'patterns');
        $patterns = $this->logisticsService->getPatternsData();
        $locations = Location::active()->ordered()->get();

        return view('logistics.index', compact(
            'tab',
            'patterns',
            'locations'
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

    /**
     * 個別資材の搬出入計画・ステップ設定画面の表示
     */
    public function show(Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        $equipment->load([
            'lane',
            'creator',
            'plans' => fn ($q) => $q->with([
                'equipment',
                'fromLocationModel.schedules',
                'toLocationModel.schedules',
                'witnessStaff',
            ])->orderBy('display_order')->orderBy('id'),
        ]);

        $locations = Location::active()->ordered()->get();
        $staffs = Staff::active()->orderBy('kana')->get();
        $patterns = EquipmentLogisticsPattern::with('steps')->ordered()->get();

        return view('logistics.show', compact('equipment', 'locations', 'staffs', 'patterns'));
    }
}
