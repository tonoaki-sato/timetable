<?php

namespace App\Http\Controllers;

use App\Enums\BoothCategory;
use App\Enums\FacilityType;
use App\Http\Requests\StoreLayoutItemRequest;
use App\Http\Requests\UpdateLayoutItemRequest;
use App\Models\LayoutItem;
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
        $map = $this->siteLayoutService->getLayoutDetails($timetable);

        $filters = [
            'item_type' => $request->query('item_type'),
            'area_id' => $request->query('area_id'),
            'category' => $request->query('category'),
            'search' => $request->query('search'),
        ];

        $items = $this->siteLayoutService->paginateItems($map, $filters, 50);
        $vendors = $this->siteLayoutService->getVendorsForSelect($timetable);

        return view('site_layouts.booths', [
            'timetable' => $timetable,
            'map' => $map,
            'items' => $items,
            'vendors' => $vendors,
            'filters' => $filters,
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
        $map = $this->siteLayoutService->getLayoutDetails($timetable);
        $item = $this->siteLayoutService->storeItem($map, $request->validated());

        return redirect()->back()->with('success', '配置情報（'.$item->code.'）を新規登録しました。');
    }

    /**
     * 配置アイテムの更新
     */
    public function updateBooth(UpdateLayoutItemRequest $request, Timetable $timetable, LayoutItem $item): RedirectResponse
    {
        $this->siteLayoutService->updateItem($item, $request->validated());

        return redirect()->back()->with('success', '配置情報（'.$item->code.'）を更新しました。');
    }

    /**
     * 配置アイテムの削除
     */
    public function destroyBooth(Timetable $timetable, LayoutItem $item): RedirectResponse
    {
        $code = $item->code;
        $name = $item->displayName;
        $this->siteLayoutService->deleteItem($item);

        return redirect()->back()->with('success', '配置「'.$name.'（'.$code.'）」を削除しました。');
    }
}
