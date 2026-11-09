<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\LocationType;
use App\Models\Timetable;
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
                'is_all_day' => false,
                'notes' => '大型自前機材（カラーコーン、ござ市用テント、大型台車等）の常時保管場所。',
                'display_order' => 10,
                'schedules' => [],
            ],
            [
                'name' => '番所倉庫',
                'type_id' => $storageType?->id,
                'is_all_day' => false,
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
                'schedules' => [],
            ],
            [
                'name' => 'コミュニティルーム',
                'type_id' => $communityType?->id,
                'is_all_day' => false,
                'notes' => "主に消耗品および看板（ステカン）の常時保管場所。\n本祭期間中の盗難のおそれがある資材や荒天時退避資材の夜間保管先としても活用。",
                'display_order' => 30,
                'schedules' => [],
            ],
            [
                'name' => '税務署前駐車場',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => 'イベントブース。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。',
                'display_order' => 40,
                'schedules' => [],
            ],
            [
                'name' => '長島駐車場（旧北川駐車場）',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => '近隣貸切駐車場。企業ブース、および1日目夜の夜間分散保管場所として利用。',
                'display_order' => 50,
                'schedules' => [],
            ],
            [
                'name' => 'S&S駐車場',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => '本陣待機場所。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。',
                'display_order' => 60,
                'schedules' => [],
            ],
            [
                'name' => '横浜銀行駐車場',
                'type_id' => $parkingType?->id,
                'is_all_day' => false,
                'notes' => '近隣住民用の代替駐車場として利用。',
                'display_order' => 70,
                'schedules' => [],
            ],
        ];

        $timetables = Timetable::all();
        if ($timetables->isEmpty()) {
            $timetables = collect([Timetable::factory()->create([
                'name' => '本番日',
                'target_date' => '2026-10-11',
            ])]);
        }

        $day4Orders = [
            'コミュニティルーム' => 10,
            '番所倉庫' => 20,
            '番所地下倉庫' => 30,
            '番所前スペース' => 40,
            '税務署前駐車場' => 50,
            '長島駐車場（旧北川駐車場）' => 60,
            'S&S駐車場' => 70,
            '横浜銀行駐車場' => 80,
        ];

        foreach ($timetables as $timetable) {
            $isDay4 = str_contains($timetable->name, '第35回');

            foreach ($locations as $locData) {
                $displayOrder = ($isDay4 && isset($day4Orders[$locData['name']]))
                    ? $day4Orders[$locData['name']]
                    : $locData['display_order'];

                $location = Location::updateOrCreate(
                    [
                        'timetable_id' => $timetable->id,
                        'name' => $locData['name'],
                    ],
                    [
                        'location_type_id' => $locData['type_id'],
                        'notes' => $locData['notes'],
                        'is_all_day' => $locData['is_all_day'],
                        'display_order' => $displayOrder,
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
}
