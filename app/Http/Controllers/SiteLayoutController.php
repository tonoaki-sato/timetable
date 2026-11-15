<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLayoutBoothRequest;
use App\Http\Requests\UpdateLayoutFacilityRequest;
use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
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
     * 現場配置計画 ダッシュボード（メイン画面）
     */
    public function index(Timetable $timetable): View
    {
        $map = $this->siteLayoutService->getLayoutDetails($timetable);
        $summary = $this->siteLayoutService->getSummary($map);
        $streetMapViewData = $this->siteLayoutService->getStreetMapViewData($map);

        return view('site_layouts.index', [
            'timetable' => $timetable,
            'map' => $map,
            'summary' => $summary,
            'streetMapViewData' => $streetMapViewData,
        ]);
    }

    /**
     * コマ割り（ブース）一覧・クイック編集画面
     */
    public function booths(Request $request, Timetable $timetable): View
    {
        $map = $this->siteLayoutService->getLayoutDetails($timetable);

        $filters = [
            'area_id' => $request->query('area_id'),
            'category' => $request->query('category'),
            'search' => $request->query('search'),
        ];

        $booths = $this->siteLayoutService->paginateBooths($map, $filters, 25);
        $gozaStores = $this->siteLayoutService->getGozaStoresForSelect($timetable);

        return view('site_layouts.booths', [
            'timetable' => $timetable,
            'map' => $map,
            'booths' => $booths,
            'gozaStores' => $gozaStores,
            'filters' => $filters,
            'areas' => $map->areas,
        ]);
    }

    /**
     * コマ割り情報の更新
     */
    public function updateBooth(UpdateLayoutBoothRequest $request, Timetable $timetable, LayoutBooth $booth): RedirectResponse
    {
        $this->siteLayoutService->updateBooth($booth, $request->validated());

        return redirect()->back()->with('success', 'コマ割り情報（'.$booth->booth_code.'）を更新しました。');
    }

    /**
     * インフラ設備一覧画面
     */
    public function facilities(Request $request, Timetable $timetable): View
    {
        $map = $this->siteLayoutService->getLayoutDetails($timetable);

        $filters = [
            'type' => $request->query('type'),
        ];

        $facilities = $this->siteLayoutService->getFacilities($map, $filters);

        return view('site_layouts.facilities', [
            'timetable' => $timetable,
            'map' => $map,
            'facilities' => $facilities,
            'filters' => $filters,
            'areas' => $map->areas,
        ]);
    }

    /**
     * 現場設備の更新
     */
    public function updateFacility(UpdateLayoutFacilityRequest $request, Timetable $timetable, LayoutFacility $facility): RedirectResponse
    {
        $this->siteLayoutService->updateFacility($facility, $request->validated());

        return redirect()->back()->with('success', '設備情報（'.$facility->name.'）を更新しました。');
    }
}
