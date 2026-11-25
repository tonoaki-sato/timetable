<?php

namespace App\Http\Controllers;

use App\Actions\ConeMap\DownloadConeMapPdfAction;
use App\Http\Requests\UpdateTimetableSiteBaseMapRequest;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Services\ConeMapService;
use App\Services\SiteMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConeMapController extends Controller
{
    public function __construct(
        protected ConeMapService $coneMapService,
        protected SiteMapService $siteMapService,
        protected DownloadConeMapPdfAction $downloadPdfAction,
    ) {}

    /**
     * カラーコーン・バリケード配置エディタ画面を表示する
     */
    public function editor(Timetable $timetable): View
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $editorData = $this->coneMapService->getEditorData($timetable);

        return view('cone_maps.editor', array_merge($editorData->toViewData(), [
            'timetable' => $timetable,
        ]));
    }

    /**
     * 運行情報で使用する白地図マスターを切り替える
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

            return redirect()->route('timetables.cone-maps.editor', $timetable)
                ->with('status', "使用白地図を「{$updated->name}」に切り替えました。");
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('timetables.cone-maps.editor', $timetable)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * カラーコーン・バリケード配置図を A3 1/400 ベクターPDF としてダウンロード出力する
     */
    public function exportPdf(Request $request, Timetable $timetable): Response
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $enhanced = $request->boolean('emphasis', true);
        $showBooths = $request->boolean('show_booths', false);

        return $this->downloadPdfAction->execute($timetable, $enhanced, $showBooths);
    }
}
