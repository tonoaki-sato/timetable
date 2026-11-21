<?php

namespace Database\Seeders;

use App\Models\LayoutArea;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LayoutAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::all()->keyBy('name');

        $areas = [
            [
                'name' => '北川駐車場内',
                'location_id' => $locations->get('長島駐車場（旧北川駐車場）')?->id,
                'notes' => null,
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'S&S前駐車場内',
                'location_id' => $locations->get('S&S駐車場')?->id,
                'notes' => null,
                'display_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => '税務署前駐車場内',
                'location_id' => $locations->get('税務署前駐車場')?->id,
                'notes' => null,
                'display_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'キッズ村',
                'location_id' => null,
                'notes' => null,
                'display_order' => 35,
                'is_active' => true,
            ],
            [
                'name' => 'メイン通りA列（東側歩道）',
                'location_id' => null,
                'notes' => null,
                'display_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'メイン通りB列（西側歩道）',
                'location_id' => null,
                'notes' => null,
                'display_order' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($areas as $area) {
            LayoutArea::firstOrCreate(
                ['name' => $area['name']],
                [
                    'location_id' => $area['location_id'],
                    'notes' => $area['notes'],
                    'display_order' => $area['display_order'],
                    'is_active' => $area['is_active'],
                ]
            );
        }
    }
}
