<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGozaStoreRequest;
use App\Http\Requests\UpdateGozaStoreRequest;
use App\Models\GozaStore;
use App\Models\Timetable;
use App\Services\SiteLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GozaStoreController extends Controller
{
    public function __construct(
        protected SiteLayoutService $siteLayoutService
    ) {}

    /**
     * ござ市出店者 一覧・管理画面
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $filters = [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];

        $stores = $this->siteLayoutService->paginateGozaStores($timetable, $filters, 25);

        return view('site_layouts.goza_stores', [
            'timetable' => $timetable,
            'stores' => $stores,
            'filters' => $filters,
        ]);
    }

    /**
     * 出店者を新規登録
     */
    public function store(StoreGozaStoreRequest $request, Timetable $timetable): RedirectResponse
    {
        $store = $this->siteLayoutService->createGozaStore($timetable, $request->validated());

        return redirect()->back()->with('success', 'ござ市出店者「'.$store->store_name.'」を登録しました。');
    }

    /**
     * 出店者情報を更新
     */
    public function update(UpdateGozaStoreRequest $request, Timetable $timetable, GozaStore $gozaStore): RedirectResponse
    {
        $this->siteLayoutService->updateGozaStore($gozaStore, $request->validated());

        return redirect()->back()->with('success', 'ござ市出店者「'.$gozaStore->store_name.'」の情報を更新しました。');
    }

    /**
     * 出店者を削除
     */
    public function destroy(Timetable $timetable, GozaStore $gozaStore): RedirectResponse
    {
        $name = $gozaStore->store_name;
        $this->siteLayoutService->deleteGozaStore($gozaStore);

        return redirect()->back()->with('success', 'ござ市出店者「'.$name.'」を削除しました。');
    }
}
