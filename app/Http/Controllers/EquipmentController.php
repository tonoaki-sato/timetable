<?php

namespace App\Http\Controllers;

use App\DTOs\EquipmentFilterDTO;
use App\Http\Requests\ReorderEquipmentsRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function __construct(
        protected EquipmentService $equipmentService
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
            $this->equipmentService->getIndexViewData($filter, $timetable, 20),
            [
                'timetable' => $timetable,
                'isTimetableScoped' => $isTimetableScoped,
            ],
            $this->buildViewMeta($timetable, $isTimetableScoped)
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
        ], $this->buildViewMeta($timetable, $isTimetableScoped)));
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
        ], $this->buildViewMeta($timetable, $isTimetableScoped, $equipment)));
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
        ], $this->buildViewMeta($timetable, $isTimetableScoped, $equipment)));
    }

    /**
     * 資機材更新処理
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment, ?Timetable $timetable = null): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $equipment);

        $this->equipmentService->updateEquipment($equipment, $request->validated());
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
        $this->equipmentService->deleteEquipment($equipment);
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

        $timetableId = ($request && $request->filled('timetable_id'))
            ? $request->integer('timetable_id')
            : null;

        return $this->equipmentService->resolveTimetable($timetableId, $timetable);
    }

    /**
     * スコープ有無に応じた画面URL・タイトル群を構築
     *
     * @return array<string, mixed>
     */
    protected function buildViewMeta(?Timetable $timetable, bool $isTimetableScoped, ?Equipment $equipment = null): array
    {
        $scopedTimetable = $isTimetableScoped ? $timetable : null;

        return [
            'scopedTimetable' => $scopedTimetable,
            'indexUrl' => $scopedTimetable ? route('timetables.equipments.index', $scopedTimetable) : route('equipments.index'),
            'createUrl' => $scopedTimetable ? route('timetables.equipments.create', $scopedTimetable) : route('equipments.create'),
            'storeUrl' => $scopedTimetable ? route('timetables.equipments.store', $scopedTimetable) : route('equipments.store'),
            'reorderUrl' => $scopedTimetable ? route('timetables.equipments.reorder', $scopedTimetable) : route('equipments.reorder'),
            'showUrl' => $equipment ? $equipment->showUrl($scopedTimetable) : '',
            'editUrl' => $equipment ? $equipment->editUrl($scopedTimetable) : '',
            'updateUrl' => $equipment
                ? ($scopedTimetable ? route('timetables.equipments.update', [$scopedTimetable, $equipment]) : route('equipments.update', $equipment))
                : '',
            'destroyUrl' => $equipment ? $equipment->destroyUrl($scopedTimetable) : '',
            'indexTitle' => $scopedTimetable ? '資機材管理 ('.$scopedTimetable->name.')' : '機材・備品一覧',
            'indexDesc' => $scopedTimetable
                ? '「'.$scopedTimetable->name.'」（'.$scopedTimetable->target_date?->format('Y年m月d日').'）運行に使用する備品の台帳および配備状況を管理します。'
                : 'まつり運行全般で使用する自前保有備品、外部レンタル機材、個人持ち込み私物の台帳です。ドラッグ＆ドロップで表示順を変更できます。',
            'createTitle' => $scopedTimetable ? '資機材の新規登録 ('.$scopedTimetable->name.')' : '機材・備品の新規登録',
            'createDesc' => $scopedTimetable
                ? '運行計画「'.$scopedTimetable->name.'」で使用する機材・備品を新規登録します。'
                : '新規機材・備品を台帳に登録します。',
            'editTitle' => $equipment
                ? '資機材編集: '.$equipment->name.($scopedTimetable ? ' ('.$scopedTimetable->name.')' : '')
                : '',
            'editDesc' => $equipment
                ? '備品「'.$equipment->name.'」の基本情報や管理情報を編集します。'
                : '',
        ];
    }
}
