<?php

namespace App\Http\Controllers;

use App\Actions\Staff\ExportStaffCsvAction;
use App\Models\Staff;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffCsvController extends Controller
{
    public function __construct(
        protected ExportStaffCsvAction $exportAction
    ) {}

    /**
     * スタッフ名簿のCSVエクスポート（UTF-8 with BOM）
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Staff::class);

        return $this->exportAction->execute();
    }
}
