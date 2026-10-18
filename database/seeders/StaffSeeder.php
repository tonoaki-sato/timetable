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
            ['name' => '漆原 拓也', 'kana' => 'ウルシハラ タクヤ', 'affiliation' => 'まつり運行本部 幹事', 'is_active' => true],
            ['name' => 'ヨーコ・サトウ', 'kana' => 'サトウ ヨーコ', 'affiliation' => 'カラーコーン班 リーダー', 'is_active' => true],
            ['name' => '宮永 健一', 'kana' => 'ミヤナガ ケンイチ', 'affiliation' => '給水設備班 リーダー', 'is_active' => true],
            ['name' => '有江 浩二', 'kana' => 'アリエ コウジ', 'affiliation' => '衛生美化班 リーダー', 'is_active' => true],
            ['name' => '佐々木 直人', 'kana' => 'ササキ ナオト', 'affiliation' => '中央商店街振興会', 'is_active' => true],
            ['name' => '高橋 美咲', 'kana' => 'タカハシ ミサキ', 'affiliation' => '青葉大学ボランティア部', 'is_active' => true],
            ['name' => '中村 亮', 'kana' => 'ナカムラ リョウ', 'affiliation' => '青葉大学ボランティア部', 'is_active' => true],
            ['name' => '小林 翔太', 'kana' => 'コバヤシ ショウタ', 'affiliation' => '青葉大学ボランティア部', 'is_active' => true],
            ['name' => '渡辺 健司', 'kana' => 'ワタナベ ケンジ', 'affiliation' => '北町青年団', 'is_active' => true],
            ['name' => '伊藤 裕美', 'kana' => 'イトウ ユミ', 'affiliation' => '一般公募ボランティア', 'is_active' => true],
            ['name' => '加藤 雅之', 'kana' => 'カトウ マサユキ', 'affiliation' => '一般公募ボランティア', 'is_active' => true],
            ['name' => '松本 さくら', 'kana' => 'マツモト サクラ', 'affiliation' => '一般公募ボランティア', 'is_active' => true],
        ];

        foreach ($staffMembers as $staff) {
            Staff::updateOrCreate(
                ['name' => $staff['name']],
                $staff
            );
        }
    }
}
