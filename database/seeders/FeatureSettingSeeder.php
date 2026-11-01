<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('feature_settings')->updateOrInsert(
            ['feature_key' => 'staff_self_registration'],
            [
                'name' => 'スタッフセルフ登録',
                'description' => 'ボランティア・現場スタッフによるWebセルフ登録受付機能',
                'is_enabled' => true,
                'opened_at' => null,
                'closed_at' => null,
                'notice_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

