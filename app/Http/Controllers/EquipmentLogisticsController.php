<?php

namespace App\Http\Controllers;

use App\Actions\Logistics\DownloadEquipmentLogisticsPdfAction;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Services\EquipmentLogisticsService;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EquipmentLogisticsController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService,
        protected DownloadEquipmentLogisticsPdfAction $downloadLogisticsPdfAction,
        protected TimetableService $timetableService
    ) {}

    /**
     * 搬出入計画管理の対象タイムテーブル選択画面の表示
     */
    public function select(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Equipment::class);

        $timetables = $this->logisticsService->getTimetablesForSelect();

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

        $selectedEquipmentIds = $this->logisticsService->parseEquipmentIds($request->input('equipment_ids'));

        $viewData = $this->logisticsService->getLogisticsIndexViewData($timetable, $selectedEquipmentIds);
        $printQuery = ['timetable' => $timetable];
        if (! empty($selectedEquipmentIds)) {
            $printQuery['equipment_ids'] = implode(',', $selectedEquipmentIds);
        }
        $viewData['printUrl'] = route('timetables.logistics.print', $printQuery);

        return view('logistics.index', $viewData);
    }

    /**
     * 指定タイムテーブルにおけるA4縦 搬出入指示書兼チェックリストPDFダウンロード
     */
    public function print(Timetable $timetable, Request $request): Response
    {
        $this->authorize('viewAny', Equipment::class);

        return $this->downloadLogisticsPdfAction->execute($request, $timetable);
    }

    // ==========================================
    // 既存互換フォールバックアクション群
    // ==========================================

    public function printLegacy(Request $request): Response
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->print($timetable, $request);
    }

    protected function resolveFallbackTimetable(?Request $request = null): Timetable
    {
        $timetableId = ($request && $request->filled('timetable_id'))
            ? $request->integer('timetable_id')
            : null;

        return $this->timetableService->resolveFallbackTimetable($timetableId);
    }
}
