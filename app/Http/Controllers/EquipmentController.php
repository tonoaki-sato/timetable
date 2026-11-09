<?php

namespace App\Http\Controllers;

use App\DTOs\EquipmentFilterDTO;
use App\Http\Requests\ReorderEquipmentsRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Models\Lane;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository
    ) {}

    /**
     * 資機材管理（台帳・配備一覧）画面の表示
     */
    public function index(Request $request, ?Timetable $timetable = null): View
    {
        $this->authorize('viewAny', Equipment::class);

        $timetable = $this->resolveTimetable($request, $timetable);
        $filter = EquipmentFilterDTO::fromArray($request->query());

        $equipments = $this->equipmentRepository->getPaginated($filter, $timetable, 20);
        $lanes = $timetable->lanes()->ordered()->get();
        if ($lanes->isEmpty()) {
            $lanes = Lane::ordered()->get();
        }

        $counts = $this->equipmentRepository->getCountsByType();

        return view('equipments.index', [
            'timetable' => $timetable,
            'equipments' => $equipments,
            'lanes' => $lanes,
            'typeFilter' => $filter->type,
            'laneFilter' => $filter->laneId,
            'checkoutRuleFilter' => $filter->checkoutRule,
            'keyword' => $filter->keyword,
            'totalCount' => $counts['total'],
            'committeeCount' => $counts['committee'],
            'rentalCount' => $counts['rental'],
            'personalCount' => $counts['personal'],
            'isTimetableScoped' => $request->route('timetable') instanceof Timetable,
        ]);
    }

    /**
     * 資機材新規登録画面の表示
     */
    public function create(Request $request, ?Timetable $timetable = null): View
    {
        $this->authorize('create', Equipment::class);

        $timetable = $this->resolveTimetable($request, $timetable);
        $lanes = $timetable->lanes()->ordered()->get();
        if ($lanes->isEmpty()) {
            $lanes = Lane::ordered()->get();
        }

        return view('equipments.create', [
            'timetable' => $timetable,
            'lanes' => $lanes,
            'isTimetableScoped' => $request->route('timetable') instanceof Timetable,
        ]);
    }

    /**
     * 備品の表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderEquipmentsRequest $request): JsonResponse
    {
        $this->authorize('update', Equipment::class);

        $this->equipmentRepository->reorder($request->validated('equipment_ids'));

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

        $data = $request->validated();
        if ($request->user() && empty($data['created_by'])) {
            $data['created_by'] = $request->user()->id;
        }
        if (! isset($data['display_order']) || $data['display_order'] === null) {
            $data['display_order'] = $this->equipmentRepository->getMaxDisplayOrder() + 10;
        }

        $equipment = $this->equipmentRepository->create($data);
        $timetable = $this->resolveTimetable($request, $timetable);

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
     * 資機材マスター詳細画面の表示 (2:1 カラムレイアウト)
     */
    public function show(Request $request, Equipment $equipment, ?Timetable $timetable = null): View
    {
        $this->authorize('view', $equipment);

        $timetable = $this->resolveTimetable($request, $timetable);
        $equipment = $this->equipmentRepository->findWithDetails($equipment->id, $timetable) ?? $equipment;
        $allTimetables = Timetable::withCount([
            'logisticsPlans' => fn ($query) => $query->where('equipment_id', $equipment->id),
        ])->orderBy('target_date', 'asc')->get();

        return view('equipments.show', [
            'timetable' => $timetable,
            'equipment' => $equipment,
            'allTimetables' => $allTimetables,
            'isTimetableScoped' => $request->route('timetable') instanceof Timetable,
        ]);
    }

    /**
     * 資機材編集画面の表示
     */
    public function edit(Request $request, Equipment $equipment, ?Timetable $timetable = null): View
    {
        $this->authorize('update', $equipment);

        $timetable = $this->resolveTimetable($request, $timetable);
        $lanes = $timetable->lanes()->ordered()->get();
        if ($lanes->isEmpty()) {
            $lanes = Lane::ordered()->get();
        }

        return view('equipments.edit', [
            'timetable' => $timetable,
            'equipment' => $equipment,
            'lanes' => $lanes,
            'isTimetableScoped' => $request->route('timetable') instanceof Timetable,
        ]);
    }

    /**
     * 資機材更新処理
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment, ?Timetable $timetable = null): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $this->equipmentRepository->update($equipment, $request->validated());
        $timetable = $this->resolveTimetable($request, $timetable);

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
        $this->equipmentRepository->delete($equipment);
        $timetable = $this->resolveTimetable($request, $timetable);

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
