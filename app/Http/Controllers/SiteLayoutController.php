<?php

namespace App\Http\Controllers;

use App\DTOs\LayoutItemFilterDTO;
use App\Enums\BoothCategory;
use App\Enums\FacilityType;
use App\Http\Requests\StoreLayoutItemRequest;
use App\Http\Requests\UpdateLayoutItemRequest;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Services\SiteLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteLayoutController extends Controller
{
    public function __construct(
        protected SiteLayoutService $siteLayoutService
    ) {}

    /**
     * 配置計画（メイン画面）
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('viewAny', LayoutMap::class);

        $map = $this->siteLayoutService->getLayoutDetails($timetable);
        $summary = $this->siteLayoutService->getSummary($map);

        return view('site_layouts.index', [
            'timetable' => $timetable,
            'map' => $map,
            'summary' => $summary,
        ]);
    }

    /**
     * 配置一覧画面（出店ブース・インフラ設備統合一覧）
     */
    public function booths(Request $request, Timetable $timetable): View
    {
        $this->authorize('viewAny', LayoutMap::class);

        $map = $this->siteLayoutService->getLayoutWithAreas($timetable);
        $filter = LayoutItemFilterDTO::fromArray($request->query());

        $items = $this->siteLayoutService->paginateItems($map, $filter);
        $vendors = $this->siteLayoutService->getVendorsForSelect($timetable);

        return view('site_layouts.booths', [
            'timetable' => $timetable,
            'map' => $map,
            'items' => $items,
            'vendors' => $vendors,
            'filters' => $filter->toFilterArray(),
            'areas' => $map->areas,
            'boothCategories' => BoothCategory::cases(),
            'facilityTypes' => FacilityType::cases(),
        ]);
    }

    /**
     * 配置アイテムの新規登録
     */
    public function storeBooth(StoreLayoutItemRequest $request, Timetable $timetable): RedirectResponse
    {
        $this->authorize('create', LayoutMap::class);

        $map = $this->siteLayoutService->getLayoutWithAreas($timetable);
        $item = $this->siteLayoutService->storeItem($map, $request->validated());

        return redirect()->back()->with('success', '配置情報（'.$item->code.'）を新規登録しました。');
    }

    /**
     * 配置アイテムの更新
     */
    public function updateBooth(UpdateLayoutItemRequest $request, Timetable $timetable, LayoutItem $item): RedirectResponse
    {
        $this->authorize('update', LayoutMap::class);

        $this->siteLayoutService->updateItem($item, $request->validated());

        return redirect()->back()->with('success', '配置情報（'.$item->code.'）を更新しました。');
    }

    /**
     * 配置アイテムの削除
     */
    public function destroyBooth(Timetable $timetable, LayoutItem $item): RedirectResponse
    {
        $this->authorize('delete', LayoutMap::class);

        $code = $item->code;
        $name = $item->displayName;
        $this->siteLayoutService->deleteItem($item);

        return redirect()->back()->with('success', '配置「'.$name.'（'.$code.'）」を削除しました。');
    }
}
