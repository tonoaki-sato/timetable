<?php

namespace App\Actions\Staff;

use App\Models\Staff;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportStaffCsvAction
{
    /**
     * スタッフ名簿のCSVエクスポート（UTF-8 with BOM）StreamedResponseを生成
     */
    public function execute(): StreamedResponse
    {
        $fileName = 'staff_list_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // Excel文字化け防止用 UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // CSVヘッダー行
            fputcsv($handle, [
                'ID',
                '氏名',
                'フリガナ',
                '所属',
                '登録経路',
                '現場到着日時',
                '稼働状態',
                'マイシフトURL',
            ]);

            Staff::orderBy('id')->chunk(200, function ($staffList) use ($handle) {
                $sanitize = function (?string $value): string {
                    if ($value === null || $value === '') {
                        return '';
                    }
                    if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                        return "'".$value;
                    }

                    return $value;
                };

                foreach ($staffList as $staff) {
                    $shiftUrl = route('staff.myshift', ['token' => $staff->access_token]);

                    fputcsv($handle, [
                        $staff->id,
                        $sanitize($staff->name),
                        $sanitize($staff->kana),
                        $sanitize($staff->affiliation),
                        $staff->registered_via === 'self' ? 'セルフ登録' : '本部登録',
                        $staff->checked_in_at?->format('Y-m-d H:i:s') ?? '',
                        $staff->is_active ? '稼働可能' : '停止中',
                        $shiftUrl,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
