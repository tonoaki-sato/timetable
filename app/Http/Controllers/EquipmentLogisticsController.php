<?php

namespace App\Http\Controllers;

use App\Actions\Logistics\DownloadEquipmentLogisticsPdfAction;
use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Timetable;
use App\Services\EquipmentLogisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EquipmentLogisticsController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService,
        protected DownloadEquipmentLogisticsPdfAction $downloadLogisticsPdfAction
    ) {}

    /**
     * 搬出入計画管理の対象タイムテーブル選択画面の表示
     */
    public function select(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Equipment::class);

        $timetables = Timetable::withCount('logisticsPlans')
            ->orderBy('target_date', 'asc')
            ->get();

        if ($timetables->isEmpty()) {
            return redirect()->route('timetables.index')
                ->with('info', '搬出入計画を管理する前に、運行日を登録してください。');
        }

        if ($timetables->count() === 1) {
            return redirect()->route('timetables.logistics.index', array_merge(
                ['timetable' => $timetables->first()],
                $request->query()
            ));
        }

        return view('logistics.select', [
            'timetables' => $timetables,
            'queryParams' => $request->query(),
        ]);
    }

    /**
     * 指定タイムテーブルにおける搬出入計画一覧画面の表示
     */
    public function index(Timetable $timetable, Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $typeFilter = $request->query('type');
        $laneFilter = $request->query('lane_id') ? (int) $request->query('lane_id') : null;

        $locations = Location::forTimetable($timetable)->active()->ordered()->get();
        $lanes = $timetable->lanes()->ordered()->get();
        $equipments = $this->logisticsService->getEquipmentsFlowData(
            type: $typeFilter,
            laneId: $laneFilter,
            timetable: $timetable
        );

        $allPlans = $timetable->logisticsPlans()->get();
        $stats = [
            'total_equipments' => Equipment::count(),
            'planned_equipments' => $allPlans->pluck('equipment_id')->unique()->count(),
            'total_steps' => $allPlans->count(),
            'completed_steps' => $allPlans->where('status', 'completed')->count(),
            'witness_required_steps' => $allPlans->where('witness_required', true)->count(),
        ];

        return view('logistics.index', compact(
            'timetable',
            'locations',
            'lanes',
            'equipments',
            'stats',
            'typeFilter',
            'laneFilter'
        ));
    }

    /**
     * 指定タイムテーブルにおけるA4横 搬出入指示書兼チェックリストPDFダウンロード（またはプレビュー表示）
     */
    public function print(Timetable $timetable, Request $request): Response|View
    {
        $this->authorize('viewAny', Equipment::class);

        if ($request->boolean('preview')) {
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
            $lanes = $timetable->lanes()->ordered()->get();

            return view('logistics.print', compact(
                'timetable',
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

        return $this->downloadLogisticsPdfAction->execute($request, $timetable);
    }

    /**
     * 指定タイムテーブルにおける個別資材の搬出入計画・ステップ設定画面の表示
     */
    public function show(Timetable $timetable, Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        $equipment->load([
            'creator',
            'plans' => fn ($q) => $q->where('timetable_id', $timetable->id)->with([
                'equipment',
                'fromLocationModel.schedules',
                'toLocationModel.schedules',
                'witnessStaff',
            ])->orderBy('display_order')->orderBy('id'),
        ]);

        $locations = Location::forTimetable($timetable)->active()->ordered()->get();
        $staffs = Staff::active()->orderBy('kana')->get();
        $patterns = EquipmentLogisticsPattern::with('steps')->ordered()->get();

        return view('logistics.show', compact('timetable', 'equipment', 'locations', 'staffs', 'patterns'));
    }

    // ==========================================
    // 既存互換フォールバックアクション群
    // ==========================================

    public function showLegacy(Equipment $equipment): View
    {
        $timetable = $this->resolveFallbackTimetable();

        return $this->show($timetable, $equipment);
    }

    public function printLegacy(Request $request): Response|View
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->print($timetable, $request);
    }

    protected function resolveFallbackTimetable(?Request $request = null): Timetable
    {
        if ($request && $request->filled('timetable_id')) {
            $found = Timetable::find($request->integer('timetable_id'));
            if ($found) {
                return $found;
            }
        }

        return Timetable::where('status', 'active')->orderBy('target_date', 'asc')->first()
            ?? Timetable::orderBy('target_date', 'asc')->first()
            ?? Timetable::factory()->create(['name' => '既定運行計画', 'target_date' => now()->toDateString()]);
    }
}
