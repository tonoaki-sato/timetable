<?php

namespace App\Http\Controllers;

use App\Actions\Lane\DownloadLanesPdfAction;
use App\Models\Lane;
use App\Models\Timetable;
use Illuminate\Http\Response;

/**
 * 担当班一覧のPDF帳票ダウンロードコントローラー
 */
class LanePrintController extends Controller
{
    public function __construct(
        protected DownloadLanesPdfAction $downloadLanesPdfAction
    ) {}

    /**
     * 指定運行日の担当班一覧PDFダウンロード
     */
    public function print(Timetable $timetable): Response
    {
        $this->authorize('viewAny', Lane::class);
        $this->authorize('view', $timetable);

        return $this->downloadLanesPdfAction->execute($timetable);
    }
}
