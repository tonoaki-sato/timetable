<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SiteMapController extends Controller
{
    /**
     * 白地図マッピングエディタ画面を表示する
     */
    public function editor(Request $request, Timetable $timetable): View
    {
        // 運行情報に紐づくアクティブな白地図を取得、存在しない場合はデフォルト白地図を準備
        $siteBaseMap = $timetable->siteBaseMaps()
            ->where('is_active', true)
            ->first();

        if (! $siteBaseMap) {
            $siteBaseMap = $timetable->siteBaseMaps()->create([
                'name' => '商店街平面図 260813版 A3 1/400',
                'cad_version' => '260813',
                'paper_size' => 'A3',
                'scale_ratio' => 26.000,
                'width_meters' => 45.8,
                'height_meters' => 32.4,
                'svg_path' => 'maps/shopping_street_base_map_260813.svg',
                'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
                'viewbox' => '0 0 1191 842',
                'is_active' => true,
            ]);
        }

        return view('site_layouts.editor', [
            'timetable' => $timetable,
            'siteBaseMap' => $siteBaseMap,
        ]);
    }
}
