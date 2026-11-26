<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. ユーザー初期データ
        $this->call(UserSeeder::class);

        // 2. スタッフ（現場ボランティア・幹事）初期データ
        $this->call(StaffSeeder::class);

        // 3. 組織・担当初期データ
        $this->call(OrganizationSeeder::class);

        // 担当班マスター
        $this->call(LaneSeeder::class);

        // 4. タイムテーブル＆タスク初期データ
        $this->call(TimetableSeeder::class);

        // 5. スケジュール初期データ
        $this->call(ScheduleSeeder::class);

        // 6. 拠点種別および拠点・利用枠初期データ
        $this->call(LocationTypeSeeder::class);
        $this->call(LocationSeeder::class);

        // 7. 搬出入パターン初期データ
        $this->call(EquipmentLogisticsPatternSeeder::class);

        // 8. 機材および搬出入計画初期データ
        $this->call(EquipmentSeeder::class);

        // 9. 機能設定初期データ
        $this->call(FeatureSettingSeeder::class);

        // 10. 会場エリアマスターおよび現場配置・コマ割り・出店者初期データ
        $this->call(LayoutAreaSeeder::class);
        $this->call(SiteLayoutSeeder::class);

        // 11. 白地図マスター・アイコン定義・白地図配置初期データ
        $this->call(SiteBaseMapSeeder::class);
        $this->call(SiteMapIconDefinitionSeeder::class);
        $this->call(SiteMapPlacementSeeder::class);

        // 12. カラーコーン・コーンバー・バリケード配置初期データ
        $this->call(ConeMapSeeder::class);
    }
}
