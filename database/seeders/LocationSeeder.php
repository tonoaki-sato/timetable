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

        $communityType = LocationType::where('name', 'community_space')->first();
        $parkingType = LocationType::where('name', 'parking')->first();
        $facilityType = LocationType::where('name', 'facility')->first();

        // 1. 番所（同日でも午前枠と夜間枠に分かれ、夜間枠は未申請の例）
        $bansho = Location::updateOrCreate(
            ['name' => '番所'],
            [
                'location_type_id' => $facilityType?->id ?? 1,
                'notes' => "【鍵情報】番所事務室キーボックス（暗証: 4920）に保管。\n【担当連絡先】番所当番携帯: 090-XXXX-1111\n【注意事項】12:00〜15:00は商店街通常利用のため実行委員会は使用禁止。",
                'is_all_day' => false,
                'display_order' => 10,
                'is_active' => true,
                'created_by' => $adminId,
            ]
        );

        $bansho->schedules()->delete();
        LocationSchedule::create([
            'location_id' => $bansho->id,
            'start_at' => Carbon::parse('2026-10-10 08:30:00'),
            'end_at' => Carbon::parse('2026-10-10 12:00:00'),
            'application_status' => 'approved',
            'title' => '午前受取・準備枠',
            'note' => '区役所許可受領済（許可番号: 26-区-081）',
        ]);
        LocationSchedule::create([
            'location_id' => $bansho->id,
            'start_at' => Carbon::parse('2026-10-10 15:00:00'),
            'end_at' => Carbon::parse('2026-10-10 21:00:00'),
            'application_status' => 'unapplied',
            'title' => '夜間集約・保管枠',
            'note' => '夜間延長の申請書が未提出。至急商店会へ提出要。',
        ]);

        // 2. 税務署前駐車場（未申請のため利用不可の例）
        $parking = Location::updateOrCreate(
            ['name' => '税務署前駐車場'],
            [
                'location_type_id' => $parkingType?->id ?? 2,
                'notes' => "【管理代行】○○パーキングサービス（03-XXXX-2222）\n前日正午から貸切予定だが申請書控え待ち。",
                'is_all_day' => false,
                'display_order' => 20,
                'is_active' => true,
                'created_by' => $adminId,
            ]
        );

        $parking->schedules()->delete();
        LocationSchedule::create([
            'location_id' => $parking->id,
            'start_at' => Carbon::parse('2026-10-10 12:00:00'),
            'end_at' => Carbon::parse('2026-10-12 21:00:00'),
            'application_status' => 'unapplied',
            'title' => '本祭前日〜終了後貸切枠',
            'note' => '駐車場管理者への専用申請書が未申請状態。',
        ]);

        // 3. 第1コミュニティスペース（申請不要・常時または承認済の例）
        $community = Location::updateOrCreate(
            ['name' => '第1コミュニティスペース'],
            [
                'location_type_id' => $communityType?->id ?? 1,
                'notes' => "実行委員会本部隣接の多目的会議室。\n冷暖房完備、Wi-Fi有。飲食可。",
                'is_all_day' => false,
                'display_order' => 30,
                'is_active' => true,
                'created_by' => $adminId,
            ]
        );

        $community->schedules()->delete();
        LocationSchedule::create([
            'location_id' => $community->id,
            'start_at' => Carbon::parse('2026-10-10 09:00:00'),
            'end_at' => Carbon::parse('2026-10-11 20:00:00'),
            'application_status' => 'not_required',
            'title' => '実行委員会本部・救護スペース',
            'note' => '自前拠点のため利用申請は不要。',
        ]);
    }
}
