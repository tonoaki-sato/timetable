<?php

namespace App\Http\Controllers;

use App\Actions\Staff\DownloadStaffCardsPdfAction;
use App\Models\Staff;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StaffPrintController extends Controller
{
    public function __construct(
        protected DownloadStaffCardsPdfAction $downloadStaffCardsPdfAction,
        protected QRCodeService $qrCodeService
    ) {}

    /**
     * スタッフ名札カードの一括PDFダウンロード（またはプレビュー表示）
     */
    public function printCards(Request $request): Response|View
    {
        $this->authorize('viewAny', Staff::class);

        // プレビュー指定時はHTMLプレビュー画面を表示
        if ($request->boolean('preview')) {
            $query = Staff::query()->where('is_active', true);

            if ($request->has('ids') && is_array($request->input('ids'))) {
                $ids = array_filter(array_map('intval', $request->input('ids')));
                if (! empty($ids)) {
                    $query->whereIn('id', $ids);
                }
            }

            $staffList = $query->orderBy('kana')->get();

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

        return $this->downloadStaffCardsPdfAction->execute($request);
    }

    /**
     * 単一スタッフの名札カードPDFダウンロード（またはプレビュー表示）
     */
    public function printSingleCard(Staff $staff, Request $request): Response|View
    {
        $this->authorize('view', $staff);

        // プレビュー指定時はHTMLプレビュー画面を表示
        if ($request->boolean('preview')) {
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

        return $this->downloadStaffCardsPdfAction->executeForSingle($staff);
    }
}
