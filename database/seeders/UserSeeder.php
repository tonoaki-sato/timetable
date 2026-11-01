<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. システム管理者アカウント (admin@example.com / password)
        User::updateOrCreate(
            ['email' => 'no-10.sato@docomo.ne.jp'],
            [
                'name' => 'システム管理者',
                'password' => Hash::make('p9MPGrzfit4fRN2T'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
