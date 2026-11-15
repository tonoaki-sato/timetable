<?php

namespace App\Http\Controllers;

use App\Enums\BoothCategory;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Timetable;
use App\Models\Vendor;
use App\Services\SiteLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        protected SiteLayoutService $siteLayoutService
    ) {}

    /**
     * 出店者 一覧・管理画面
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $filters = [
            'category' => $request->query('category'),
            'search' => $request->query('search'),
        ];

        $vendors = $this->siteLayoutService->paginateVendors($timetable, $filters, 25);
        $allBooths = $this->siteLayoutService->getBoothsForSelect($timetable);
        $categories = BoothCategory::cases();

        return view('site_layouts.vendors', [
            'timetable' => $timetable,
            'vendors' => $vendors,
            'allBooths' => $allBooths,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * 出店者を新規登録
     */
    public function store(StoreVendorRequest $request, Timetable $timetable): RedirectResponse
    {
        $validated = $request->validated();
        $vendorData = [
            'name' => $validated['name'],
            'representative_name' => $validated['representative_name'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        $vendor = $this->siteLayoutService->createVendor(
            $timetable,
            $vendorData,
            $request->input('initial_booth_id') ? (int) $request->input('initial_booth_id') : null,
            $request->input('initial_category'),
            $request->input('initial_product_items')
        );

        return redirect()->back()->with('success', '出店者「'.$vendor->name.'」を登録しました。');
    }

    /**
     * 出店者情報を更新
     */
    public function update(UpdateVendorRequest $request, Timetable $timetable, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validated();
        $vendorData = [
            'name' => $validated['name'],
            'representative_name' => $validated['representative_name'] ?? null,
            'emergency_contact' => $validated['emergency_contact'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        $this->siteLayoutService->updateVendor(
            $vendor,
            $vendorData,
            $request->input('booths'),
            $request->input('add_booth_id') ? (int) $request->input('add_booth_id') : null,
            $request->input('add_category'),
            $request->input('add_product_items')
        );

        return redirect()->back()->with('success', '出店者「'.$vendor->name.'」の情報を更新しました。');
    }

    /**
     * 出店者を削除
     */
    public function destroy(Timetable $timetable, Vendor $vendor): RedirectResponse
    {
        $name = $vendor->name;
        $this->siteLayoutService->deleteVendor($vendor);

        return redirect()->back()->with('success', '出店者「'.$name.'」を削除しました。');
    }
}
