<?php

namespace App\Actions\Staff;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Services\PdfService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class DownloadStaffCardsPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected QRCodeService $qrCodeService,
        protected StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * スタッフ名札カード一括PDFダウンロードを実行
     */
    public function execute(Request $request): Response
    {
        $cards = $this->buildCardsForRequest($request);

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
     * リクエスト条件（ids指定可）に基づいてQRコード付きカードコレクションを構築
     *
     * @return Collection<int, array{staff: Staff, shift_url: string, qr_svg: string, qr_data_uri: string}>
     */
    public function buildCardsForRequest(Request $request): Collection
    {
        $ids = null;
        if ($request->has('ids') && is_array($request->input('ids'))) {
            $filtered = array_filter(array_map('intval', $request->input('ids')));
            if (! empty($filtered)) {
                $ids = array_values($filtered);
            }
        }

        $staffList = $this->staffRepository->getActiveByIds($ids);

        return $this->buildCardsData($staffList);
    }

    /**
     * スタッフ一覧からQRコード付きカードコレクションを構築
     *
     * @param  Collection<int, Staff>  $staffList
     * @return Collection<int, array{staff: Staff, shift_url: string, qr_svg: string, qr_data_uri: string}>
     */
    public function buildCardsData(Collection $staffList, int $qrSize = 100): Collection
    {
        return $staffList->map(function (Staff $staff) use ($qrSize): array {
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
