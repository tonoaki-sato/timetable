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
        $baseMap = SiteBaseMap::updateOrCreate(
            [
                'cad_version' => '260813',
                'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            ],
            [
                'name' => '商店街平面図 260813版 A3 1/400',
                'paper_size' => 'A3',
                'scale_ratio' => 7.0866,
                'width_meters' => 118.80,
                'height_meters' => 168.00,
                'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
                'viewbox' => '0 0 842 1191',
                'is_default' => true,
                'is_active' => true,
                'notes' => 'A3 1/400 CAD図面ベースマップ。商店街メイン通り東側・西側歩道、駐車場特設エリアを含む。',
            ]
        );

        Timetable::whereNull('site_base_map_id')->update([
            'site_base_map_id' => $baseMap->id,
        ]);
    }
}
