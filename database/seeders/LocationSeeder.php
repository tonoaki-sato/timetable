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
                'notes' => "【鍵情報】番所事務室キーボックス（暗証: 4920）に保管。\n実行委員会保有の機材（カラーコーン、ござ市用テント等）の常時保管場所。",
                'display_order' => 10,
                'schedules' => [],
            ],
            [
                'name' => '番所前スペース',
                'type_id' => $openSpaceType?->id,
                'is_all_day' => true,
                'notes' => '番所前空きスペース。前日の業者受取時の一時待機場所、および2日目終了後の業者回収待ち保管場所。',
                'display_order' => 20,
                'schedules' => [],
            ],
            [
                'name' => 'コミュニティルーム',
                'type_id' => $communityType?->id,
                'is_all_day' => true,
                'notes' => "室内保管用機材（音響機器、看板類、書類・備品）の常時保管場所。\n自前拠点のため利用申請は不要。",
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
                        'application_status' => 'approved',
                        'title' => '本祭前日〜終了後貸切枠',
                        'note' => '前日正午から貸切状態。許可取得済。',
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
                        'application_status' => 'approved',
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
                        'application_status' => 'approved',
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
                        'application_status' => 'approved',
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
                        'application_status' => $sch['application_status'],
                        'title' => $sch['title'],
                        'note' => $sch['note'],
                    ]);
                }
            }
        }
    }
}
