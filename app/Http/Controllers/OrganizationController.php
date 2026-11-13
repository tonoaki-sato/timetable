<?php

namespace App\Http\Controllers;

use App\Actions\Organization\DownloadOrganizationPdfAction;
use App\Actions\Organization\DownloadOrganizationStaffListPdfAction;
use App\DTOs\Organization\OrganizationUnitData;
use App\Http\Requests\StoreOrganizationUnitRequest;
use App\Http\Requests\UpdateOrganizationUnitRequest;
use App\Models\OrganizationUnit;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationService $organizationService,
        protected DownloadOrganizationPdfAction $downloadOrganizationPdfAction,
        protected DownloadOrganizationStaffListPdfAction $downloadOrganizationStaffListPdfAction
    ) {}

    /**
     * 組織一覧・組織図画面の表示
     */
    public function index(): View
    {
        $this->authorize('viewAny', OrganizationUnit::class);

        $viewData = $this->organizationService->getIndexViewData();

        return view('organizations.index', $viewData);
    }

    /**
     * 組織詳細データの取得（モーダル表示用）
     */
    public function show(OrganizationUnit $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        $data = $this->organizationService->getUnitDetails($organization);

        return response()->json($data);
    }

    /**
     * 組織ユニットの新規登録
     */
    public function store(StoreOrganizationUnitRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', OrganizationUnit::class);

        $dto = OrganizationUnitData::fromArray($request->validated());
        $unit = $this->organizationService->createUnit($dto);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "組織「{$unit->name}」を登録しました。",
                'unit' => $unit,
            ], 201);
        }

        return redirect()->route('organizations.index')
            ->with('success', "組織「{$unit->name}」を登録しました。");
    }

    /**
     * 組織ユニット編集用データの取得（モーダル表示用）
     */
    public function edit(OrganizationUnit $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $data = $this->organizationService->getUnitDetails($organization);

        return response()->json($data);
    }

    /**
     * 組織ユニットの更新
     */
    public function update(UpdateOrganizationUnitRequest $request, OrganizationUnit $organization): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $organization);

        $dto = OrganizationUnitData::fromArray($request->validated());
        $unit = $this->organizationService->updateUnit($organization, $dto);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "組織「{$unit->name}」を更新しました。",
                'unit' => $unit,
            ]);
        }

        return redirect()->route('organizations.index')
            ->with('success', "組織「{$unit->name}」を更新しました。");
    }

    /**
     * 組織ユニットの削除
     */
    public function destroy(Request $request, OrganizationUnit $organization): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $organization);

        $name = $organization->name;
        $this->organizationService->deleteUnit($organization);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "組織「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('organizations.index')
            ->with('success', "組織「{$name}」を削除しました。");
    }

    /**
     * 組織ユニットの並び順を一括保存（DnD操作時）
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('update', new OrganizationUnit);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:organization_units,id'],
        ]);

        $this->organizationService->reorderUnits($validated['ids']);

        return response()->json([
            'success' => true,
            'message' => '並び順を更新しました。',
        ]);
    }

    /**
     * 組織図A4縦PDFのダウンロード
     */
    public function print(): Response
    {
        $this->authorize('viewAny', OrganizationUnit::class);

        return $this->downloadOrganizationPdfAction->execute();
    }

    /**
     * 組織別所属スタッフ一覧A4縦PDFのダウンロード
     */
    public function staffListPdf(): Response
    {
        $this->authorize('viewAny', OrganizationUnit::class);

        return $this->downloadOrganizationStaffListPdfAction->execute();
    }
}
