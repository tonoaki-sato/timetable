<?php

namespace App\Http\Controllers;

use App\Actions\Staff\ExportStaffCsvAction;
use App\Actions\Staff\ImportStaffCsvAction;
use App\Http\Requests\ImportStaffCsvRequest;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffCsvController extends Controller
{
    public function __construct(
        protected ExportStaffCsvAction $exportAction,
        protected ImportStaffCsvAction $importAction
    ) {}

    /**
     * スタッフ名簿のCSVエクスポート（UTF-8 with BOM）
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Staff::class);

        return $this->exportAction->execute();
    }

    /**
     * CSVファイルからの一括スタッフインポート
     */
    public function import(ImportStaffCsvRequest $request): RedirectResponse
    {
        $this->authorize('create', Staff::class);

        $uploadedFile = $request->file('file');
        if (! $uploadedFile) {
            return back()->withErrors(['file' => 'ファイルのアップロードに失敗しました。']);
        }

        $result = $this->importAction->execute($uploadedFile);

        if (! $result['success']) {
            return back()->withErrors(['file' => $result['error'] ?? 'CSVファイルの解析に失敗しました。']);
        }

        return redirect()->route('staff.index')
            ->with('success', "名簿CSVの取込が完了しました。（新規登録: {$result['created_count']}名、情報更新: {$result['updated_count']}名）");
    }
}
