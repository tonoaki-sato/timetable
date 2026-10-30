<?php

namespace Database\Seeders;

use App\Models\LogisticsLocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class LogisticsLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin?->id;

        $locations = [
            [
                'name' => '番所地下倉庫',
                'type' => 'facility',
                'is_all_day' => true,
                'available_from' => null,
                'available_to' => null,
                'description' => '実行委員会保有の機材（カラーコーン、ござ市用テント等）の常時保管場所。鍵は番所事務室保管。',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => '番所空きスペース',
                'type' => 'open_space',
                'is_all_day' => true,
                'available_from' => null,
                'available_to' => null,
                'description' => '番所横の屋根付き空きスペース。前日の業者受取時の一時待機場所、および2日目終了後の業者回収待ち保管場所。',
                'display_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'コミュニティルーム',
                'type' => 'facility',
                'is_all_day' => true,
                'available_from' => null,
                'available_to' => null,
                'description' => '室内保管用機材（音響機器、看板類、書類・備品）の常時保管場所。',
                'display_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => '税務署前駐車場',
                'type' => 'parking',
                'is_all_day' => false,
                'available_from' => '2026-10-10 12:00:00',
                'available_to' => '2026-10-12 21:00:00',
                'description' => '前日正午から貸切状態。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。',
                'display_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => '北川駐車場',
                'type' => 'parking',
                'is_all_day' => false,
                'available_from' => '2026-10-10 13:00:00',
                'available_to' => '2026-10-12 20:30:00',
                'description' => '近隣貸切駐車場。1日目夜の夜間分散保管場所（設置位置の近い機材）として利用。',
                'display_order' => 50,
                'is_active' => true,
            ],
        ];

        foreach ($locations as $loc) {
            LogisticsLocation::updateOrCreate(
                ['name' => $loc['name']],
                array_merge($loc, ['created_by' => $adminId])
            );
        }
    }
}

