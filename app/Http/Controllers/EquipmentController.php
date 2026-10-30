<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Models\Lane;
use App\Models\LogisticsLocation;
use App\Models\Staff;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService
    ) {}

    /**
     * 資材・搬出入計画一覧画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $tab = $request->query('tab', 'flow');
        $typeFilter = $request->query('type');
        $laneFilter = $request->query('lane_id') ? (int) $request->query('lane_id') : null;
        $phaseFilter = $request->query('phase');

        $equipments = $this->logisticsService->getEquipmentsFlowData($typeFilter, $laneFilter);
        $checklistPlans = $this->logisticsService->getPhaseChecklistData($phaseFilter);
        $locationsData = $this->logisticsService->getLocationsStatusData();
        $eventCompanyPlans = $this->logisticsService->getEventCompanyScheduleData();

        $locations = LogisticsLocation::active()->ordered()->get();
        $lanes = Lane::active()->ordered()->get();
        $staffs = Staff::active()->orderBy('kana')->get();

        return view('equipments.index', compact(
            'tab',
            'typeFilter',
            'laneFilter',
            'phaseFilter',
            'equipments',
            'checklistPlans',
            'locationsData',
            'eventCompanyPlans',
            'locations',
            'lanes',
            'staffs'
        ));
    }

    /**
     * 資材新規登録画面の表示
     */
    public function create(): View
    {
        $this->authorize('create', Equipment::class);

        $locations = LogisticsLocation::active()->ordered()->get();
        $lanes = Lane::active()->ordered()->get();
        $staffs = Staff::active()->orderBy('kana')->get();

        return view('equipments.create', compact('locations', 'lanes', 'staffs'));
    }

    /**
     * 資材新規登録処理（テンプレート適用含む）
     */
    public function store(StoreEquipmentRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Equipment::class);

        $equipment = $this->logisticsService->createEquipmentWithTemplate(
            $request->validated(),
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資材「{$equipment->name}」を登録しました。",
                'equipment' => $equipment->load('plans'),
            ]);
        }

        return redirect()->route('equipments.index')
            ->with('status', "資材「{$equipment->name}」を登録しました。");
    }

    /**
     * 資材詳細画面の表示
     */
    public function show(Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        $equipment->load([
            'lane',
            'creator',
            'plans' => fn ($q) => $q->with(['fromLocationModel', 'toLocationModel', 'witnessStaff'])->orderBy('display_order')->orderBy('id'),
        ]);

        $locations = LogisticsLocation::active()->ordered()->get();
        $staffs = Staff::active()->orderBy('kana')->get();

        return view('equipments.show', compact('equipment', 'locations', 'staffs'));
    }

    /**
     * 資材編集画面の表示
     */
    public function edit(Equipment $equipment): View
    {
        $this->authorize('update', $equipment);

        $locations = LogisticsLocation::active()->ordered()->get();
        $lanes = Lane::active()->ordered()->get();

        return view('equipments.edit', compact('equipment', 'locations', 'lanes'));
    }

    /**
     * 資材更新処理
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $equipment->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資材「{$equipment->name}」を更新しました。",
                'equipment' => $equipment,
            ]);
        }

        return redirect()->route('equipments.show', $equipment)
            ->with('status', "資材「{$equipment->name}」を更新しました。");
    }

    /**
     * 資材削除処理
     */
    public function destroy(Request $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $equipment);

        $name = $equipment->name;
        $equipment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資材「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('equipments.index')
            ->with('status', "資材「{$name}」を削除しました。");
    }
}
