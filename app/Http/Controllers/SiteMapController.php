<?php

namespace App\Http\Controllers;

use App\Actions\SiteMap\DownloadSiteMapPdfAction;
use App\Http\Requests\UpdateTimetableSiteBaseMapRequest;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Services\SiteMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteMapController extends Controller
{
    public function __construct(
        protected SiteMapService $siteMapService,
        protected DownloadSiteMapPdfAction $downloadPdfAction,
    ) {}

    /**
     * 白地図マッピングエディタ画面を表示する
     */
    public function editor(Request $request, Timetable $timetable): View
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $editorData = $this->siteMapService->getEditorData($timetable);

        return view('site_layouts.editor', array_merge($editorData, [
            'timetable' => $timetable,
        ]));
    }

    /**
     * 運行情報で使用する白地図マスターを切り替える API / アクション
     */
    public function updateBaseMap(UpdateTimetableSiteBaseMapRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        try {
            $updated = $this->siteMapService->switchTimetableBaseMapById(
                $timetable,
                (int) $request->validated('site_base_map_id')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "使用白地図を「{$updated->name}」に切り替えました。",
                    'site_base_map' => $updated,
                ]);
            }

            return redirect()->route('timetables.site-maps.editor', $timetable)
                ->with('status', "使用白地図を「{$updated->name}」に切り替えました。");
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('timetables.site-maps.editor', $timetable)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 編集済みの白地図配置を A3 1/400 ベクターPDFとしてダウンロード出力する
     */
    public function exportPdf(Timetable $timetable): Response
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        return $this->downloadPdfAction->execute($timetable);
    }
}
