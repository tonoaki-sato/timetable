<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class CsvSanitizer
{
    /**
     * CSV数式インジェクション対策用サニタイズ（エクスポート時）
     */
    public static function sanitizeForExport(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * アップロードCSVファイルをUTF-8として読み取り行配列へ変換
     *
     * @return array<int, array<int, string>>
     */
    public static function parseUploadedCsv(UploadedFile $file): array
    {
        $raw = (string) file_get_contents($file->getRealPath());

        // UTF-8 BOM 除去
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        // Shift-JIS等の非UTF-8ファイルを自動変換
        if (! mb_check_encoding($raw, 'UTF-8')) {
            $converted = mb_convert_encoding($raw, 'UTF-8', 'SJIS-win,CP932,EUC-JP,UTF-8');
            if (is_string($converted)) {
                $raw = $converted;
            }
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [];
        }

        fwrite($stream, $raw);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $normalized = array_map(
                fn (mixed $cell): string => trim((string) ($cell ?? '')),
                $row
            );

            // 全セル空行はスキップ
            if (implode('', $normalized) === '') {
                continue;
            }

            $rows[] = $normalized;
        }

        fclose($stream);

        return $rows;
    }
}
