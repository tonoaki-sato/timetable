<?php

namespace App\Actions\Equipment;

use App\Models\Equipment;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Support\CsvSanitizer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportEquipmentsCsvAction
{
    public function __construct(
        protected EquipmentRepositoryInterface $equipmentRepository
    ) {}

    /**
     * 資機材マスターのCSVエクスポート（UTF-8 with BOM）
     */
    public function execute(): StreamedResponse
    {
        $fileName = 'equipments_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $equipments = $this->equipmentRepository->getAllOrdered();

        return response()->stream(function () use ($equipments): void {
            $handle = fopen('php://output', 'w');

            // Excel文字化け防止用 UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                '備品名',
                '区分',
                '持ち出しルール',
                '基準数量',
                '単位',
                '概数フラグ',
                '定常保管場所',
                '備考',
            ]);

            foreach ($equipments as $equipment) {
                $typeLabel = Equipment::TYPES[$equipment->type] ?? $equipment->type;
                $ruleLabel = $equipment->checkout_rule_enum->label();

                fputcsv($handle, [
                    $equipment->id,
                    CsvSanitizer::sanitizeForExport($equipment->name),
                    CsvSanitizer::sanitizeForExport($typeLabel),
                    CsvSanitizer::sanitizeForExport($ruleLabel),
                    $equipment->quantity,
                    CsvSanitizer::sanitizeForExport($equipment->unit),
                    $equipment->is_approximate_quantity ? '1' : '0',
                    CsvSanitizer::sanitizeForExport($equipment->storage_location),
                    CsvSanitizer::sanitizeForExport($equipment->description),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
