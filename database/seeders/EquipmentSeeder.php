<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Lane;
use App\Models\LogisticsLocation;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin?->id;

        $staffWitness = Staff::first();
        $staffWitnessId = $staffWitness?->id;

        $locBanshoUnderground = LogisticsLocation::where('name', '番所地下倉庫')->first();
        $locBanshoSpace = LogisticsLocation::where('name', '番所空きスペース')->first();
        $locTaxOfficeParking = LogisticsLocation::where('name', '税務署前駐車場')->first();
        $locKitagawaParking = LogisticsLocation::where('name', '北川駐車場')->first();

        $laneColorCone = Lane::where('name', 'カラーコーン')->first();
        $laneWater = Lane::where('name', '給水設備')->first();
        $laneGoza = Lane::where('name', 'ござ市')->first();
        $laneHonjin = Lane::where('name', '本陣')->first();

        // =========================================================================
        // 1. カラーコーン (例1: 実行委員会管理機材・分散夜間保管・パターンA)
        // =========================================================================
        $equipmentColorCone = Equipment::updateOrCreate(
            ['name' => 'カラーコーン（バーセット含む）'],
            [
                'type' => 'committee',
                'quantity' => 60,
                'unit' => '本',
                'storage_location' => '番所地下倉庫',
                'event_company_name' => null,
                'event_company_contact' => null,
                'lane_id' => $laneColorCone?->id,
                'description' => '路上規制用カラーコーンおよびコーンバー。前日に税務署前駐車場へ出し、1日目夜は2日目の設置位置に応じて税務署前駐車場と北川駐車場に分散保管。',
                'display_order' => 10,
                'created_by' => $adminId,
            ]
        );

        $colorConePlans = [
            [
                'target_date' => '2026-10-10',
                'day_phase' => 'pre_day',
                'action_type' => 'storage_out',
                'title' => '前日倉庫出し・中継移動',
                'quantity' => 60,
                'start_time' => '13:00',
                'end_time' => '14:30',
                'from_location' => '番所地下倉庫',
                'to_location' => '税務署前駐車場',
                'from_location_id' => $locBanshoUnderground?->id,
                'to_location_id' => $locTaxOfficeParking?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '台車を使用。税務署前駐車場の奥側にまとめて集約。',
                'display_order' => 1,
            ],
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_morning',
                'action_type' => 'setup',
                'title' => '1日目朝 会場各所へ設置',
                'quantity' => 60,
                'start_time' => '07:30',
                'end_time' => '09:00',
                'from_location' => '税務署前駐車場',
                'to_location' => '会場設置',
                'from_location_id' => $locTaxOfficeParking?->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '車両通行規制ライン（東西ゲートおよび各交差点）へ本設置。',
                'display_order' => 2,
            ],
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_evening',
                'action_type' => 'night_storage',
                'title' => '1日目夜 分散夜間保管（税務署前駐車場）',
                'quantity' => 35,
                'start_time' => '18:00',
                'end_time' => '19:00',
                'from_location' => '会場設置',
                'to_location' => '税務署前駐車場',
                'from_location_id' => null,
                'to_location_id' => $locTaxOfficeParking?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '西側・中央ブロック設置分のコーンを集約保管。',
                'display_order' => 3,
            ],
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_evening',
                'action_type' => 'night_storage',
                'title' => '1日目夜 分散夜間保管（北川駐車場）',
                'quantity' => 25,
                'start_time' => '18:00',
                'end_time' => '19:00',
                'from_location' => '会場設置',
                'to_location' => '北川駐車場',
                'from_location_id' => null,
                'to_location_id' => $locKitagawaParking?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '東側ブロック設置分のコーンを集約保管。',
                'display_order' => 4,
            ],
            [
                'target_date' => '2026-10-12',
                'day_phase' => 'day2_morning',
                'action_type' => 'setup',
                'title' => '2日目朝 会場各所へ再設置',
                'quantity' => 60,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'from_location' => '税務署前P・北川P',
                'to_location' => '会場設置',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '2日目の通行止め開始に合わせて各ポイントに配置。',
                'display_order' => 5,
            ],
            [
                'target_date' => '2026-10-12',
                'day_phase' => 'day2_evening',
                'action_type' => 'return_storage',
                'title' => '2日目夜 撤収・番所地下倉庫へ格納',
                'quantity' => 60,
                'start_time' => '18:30',
                'end_time' => '20:00',
                'from_location' => '会場設置',
                'to_location' => '番所地下倉庫',
                'from_location_id' => null,
                'to_location_id' => $locBanshoUnderground?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '清掃・員数確認後、番所地下倉庫へ元通り返還・格納。',
                'display_order' => 6,
            ],
        ];

        foreach ($colorConePlans as $plan) {
            EquipmentLogisticsPlan::updateOrCreate(
                [
                    'equipment_id' => $equipmentColorCone->id,
                    'title' => $plan['title'],
                ],
                $plan
            );
        }

        // =========================================================================
        // 2. 足踏み式給水設備 (例2: イベント会社レンタル・自前設置・パターンB)
        // =========================================================================
        $equipmentWater = Equipment::updateOrCreate(
            ['name' => '足踏み式給水設備'],
            [
                'type' => 'rental',
                'quantity' => 4,
                'unit' => '台',
                'storage_location' => null,
                'event_company_name' => '東京イベントサプライ株式会社',
                'event_company_contact' => '03-3456-7890 (配送担当直通: 090-1111-2222)',
                'lane_id' => $laneWater?->id,
                'description' => '前日午前中に番所で業者から受取立会後、税務署前駐車場が貸切になったら移動。1日目夜は近い駐車場へ分散夜間保管。2日目終了時に番所空きスペースへ集約して翌日業者回収待ち。',
                'display_order' => 20,
                'created_by' => $adminId,
            ]
        );

        $waterPlans = [
            [
                'target_date' => '2026-10-10',
                'day_phase' => 'pre_day',
                'action_type' => 'vendor_receive',
                'title' => '前日午前 業者受取・立会',
                'quantity' => 4,
                'start_time' => '10:30',
                'end_time' => '11:15',
                'from_location' => 'イベント会社車両',
                'to_location' => '番所空きスペース',
                'from_location_id' => null,
                'to_location_id' => $locBanshoSpace?->id,
                'operator_type' => 'committee',
                'witness_required' => true,
                'witness_staff_id' => $staffWitnessId,
                'contact_info' => 'ドライバー携帯: 090-1111-2222',
                'status' => 'planned',
                'notes' => 'ペダル動作・付属品ホース・給水タンク確認の上、受領サイン。',
                'display_order' => 1,
            ],
            [
                'target_date' => '2026-10-10',
                'day_phase' => 'pre_day',
                'action_type' => 'staging_move',
                'title' => '前日午後 税務署前駐車場貸切後に移動',
                'quantity' => 4,
                'start_time' => '13:00',
                'end_time' => '13:45',
                'from_location' => '番所空きスペース',
                'to_location' => '税務署前駐車場',
                'from_location_id' => $locBanshoSpace?->id,
                'to_location_id' => $locTaxOfficeParking?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '税務署前駐車場が12:00以降貸切状態になったことを確認後に移動。',
                'display_order' => 2,
            ],
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_morning',
                'action_type' => 'setup',
                'title' => '1日目朝 給水所各箇所へ設置',
                'quantity' => 4,
                'start_time' => '08:00',
                'end_time' => '09:30',
                'from_location' => '税務署前駐車場',
                'to_location' => '会場設置',
                'from_location_id' => $locTaxOfficeParking?->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '給水ポイント4箇所へ配置。水漏れ・ペダルテスト実施。',
                'display_order' => 3,
            ],
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_evening',
                'action_type' => 'night_storage',
                'title' => '1日目夜 分散夜間保管（税務署前駐車場）',
                'quantity' => 2,
                'start_time' => '17:30',
                'end_time' => '18:15',
                'from_location' => '会場設置',
                'to_location' => '税務署前駐車場',
                'from_location_id' => null,
                'to_location_id' => $locTaxOfficeParking?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '西側・本陣付近の2台を移動・夜間保管。',
                'display_order' => 4,
            ],
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_evening',
                'action_type' => 'night_storage',
                'title' => '1日目夜 分散夜間保管（北川駐車場）',
                'quantity' => 2,
                'start_time' => '17:30',
                'end_time' => '18:15',
                'from_location' => '会場設置',
                'to_location' => '北川駐車場',
                'from_location_id' => null,
                'to_location_id' => $locKitagawaParking?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '東側・キッズ村付近の2台を移動・夜間保管。',
                'display_order' => 5,
            ],
            [
                'target_date' => '2026-10-12',
                'day_phase' => 'day2_morning',
                'action_type' => 'setup',
                'title' => '2日目朝 給水所各箇所へ再設置',
                'quantity' => 4,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'from_location' => '税務署前P・北川P',
                'to_location' => '会場設置',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '各給水ポイントへ設置・注水。',
                'display_order' => 6,
            ],
            [
                'target_date' => '2026-10-12',
                'day_phase' => 'day2_evening',
                'action_type' => 'collection_staging',
                'title' => '2日目夜 番所空きスペースへ集約・保管',
                'quantity' => 4,
                'start_time' => '18:00',
                'end_time' => '19:00',
                'from_location' => '会場設置',
                'to_location' => '番所空きスペース',
                'from_location_id' => null,
                'to_location_id' => $locBanshoSpace?->id,
                'operator_type' => 'committee',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => null,
                'status' => 'planned',
                'notes' => '水を抜き、乾燥・拭き上げの上、番所空きスペースに施錠保管。翌日の業者回収を待つ。',
                'display_order' => 7,
            ],
            [
                'target_date' => '2026-10-13',
                'day_phase' => 'post_day',
                'action_type' => 'vendor_pickup',
                'title' => '翌日午前 業者回収・引渡立会',
                'quantity' => 4,
                'start_time' => '10:00',
                'end_time' => '10:45',
                'from_location' => '番所空きスペース',
                'to_location' => 'イベント会社車両',
                'from_location_id' => $locBanshoSpace?->id,
                'to_location_id' => null,
                'operator_type' => 'committee',
                'witness_required' => true,
                'witness_staff_id' => $staffWitnessId,
                'contact_info' => 'ドライバー携帯: 090-1111-2222',
                'status' => 'planned',
                'notes' => '業者トラック積み込み立ち会い、返却サイン。',
                'display_order' => 8,
            ],
        ];

        foreach ($waterPlans as $plan) {
            EquipmentLogisticsPlan::updateOrCreate(
                [
                    'equipment_id' => $equipmentWater->id,
                    'title' => $plan['title'],
                ],
                $plan
            );
        }

        // =========================================================================
        // 3. パイプテント・天幕・ウェイト (例3: イベント会社設営・解体型・パターンC)
        // =========================================================================
        $equipmentTent = Equipment::updateOrCreate(
            ['name' => 'パイプテント（2K×3K）・天幕・ウェイトセット'],
            [
                'type' => 'rental',
                'quantity' => 12,
                'unit' => '張',
                'storage_location' => null,
                'event_company_name' => '東京イベントサプライ株式会社',
                'event_company_contact' => '03-3456-7890 (現場責任者: 佐々木 090-3333-4444)',
                'lane_id' => $laneHonjin?->id,
                'description' => '本部・救護所・各ブース用テント。イベント会社が一任で搬入・設営および解体・搬出を実施。幹事はスケジュール把握のみ（立会不要）。',
                'display_order' => 30,
                'created_by' => $adminId,
            ]
        );

        $tentPlans = [
            [
                'target_date' => '2026-10-11',
                'day_phase' => 'day1_morning',
                'action_type' => 'vendor_setup',
                'title' => 'イベント会社 搬入・設営',
                'quantity' => 12,
                'start_time' => '06:30',
                'end_time' => '08:30',
                'from_location' => 'イベント会社車両',
                'to_location' => '会場各ブース',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'event_company',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => '現場責任者: 佐々木 090-3333-4444',
                'status' => 'planned',
                'notes' => '設営はイベント会社が実施。幹事は開始・完了状況を把握。',
                'display_order' => 1,
            ],
            [
                'target_date' => '2026-10-12',
                'day_phase' => 'day2_evening',
                'action_type' => 'vendor_pickup',
                'title' => 'イベント会社 解体・搬出',
                'quantity' => 12,
                'start_time' => '18:00',
                'end_time' => '20:30',
                'from_location' => '会場各ブース',
                'to_location' => 'イベント会社車両',
                'from_location_id' => null,
                'to_location_id' => null,
                'operator_type' => 'event_company',
                'witness_required' => false,
                'witness_staff_id' => null,
                'contact_info' => '現場責任者: 佐々木 090-3333-4444',
                'status' => 'planned',
                'notes' => '解体・搬出はイベント会社が実施。歩行者規制解除までにトラック完全退場。',
                'display_order' => 2,
            ],
        ];

        foreach ($tentPlans as $plan) {
            EquipmentLogisticsPlan::updateOrCreate(
                [
                    'equipment_id' => $equipmentTent->id,
                    'title' => $plan['title'],
                ],
                $plan
            );
        }

        // =========================================================================
        // 4. ござ市用テント (自前機材・パターンA)
        // =========================================================================
        Equipment::updateOrCreate(
            ['name' => 'ござ市用テント・台車'],
            [
                'type' => 'committee',
                'quantity' => 6,
                'unit' => '張',
                'storage_location' => '番所地下倉庫',
                'event_company_name' => null,
                'event_company_contact' => null,
                'lane_id' => $laneGoza?->id,
                'description' => 'ござ市エリアで使用する自前テントおよび敷物。番所地下倉庫保管。',
                'display_order' => 40,
                'created_by' => $adminId,
            ]
        );
    }
}

