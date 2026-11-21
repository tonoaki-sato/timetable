<?php

namespace App\Http\Controllers;

use App\DTOs\EquipmentFilterDTO;
use App\Http\Requests\ReorderEquipmentsRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentService $equipmentService,
    ) {}

    /**
     * 機材・備品一覧画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $filter = EquipmentFilterDTO::fromArray($request->query());

        return view(
            'equipments.index',
            $this->equipmentService->getIndexViewData($filter, $filter->perPage)
        );
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
    public function store(StoreEquipmentRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Equipment::class);

        $equipment = $this->equipmentService->createEquipment(
            $request->validated(),
            $request->user()?->id
        )->load('storageLocation');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資機材「{$equipment->name}」を登録しました。",
                'equipment' => $equipment,
            ]);
        }

        return redirect()->route('equipments.index')
            ->with('status', "資機材「{$equipment->name}」を登録しました。");
    }

    /**
     * 資機材更新処理
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $this->equipmentService->updateEquipment($equipment, $request->validated());
        $equipment->load('storageLocation');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資機材「{$equipment->name}」を更新しました。",
                'equipment' => $equipment,
            ]);
        }

        return redirect()->route('equipments.index')
            ->with('status', "資機材「{$equipment->name}」を更新しました。");
    }

    /**
     * 資機材削除処理
     */
    public function destroy(Request $request, Equipment $equipment): RedirectResponse|JsonResponse
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
}
