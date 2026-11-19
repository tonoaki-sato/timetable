<?php

namespace App\Actions\Vendor;

use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Support\CsvSanitizer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportVendorsCsvAction
{
    public function __construct(
        protected VendorRepositoryInterface $vendorRepository
    ) {}

    /**
     * 出展・出店者マスターのCSVエクスポート（UTF-8 with BOM）
     */
    public function execute(): StreamedResponse
    {
        $fileName = 'vendors_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $vendors = $this->vendorRepository->getAll();

        return response()->stream(function () use ($vendors): void {
            $handle = fopen('php://output', 'w');

            // Excel文字化け防止用 UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                '出展・出店者名',
                '窓口担当者',
                '連絡先',
                '特記事項・備考',
            ]);

            foreach ($vendors as $vendor) {
                fputcsv($handle, [
                    $vendor->id,
                    CsvSanitizer::sanitizeForExport($vendor->name),
                    CsvSanitizer::sanitizeForExport($vendor->contact_person),
                    CsvSanitizer::sanitizeForExport($vendor->contact),
                    CsvSanitizer::sanitizeForExport($vendor->notes),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
