<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffPrintController extends Controller
{
    public function __construct(
        protected QRCodeService $qrCodeService
    ) {}

    /**
     * スタッフ名札カードの一括印刷画面の表示
     */
    public function printCards(Request $request): View
    {
        $this->authorize('viewAny', Staff::class);

        $query = Staff::query()->where('is_active', true);

        // 指定IDがある場合は絞り込み
        if ($request->has('ids') && is_array($request->input('ids'))) {
            $ids = array_filter(array_map('intval', $request->input('ids')));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $staffList = $query->orderBy('kana')->get();

        // 各スタッフのマイシフトURLおよびQRコードSVGを付与
        $cards = $staffList->map(function (Staff $staff) {
            $shiftUrl = route('staff.myshift', ['token' => $staff->access_token]);
            $qrSvg = $this->qrCodeService->generateSvg($shiftUrl, 100);

            return [
                'staff' => $staff,
                'shift_url' => $shiftUrl,
                'qr_svg' => $qrSvg,
            ];
        });

        return view('staff.print_cards', [
            'cards' => $cards,
            'title' => 'スタッフ名札カード一括印刷 ('.$cards->count().'名)',
        ]);
    }

    /**
     * 単一スタッフの名札カード印刷画面の表示
     */
    public function printSingleCard(Staff $staff): View
    {
        $this->authorize('view', $staff);

        $shiftUrl = route('staff.myshift', ['token' => $staff->access_token]);
        $qrSvg = $this->qrCodeService->generateSvg($shiftUrl, 120);

        $cards = collect([
            [
                'staff' => $staff,
                'shift_url' => $shiftUrl,
                'qr_svg' => $qrSvg,
            ],
        ]);

        return view('staff.print_cards', [
            'cards' => $cards,
            'title' => "{$staff->name} 名札カード印刷",
        ]);
    }
}
