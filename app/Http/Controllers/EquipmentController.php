<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Models\Lane;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    /**
     * 資材管理（備品・機材台帳）一覧画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $typeFilter = $request->query('type');
        $laneFilter = $request->query('lane_id') ? (int) $request->query('lane_id') : null;
        $checkoutRuleFilter = $request->query('checkout_rule');
        $keyword = $request->query('keyword');

        $query = Equipment::query()
            ->with(['lane', 'plans'])
            ->when($typeFilter, fn ($q) => $q->where('type', $typeFilter))
            ->when($laneFilter, fn ($q) => $q->where('lane_id', $laneFilter))
            ->when($checkoutRuleFilter, fn ($q) => $q->where('checkout_rule', $checkoutRuleFilter))
            ->when($keyword, fn ($q) => $q->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('storage_location', 'like', "%{$keyword}%")
                    ->orWhere('storage_detail', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            }))
            ->ordered();

        $equipments = $query->paginate(20)->withQueryString();

        $lanes = Lane::ordered()->get();

        // 統計カウント
        $totalCount = Equipment::count();
        $committeeCount = Equipment::where('type', 'committee')->count();
        $rentalCount = Equipment::where('type', 'rental')->count();
        $personalCount = Equipment::where('type', 'personal')->count();

        return view('equipments.index', compact(
            'equipments',
            'lanes',
            'typeFilter',
            'laneFilter',
            'checkoutRuleFilter',
            'keyword',
            'totalCount',
            'committeeCount',
            'rentalCount',
            'personalCount'
        ));
    }

    /**
     * 資材新規登録画面の表示
     */
    public function create(): View
    {
        $this->authorize('create', Equipment::class);

        $lanes = Lane::ordered()->get();

        return view('equipments.create', compact('lanes'));
    }

    /**
     * 資材新規登録処理
     */
    public function store(StoreEquipmentRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Equipment::class);

        $data = $request->validated();
        if ($request->user() && empty($data['created_by'])) {
            $data['created_by'] = $request->user()->id;
        }

        $equipment = Equipment::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "資材「{$equipment->name}」を登録しました。",
                'equipment' => $equipment,
            ]);
        }

        return redirect()->route('equipments.show', $equipment)
            ->with('status', "資材「{$equipment->name}」を登録しました。");
    }

    /**
     * 資材マスター詳細画面の表示
     */
    public function show(Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        $equipment->load([
            'lane',
            'creator',
            'plans' => fn ($q) => $q->orderBy('display_order')->orderBy('id'),
        ]);

        return view('equipments.show', compact('equipment'));
    }

    /**
     * 資材編集画面の表示
     */
    public function edit(Equipment $equipment): View
    {
        $this->authorize('update', $equipment);

        $lanes = Lane::ordered()->get();

        return view('equipments.edit', compact('equipment', 'lanes'));
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
