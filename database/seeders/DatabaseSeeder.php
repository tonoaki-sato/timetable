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

        // 3. 年間スケジュール初期データ
        $this->call(AnnualScheduleSeeder::class);

        // 4. タイムテーブル＆タスク初期データ
        $this->call(TimetableSeeder::class);

        // 5. 会議・打ち合わせ初期データ
        $this->call(MeetingSeeder::class);
    }
}
