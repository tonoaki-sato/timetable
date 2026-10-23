<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffMembers = [
            // 本部・各班リーダー
            ['name' => '佐藤 外亮', 'kana' => 'サトウ トノアキ', 'affiliation' => null, 'is_active' => true],
        ];

        foreach ($staffMembers as $staff) {
            Staff::updateOrCreate(
                ['name' => $staff['name']],
                $staff
            );
        }
    }
}
