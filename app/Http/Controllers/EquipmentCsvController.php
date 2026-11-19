<?php

namespace App\Http\Controllers;

use App\Actions\Equipment\ExportEquipmentsCsvAction;
use App\Actions\Equipment\ImportEquipmentsCsvAction;
use App\Http\Requests\ImportEquipmentsCsvRequest;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipmentCsvController extends Controller
{
    public function __construct(
        protected ExportEquipmentsCsvAction $exportAction,
        protected ImportEquipmentsCsvAction $importAction,
    ) {}

    /**
     * 資機材マスターのCSVエクスポート
     */
    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Equipment::class);

        return $this->exportAction->execute();
    }

    /**
     * 資機材マスターのCSVインポート
     */
    public function import(ImportEquipmentsCsvRequest $request): RedirectResponse
    {
        $this->authorize('create', Equipment::class);

        $summary = $this->importAction->execute($request->file('csv_file'), $request->user()?->id);

        return redirect()->route('equipments.index')
            ->with('success', "CSVを取り込みました（新規登録 {$summary['created']}件 / 更新 {$summary['updated']}件）。");
    }
}
