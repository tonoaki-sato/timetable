<?php

namespace App\Http\Controllers;

use App\DTOs\VendorFilterDTO;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        protected VendorService $vendorService
    ) {}

    /**
     * 出展・出店者マスター画面を表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        $filter = VendorFilterDTO::fromArray($request->query());
        $vendors = $this->vendorService->paginateVendors($filter);

        return view('vendors.index', [
            'vendors' => $vendors,
            'filter' => $filter,
        ]);
    }

    /**
     * 出展・出店者を新規登録
     */
    public function store(StoreVendorRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Vendor::class);

        $vendor = $this->vendorService->createVendor($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "「{$vendor->name}」を登録しました。",
                'vendor' => $vendor,
            ]);
        }

        return redirect()->route('vendors.index')
            ->with('success', "出展・出店者「{$vendor->name}」を登録しました。");
    }

    /**
     * 出展・出店者を更新
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $vendor);

        $updated = $this->vendorService->updateVendor($vendor, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "「{$updated->name}」の情報を更新しました。",
                'vendor' => $updated,
            ]);
        }

        return redirect()->route('vendors.index')
            ->with('success', "出展・出店者「{$updated->name}」を更新しました。");
    }

    /**
     * 出展・出店者を削除
     */
    public function destroy(Request $request, Vendor $vendor): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $vendor);

        $name = $vendor->name;
        $this->vendorService->deleteVendor($vendor);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('vendors.index')
            ->with('success', "出展・出店者「{$name}」を削除しました。");
    }
}
