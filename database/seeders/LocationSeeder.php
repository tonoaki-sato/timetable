<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $adminId = $admin?->id;

        $storageType = LocationType::where('name', 'storage')->first();
        $openSpaceType = LocationType::where('name', 'open_space')->first();
        $communityType = LocationType::where('name', 'community_space')->first();
        $parkingType = LocationType::where('name', 'parking')->first();

        $locations = [
            [
                'name' => '番所地下倉庫',
                'type_id' => $storageType?->id,
                'is_all_day' => true,
                'notes' => "【鍵情報】番所事務室キーボックス（暗証: 4920）に保管。\n大型自前機材（カラーコーン、ござ市用テント、大型台車等）の常時保管場所。",
                'display_order' => 10,
                'schedules' => [],
            ],
            [
                'name' => '番所倉庫',
                'type_id' => $storageType?->id,
                'is_all_day' => true,
                'notes' => '地下倉庫とは別の番所倉庫。交通整理用LED誘導棒などの常時保管場所。',
                'display_order' => 15,
                'schedules' => [],
            ],
            [
                'name' => '番所前スペース',
                'type_id' => $openSpaceType?->id,
                'is_all_day' => false,
                'notes' => "番所空きスペース。前日の倉庫出し・業者納品時の一時集約場所、および2日目終了後〜翌日の倉庫格納前・業者回収待ち集約場所。\n※1日目・2日目の通行止め（まつり開催）期間中はイベントブースとして使用されるため資材置き場として利用不可。",
                'display_order' => 20,
                'schedules' => [
                    [
                        'start_at' => '2026-10-10 08:00:00',
                        'end_at' => '2026-10-11 08:30:00',
                        'availability_status' => 'available',
                        'title' => '開催前 倉庫出し・荷捌き一時集約枠',
                        'note' => '1日目イベントブース設営前まで利用可能。',
                    ],
                    [
                        'start_at' => '2026-10-12 18:00:00',
                        'end_at' => '2026-10-13 17:00:00',
                        'availability_status' => 'available',
                        'title' => '終了後 撤収集約・翌日業者回収待ち枠',
                        'note' => '2日目イベントブース撤収後〜翌日業者回収まで利用可能。',
                    ],
                ],
            ],
            [
                'name' => 'コミュニティルーム',
                'type_id' => $communityType?->id,
                'is_all_day' => true,
                'notes' => "主に消耗品および看板（ステカン）の常時保管場所。\n本祭期間中の盗難のおそれがある資材や荒天時退避資材の夜間保管先としても活用。",
                'display_order' => 30,
                'schedules' => [],
            ],
            [
                'name' => '税務署前駐車場',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => "【管理代行】○○パーキングサービス（03-XXXX-2222）\nイベントブース。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。",
                'display_order' => 40,
                'schedules' => [
                    [
                        'start_at' => '2026-10-10 12:00:00',
                        'end_at' => '2026-10-12 21:00:00',
                        'availability_status' => 'available',
                        'title' => '本祭前日〜終了後貸切枠',
                        'note' => '前日正午から貸切状態。利用確保済。',
                    ],
                ],
            ],
            [
                'name' => '長島駐車場（旧北川駐車場）',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => '近隣貸切駐車場。企業ブース、および1日目夜の夜間分散保管場所として利用。',
                'display_order' => 50,
                'schedules' => [
                    [
                        'start_at' => '2026-10-10 13:00:00',
                        'end_at' => '2026-10-12 20:30:00',
                        'availability_status' => 'available',
                        'title' => '企業ブース・夜間分散保管枠',
                        'note' => '利用契約締結済。',
                    ],
                ],
            ],
            [
                'name' => 'S&S駐車場',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => '本陣待機場所。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。',
                'display_order' => 60,
                'schedules' => [
                    [
                        'start_at' => '2026-10-10 09:00:00',
                        'end_at' => '2026-10-12 21:00:00',
                        'availability_status' => 'available',
                        'title' => '本陣待機・中継枠',
                        'note' => '関係車両専用。',
                    ],
                ],
            ],
            [
                'name' => '横浜銀行駐車場',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => '近隣住民用の代替駐車場として利用。',
                'display_order' => 70,
                'schedules' => [
                    [
                        'start_at' => '2026-10-10 09:00:00',
                        'end_at' => '2026-10-12 20:00:00',
                        'availability_status' => 'available',
                        'title' => '住民代替駐車枠',
                        'note' => '銀行側と覚書締結済。',
                    ],
                ],
            ],
        ];

        foreach ($locations as $locData) {
            $location = Location::updateOrCreate(
                ['name' => $locData['name']],
                [
                    'location_type_id' => $locData['type_id'],
                    'notes' => $locData['notes'],
                    'is_all_day' => $locData['is_all_day'],
                    'display_order' => $locData['display_order'],
                    'is_active' => true,
                    'created_by' => $adminId,
                ]
            );

            // スケジュール設定
            if (! empty($locData['schedules'])) {
                $location->schedules()->delete();
                foreach ($locData['schedules'] as $sch) {
                    LocationSchedule::create([
                        'location_id' => $location->id,
                        'start_at' => Carbon::parse($sch['start_at']),
                        'end_at' => Carbon::parse($sch['end_at']),
                        'availability_status' => $sch['availability_status'],
                        'title' => $sch['title'],
                        'note' => $sch['note'],
                    ]);
                }
            }
        }
    }
}
