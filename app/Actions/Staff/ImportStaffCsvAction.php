<?php

namespace App\Actions\Staff;

use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Http\UploadedFile;

class ImportStaffCsvAction
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository
    ) {}

    /**
     * CSVファイルからスタッフを一括インポート・更新
     *
     * @return array{success: bool, created_count: int, updated_count: int, error?: string}
     */
    public function execute(UploadedFile $uploadedFile): array
    {
        $content = file_get_contents($uploadedFile->getRealPath());
        if ($content === false) {
            return [
                'success' => false,
                'created_count' => 0,
                'updated_count' => 0,
                'error' => 'ファイルを読み込めませんでした。',
            ];
        }

        // 文字コード判定とUTF-8変換（Shift-JIS / CP932 / UTF-8）
        $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-win', 'SJIS', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // BOMの除去
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // 一時ファイルに書き出して fgetcsv で解析
        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        rewind($tempFile);

        $createdCount = 0;
        $updatedCount = 0;
        $rowNumber = 0;

        while (($row = fgetcsv($tempFile)) !== false) {
            $rowNumber++;

            // 空行スキップ
            if (empty(array_filter($row))) {
                continue;
            }

            // 1行目がヘッダー（「氏名」または「name」等を含む）の場合はスキップ
            if ($rowNumber === 1 && (str_contains($row[0] ?? '', '氏名') || str_contains($row[0] ?? '', 'ID') || str_contains($row[0] ?? '', 'name'))) {
                continue;
            }

            // CSV列定義:
            // 形式A (エクスポート同等): [0: ID, 1: 氏名, 2: フリガナ, 3: 所属, ...]
            // 形式B (シンプル手動作成): [0: 氏名, 1: フリガナ, 2: 所属]
            if (is_numeric($row[0] ?? null) && count($row) >= 4) {
                // 形式A
                $name = mb_substr(trim($row[1] ?? ''), 0, 255);
                $kana = mb_substr(trim($row[2] ?? ''), 0, 255);
                $affiliation = mb_substr(trim($row[3] ?? ''), 0, 100);
            } else {
                // 形式B
                $name = mb_substr(trim($row[0] ?? ''), 0, 255);
                $kana = mb_substr(trim($row[1] ?? ''), 0, 255);
                $affiliation = mb_substr(trim($row[2] ?? ''), 0, 100);
            }

            if ($name === '') {
                continue;
            }

            // カナが空の場合は氏名を仮設定
            if ($kana === '') {
                $kana = $name;
            }

            // 既存スタッフ判定（氏名 + フリガナ一致）
            $existing = $this->staffRepository->findFirstByNameAndKana($name, $kana);

            if ($existing) {
                $updateData = [];
                if ($affiliation !== '') {
                    $updateData['affiliation'] = $affiliation;
                }

                if (! empty($updateData)) {
                    $this->staffRepository->update($existing, $updateData);
                    $updatedCount++;
                }
            } else {
                $this->staffRepository->create([
                    'name' => $name,
                    'kana' => $kana,
                    'affiliation' => $affiliation !== '' ? $affiliation : null,
                    'registered_via' => 'admin',
                    'is_active' => true,
                ]);
                $createdCount++;
            }
        }

        fclose($tempFile);

        return [
            'success' => true,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
        ];
    }
}
