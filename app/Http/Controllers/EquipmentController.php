<?php

namespace App\Http\Controllers;

use App\DTOs\EquipmentFilterDTO;
use App\Http\Requests\ReorderEquipmentsRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Presenters\EquipmentViewPresenter;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentService $equipmentService,
        protected EquipmentViewPresenter $viewPresenter,
    ) {}

    /**
     * 資機材管理（台帳・配備一覧）画面の表示
     */
    public function index(Request $request, ?Timetable $timetable = null): View
    {
        $this->authorize('viewAny', Equipment::class);

        $timetable = $this->resolveTimetable($request, $timetable);
        $filter = EquipmentFilterDTO::fromArray($request->query());
        $isTimetableScoped = $request->route('timetable') instanceof Timetable;

        return view('equipments.index', array_merge(
            $this->equipmentService->getIndexViewData($filter, $timetable, $filter->perPage),
            [
                'timetable' => $timetable,
                'isTimetableScoped' => $isTimetableScoped,
            ],
            $this->viewPresenter->buildViewMeta($timetable, $isTimetableScoped)
        ));
    }

    /**
     * 資機材新規登録画面の表示
     */
    public function create(Request $request, ?Timetable $timetable = null): View
    {
        $this->authorize('create', Equipment::class);

        $timetable = $this->resolveTimetable($request, $timetable);
        $isTimetableScoped = $request->route('timetable') instanceof Timetable;

        return view('equipments.create', array_merge([
            'timetable' => $timetable,
            'isTimetableScoped' => $isTimetableScoped,
        ], $this->viewPresenter->buildViewMeta($timetable, $isTimetableScoped)));
    }

    /**
     * 備品の表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderEquipmentsRequest $request): JsonResponse
    {
        $this->authorize('update', Equipment::class);

        $this->equipmentService->reorderEquipments($request->validated('equipment_ids'));

        return response()->json([
            'success' => true,
            'message' => '備品の表示順を更新しました。',
        ]);
    }

    /**
     * 資機材新規登録処理
     */
    public function store(StoreEquipmentRequest $request, ?Timetable $timetable = null): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Equipment::class);

        $equipment = $this->equipmentService->createEquipment(
            $request->validated(),
            $request->user()?->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資機材「{$equipment->name}」を登録しました。",
                'equipment' => $equipment,
            ]);
        }

        return redirect()->route('equipments.show', $equipment)
            ->with('status', "資機材「{$equipment->name}」を登録しました。");
    }

    /**
     * 資機材マスター詳細画面の表示
     */
    public function show(Request $request, Equipment $equipment, ?Timetable $timetable = null): View
    {
        $this->authorize('view', $equipment);

        $timetable = $this->resolveTimetable($request, $timetable);
        $equipment = $this->equipmentService->findWithDetails($equipment->id, $timetable) ?? $equipment;
        $isTimetableScoped = $request->route('timetable') instanceof Timetable;

        return view('equipments.show', array_merge([
            'timetable' => $timetable,
            'equipment' => $equipment,
            'isTimetableScoped' => $isTimetableScoped,
        ], $this->viewPresenter->buildViewMeta($timetable, $isTimetableScoped, $equipment)));
    }

    /**
     * 資機材編集画面の表示
     */
    public function edit(Request $request, Equipment $equipment, ?Timetable $timetable = null): View
    {
        $this->authorize('update', $equipment);

        $timetable = $this->resolveTimetable($request, $timetable);
        $isTimetableScoped = $request->route('timetable') instanceof Timetable;

        return view('equipments.edit', array_merge([
            'timetable' => $timetable,
            'equipment' => $equipment,
            'isTimetableScoped' => $isTimetableScoped,
        ], $this->viewPresenter->buildViewMeta($timetable, $isTimetableScoped, $equipment)));
    }

    /**
     * 資機材更新処理
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment, ?Timetable $timetable = null): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $this->equipmentService->updateEquipment($equipment, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資機材「{$equipment->name}」を更新しました。",
                'equipment' => $equipment,
            ]);
        }

        return redirect()->route('equipments.show', $equipment)
            ->with('status', "資機材「{$equipment->name}」を更新しました。");
    }

    /**
     * 資機材削除処理
     */
    public function destroy(Request $request, Equipment $equipment, ?Timetable $timetable = null): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $equipment);

        $name = $equipment->name;
        $this->equipmentService->deleteEquipment($equipment);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資機材「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('equipments.index')
            ->with('status', "資機材「{$name}」を削除しました。");
    }

    /**
     * タイムテーブルスコープ用のアクション群
     */
    public function timetableIndex(Request $request, Timetable $timetable): View
    {
        return $this->index($request, $timetable);
    }

    public function timetableCreate(Request $request, Timetable $timetable): View
    {
        return $this->create($request, $timetable);
    }

    public function timetableStore(StoreEquipmentRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        return $this->store($request, $timetable);
    }

    public function timetableShow(Request $request, Timetable $timetable, Equipment $equipment): View
    {
        return $this->show($request, $equipment, $timetable);
    }

    public function timetableEdit(Request $request, Timetable $timetable, Equipment $equipment): View
    {
        return $this->edit($request, $equipment, $timetable);
    }

    public function timetableUpdate(UpdateEquipmentRequest $request, Timetable $timetable, Equipment $equipment): RedirectResponse|JsonResponse
    {
        return $this->update($request, $equipment, $timetable);
    }

    public function timetableDestroy(Request $request, Timetable $timetable, Equipment $equipment): RedirectResponse|JsonResponse
    {
        return $this->destroy($request, $equipment, $timetable);
    }

    /**
     * 対象運行日（Timetable）の解決ヘルパー
     */
    protected function resolveTimetable(?Request $request = null, ?Timetable $timetable = null): Timetable
    {
        if ($timetable && $timetable->exists) {
            return $timetable;
        }

        if ($request && $request->route('timetable') instanceof Timetable) {
            return $request->route('timetable');
        }

        $timetableId = ($request && $request->filled('timetable_id'))
            ? $request->integer('timetable_id')
            : null;

        return $this->equipmentService->resolveTimetable($timetableId, $timetable);
    }
}
