<?php

namespace Database\Seeders;

use App\Models\LocationType;
use Illuminate\Database\Seeder;

class LocationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'community_space',
                'label' => 'コミュニティスペース',
                'description' => '地域住民・実行委員会の打合せや待機、休憩等に使用する屋内スペース。',
                'color' => 'indigo',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'parking',
                'label' => '駐車場',
                'description' => '搬出入車両の一時駐車や機材中継、夜間保管等に利用する屋外駐車場。',
                'color' => 'amber',
                'display_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'facility',
                'label' => '番所・倉庫',
                'description' => '常設の番所事務室や常備機材の保管倉庫。',
                'color' => 'emerald',
                'display_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'open_space',
                'label' => '空きスペース・広場',
                'description' => '屋外の軒下や広場スペース。一時待機場所等。',
                'color' => 'sky',
                'display_order' => 40,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LocationType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
