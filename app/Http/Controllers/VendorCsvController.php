<?php

namespace App\Http\Controllers;

use App\Actions\Vendor\ExportVendorsCsvAction;
use App\Actions\Vendor\ImportVendorsCsvAction;
use App\Http\Requests\ImportVendorsCsvRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorCsvController extends Controller
{
    public function __construct(
        protected ExportVendorsCsvAction $exportAction,
        protected ImportVendorsCsvAction $importAction,
    ) {}

    /**
     * 出展・出店者マスターのCSVエクスポート
     */
    public function export(): StreamedResponse
    {
        return $this->exportAction->execute();
    }

    /**
     * 出展・出店者マスターのCSVインポート
     */
    public function import(ImportVendorsCsvRequest $request): RedirectResponse
    {
        $summary = $this->importAction->execute($request->file('csv_file'));

        return redirect()->route('vendors.index')
            ->with('success', "CSVを取り込みました（新規登録 {$summary['created']}件 / 更新 {$summary['updated']}件）。");
    }
}
