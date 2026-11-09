<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $adminId = $admin?->id;

        // 立会スタッフ（佐藤 外亮）
        $witnessStaff = Staff::where('name', '佐藤 外亮')->first();

        $timetableDay1 = Timetable::where('target_date', '2026-10-11')->first() ?? Timetable::first();
        $timetableDay2 = Timetable::where('target_date', '2026-10-12')->first() ?? $timetableDay1;

        // 関連拠点
        $banshoSpace1 = Location::where('timetable_id', $timetableDay1?->id)->where('name', '番所前スペース')->first();
        $taxOfficeParking1 = Location::where('timetable_id', $timetableDay1?->id)->where('name', '税務署前駐車場')->first();
        $banshoSpace2 = Location::where('timetable_id', $timetableDay2?->id)->where('name', '番所前スペース')->first() ?? $banshoSpace1;

        // 1. 機材: カラーコーン
        Equipment::updateOrCreate(
            ['name' => 'カラーコーン'],
            [
                'type' => 'committee',
                'checkout_rule' => 'permission_required',
                'quantity' => 100,
                'is_approximate_quantity' => true,
                'unit' => '個',
                'storage_location' => '番所地下倉庫',
                'description' => null,
                'display_order' => 10,
                'created_by' => $adminId,
            ]
        );

        // 2. 機材: 足踏み式給水設備
        $equipment = Equipment::updateOrCreate(
            ['name' => '足踏み式給水設備'],
            [
                'type' => 'rental',
                'checkout_rule' => 'leader_only',
                'quantity' => 5,
                'is_approximate_quantity' => false,
                'unit' => '台',
                'storage_location' => null,
                'description' => '給水用と排水用でポリタンク（20リットル）が４個付属',
                'display_order' => 20,
                'created_by' => $adminId,
            ]
        );

        // 2. 搬出入計画ステップ（7ステップ）
        $plans = [
            [
                'timetable_id' => $timetableDay1?->id,
                'target_date' => '2026-10-10',
                'day_phase' => 'pre_day',
                'action_type' => 'vendor_receive',
                'title' => '業者納品受取・検品立会',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '番所前スペース',
                'to_location' => '番所前スペース',
                'from_location_id' => $banshoSpace1?->id,
                'to_location_id' => $banshoSpace1?->id,
                'operator_type' => 'committee',
                'witness_required' => true,
                'witness_staff_id' => $witnessStaff?->id,
                'status' => 'planned',
                'notes' => 'ペダル動作・付属品員数等を確認',
                'display_order' => 10,
            ],
            [
                'timetable_id' => $timetableDay1?->id,
                'target_date' => null,
                'day_phase' => 'pre_day',
                'action_type' => 'staging_move',
                'title' => '貸切駐車場へ中継移動',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '番所前スペース',
                'to_location' => '税務署前駐車場',
                'from_location_id' => $banshoSpace1?->id,
                'to_location_id' => $taxOfficeParking1?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'status' => 'planned',
                'notes' => '番所前スペースが翌日ブース化するため、12:00以降駐車場へ移動',
                'display_order' => 20,
            ],
            [
                'timetable_id' => $timetableDay1?->id,
                'target_date' => null,
                'day_phase' => 'day1_morning',
                'action_type' => 'setup',
                'title' => '1日目 会場設置・通水テスト',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '税務署前駐車場',
                'to_location' => '設置点各所',
                'from_location_id' => $taxOfficeParking1?->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'status' => 'planned',
                'notes' => '会場各ポイントへ配備し、水漏れ・ペダル動作確認を実施',
                'display_order' => 30,
            ],
            [
                'timetable_id' => $timetableDay1?->id,
                'target_date' => null,
                'day_phase' => 'day1_evening',
                'action_type' => 'night_storage',
                'title' => '1日目 夜間保管移動',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '設置点各所',
                'to_location' => '最寄り拠点',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'status' => 'planned',
                'notes' => '2日目の再設置に近い近隣駐車場へ夜間保管',
                'display_order' => 40,
            ],
            [
                'timetable_id' => $timetableDay2?->id,
                'target_date' => null,
                'day_phase' => 'day2_morning',
                'action_type' => 'setup',
                'title' => '2日目 会場再設置・注水',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '最寄り拠点',
                'to_location' => '設置点各所',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'status' => 'planned',
                'notes' => '各ポイントへ再配備し、注水・稼働準備',
                'display_order' => 50,
            ],
            [
                'timetable_id' => $timetableDay2?->id,
                'target_date' => null,
                'day_phase' => 'day2_evening',
                'action_type' => 'collection_staging',
                'title' => '番所前回収待ち集約',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '設置点各所',
                'to_location' => '番所前スペース',
                'from_location_id' => null,
                'to_location_id' => $banshoSpace2?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'status' => 'planned',
                'notes' => 'イベントブース終了後の番所前スペースへ集約',
                'display_order' => 60,
            ],
            [
                'timetable_id' => $timetableDay2?->id,
                'target_date' => '2026-10-13',
                'day_phase' => 'post_day',
                'action_type' => 'vendor_pickup',
                'title' => '業者回収・引渡立会',
                'quantity' => 5,
                'quantity_note' => null,
                'start_time' => null,
                'end_time' => null,
                'from_location' => '番所前スペース',
                'to_location' => '（未定）',
                'from_location_id' => $banshoSpace2?->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => true,
                'witness_staff_id' => $witnessStaff?->id,
                'status' => 'planned',
                'notes' => '業者トラック積み込みに立ち会い、員数確認の上で返却サイン（立会必須）',
                'display_order' => 70,
            ],
        ];

        // 既存の計画ステップを再作成
        $equipment->plans()->delete();

        foreach ($plans as $planData) {
            $equipment->plans()->create($planData);
        }
    }
}
