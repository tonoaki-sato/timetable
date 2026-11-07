<?php

namespace App\Actions\Staff;

use App\Models\Staff;
use App\Services\PdfService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class DownloadStaffCardsPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected QRCodeService $qrCodeService
    ) {}

    /**
     * スタッフ名札カード一括PDFダウンロードを実行
     */
    public function execute(Request $request): Response
    {
        $query = Staff::query()
            ->where('is_active', true);

        if ($request->has('ids') && is_array($request->input('ids'))) {
            $ids = array_filter(array_map('intval', $request->input('ids')));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $staffList = $query->orderBy('kana')->get();
        $cards = $this->buildCardsData($staffList);

        $filename = 'staff_cards_'.now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.staff_cards',
            [
                'cards' => $cards,
                'title' => 'スタッフ名札カード ('.$cards->count().'名)',
            ],
            $filename,
            'portrait'
        );
    }

    /**
     * 単一スタッフの名札カードPDFダウンロードを実行
     */
    public function executeForSingle(Staff $staff): Response
    {
        $cards = $this->buildCardsData(collect([$staff]), 120);

        $sanitizedName = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $staff->name);
        $filename = "staff_card_{$sanitizedName}_".now()->format('Ymd').'.pdf';

        return $this->pdfService->download(
            'pdf.staff_cards',
            [
                'cards' => $cards,
                'title' => "{$staff->name} 名札カード",
            ],
            $filename,
            'portrait'
        );
    }

    /**
     * スタッフ一覧からQRコード付きカードコレクションを構築
     *
     * @param  Collection<int, Staff>  $staffList
     * @return Collection<int, array{staff: Staff, shift_url: string, qr_svg: string, qr_data_uri: string}>
     */
    protected function buildCardsData(Collection $staffList, int $qrSize = 100): Collection
    {
        return $staffList->map(function (Staff $staff) use ($qrSize) {
            $shiftUrl = route('staff.myshift', ['token' => $staff->access_token]);
            $qrSvg = $this->qrCodeService->generateSvg($shiftUrl, $qrSize);
            $qrDataUri = $this->qrCodeService->generatePngDataUri($shiftUrl, $qrSize * 2);

            return [
                'staff' => $staff,
                'shift_url' => $shiftUrl,
                'qr_svg' => $qrSvg,
                'qr_data_uri' => $qrDataUri,
            ];
        });
    }
}
