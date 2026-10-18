<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. システム管理者アカウント
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '実行委員長（システム管理者）',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 2. 本部オペレーターアカウント
        User::updateOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => '本部オペレーター',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'is_active' => true,
            ]
        );

        // 3. 年間スケジュール初期データ
        $this->call(AnnualScheduleSeeder::class);

        // 4. スタッフ（現場ボランティア）初期データ
        $this->call(StaffSeeder::class);

        // 5. タイムテーブル＆タスク初期データ
        $this->call(TimetableSeeder::class);
    }
}
