<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffCsvController extends Controller
{
    public function __construct(
        protected StaffService $staffService
    ) {}

    /**
     * スタッフ名簿のCSVエクスポート（UTF-8 with BOM）
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Staff::class);

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
                'LINE表示名',
                '登録経路',
                '現場到着日時',
                '稼働状態',
                'マイシフトURL',
            ]);

            Staff::orderBy('id')->chunk(200, function ($staffList) use ($handle) {
                foreach ($staffList as $staff) {
                    $shiftUrl = route('staff.myshift', ['token' => $staff->access_token]);

                    fputcsv($handle, [
                        $staff->id,
                        $staff->name,
                        $staff->kana,
                        $staff->affiliation ?? '',
                        $staff->line_display_name ?? '',
                        $staff->registered_via === 'self' ? 'LINEセルフ' : '本部登録',
                        $staff->checked_in_at?->format('Y-m-d H:i:s') ?? '',
                        $staff->is_active ? '稼働可能' : '停止中',
                        $shiftUrl,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * CSVファイルからの一括スタッフインポート
     */
    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Staff::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $uploadedFile = $request->file('file');
        if (! $uploadedFile) {
            return back()->withErrors(['file' => 'ファイルのアップロードに失敗しました。']);
        }

        $content = file_get_contents($uploadedFile->getRealPath());
        if ($content === false) {
            return back()->withErrors(['file' => 'ファイルを読み込めませんでした。']);
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
            // 形式A (エクスポート同等): [0: ID, 1: 氏名, 2: フリガナ, 3: 所属, 4: LINE表示名, ...]
            // 形式B (シンプル手動作成): [0: 氏名, 1: フリガナ, 2: 所属, 3: LINE表示名]
            if (is_numeric($row[0] ?? null) && count($row) >= 4) {
                // 形式A
                $name = trim($row[1] ?? '');
                $kana = trim($row[2] ?? '');
                $affiliation = trim($row[3] ?? '');
                $lineDisplayName = trim($row[4] ?? '');
            } else {
                // 形式B
                $name = trim($row[0] ?? '');
                $kana = trim($row[1] ?? '');
                $affiliation = trim($row[2] ?? '');
                $lineDisplayName = trim($row[3] ?? '');
            }

            if ($name === '') {
                continue;
            }

            // カナが空の場合は氏名を仮設定
            if ($kana === '') {
                $kana = $name;
            }

            // 既存スタッフ判定（氏名 + フリガナ一致）
            $existing = Staff::where('name', $name)
                ->where('kana', $kana)
                ->first();

            if ($existing) {
                $updateData = [];
                if ($affiliation !== '') {
                    $updateData['affiliation'] = $affiliation;
                }
                if ($lineDisplayName !== '') {
                    $updateData['line_display_name'] = $lineDisplayName;
                }

                if (! empty($updateData)) {
                    $existing->update($updateData);
                    $updatedCount++;
                }
            } else {
                Staff::create([
                    'name' => $name,
                    'kana' => $kana,
                    'affiliation' => $affiliation !== '' ? $affiliation : null,
                    'line_display_name' => $lineDisplayName !== '' ? $lineDisplayName : null,
                    'registered_via' => 'admin',
                    'is_active' => true,
                ]);
                $createdCount++;
            }
        }

        fclose($tempFile);

        return redirect()->route('staff.index')
            ->with('success', "名簿CSVの取込が完了しました。（新規登録: {$createdCount}名、情報更新: {$updatedCount}名）");
    }
}
