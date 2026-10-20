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

        // 3. 年間スケジュール初期データ
        $this->call(AnnualScheduleSeeder::class);

        // 4. スタッフ（現場ボランティア）初期データ
        $this->call(StaffSeeder::class);

        // 5. タイムテーブル＆タスク初期データ
        $this->call(TimetableSeeder::class);
    }
}
