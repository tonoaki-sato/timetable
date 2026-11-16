<?php

namespace Database\Seeders;

use App\Models\SiteBaseMap;
use App\Models\Timetable;
use Illuminate\Database\Seeder;

class SiteBaseMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timetables = Timetable::all();

        if ($timetables->isEmpty()) {
            return;
        }

        foreach ($timetables as $timetable) {
            SiteBaseMap::updateOrCreate(
                [
                    'timetable_id' => $timetable->id,
                    'cad_version' => '260813',
                ],
                [
                    'name' => '商店街平面図 260813版 A3 1/400',
                    'paper_size' => 'A3',
                    'scale_ratio' => 26.0000,
                    'width_meters' => 45.80,
                    'height_meters' => 32.40,
                    'svg_path' => 'maps/shopping_street_base_map_260813.svg',
                    'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
                    'viewbox' => '0 0 1191 842',
                    'is_active' => true,
                    'notes' => 'A3 1/400 CAD図面ベースマップ。商店街メイン通り東側・西側歩道、駐車場特設エリアを含む。',
                ]
            );
        }
    }
}
