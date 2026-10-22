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
            ['name' => '漆原 拓也', 'kana' => 'ウルシハラ タクヤ', 'affiliation' => 'まつり運行本部 幹事（ござ市統括）', 'is_active' => true, 'line_display_name' => 'たくや@ござ市'],
            ['name' => '萩原 誠', 'kana' => 'ハギワラ マコト', 'affiliation' => 'まつり運行本部 副幹事（ござ市担当）', 'is_active' => true, 'line_display_name' => 'マコト'],
            ['name' => 'ヨーコ・サトウ', 'kana' => 'サトウ ヨーコ', 'affiliation' => 'カラーコーン班 リーダー', 'is_active' => true, 'line_display_name' => 'yoko.s'],
            ['name' => '宮永 健一', 'kana' => 'ミヤナガ ケンイチ', 'affiliation' => '給水設備班 リーダー', 'is_active' => true],
            ['name' => '有江 浩二', 'kana' => 'アリエ コウジ', 'affiliation' => '衛生美化班 リーダー', 'is_active' => true],
            ['name' => '佐藤 秀樹', 'kana' => 'サトウ ヒデキ', 'affiliation' => '巡回警備・広報看板担当 リーダー', 'is_active' => true],
            ['name' => '姉川 雅代', 'kana' => 'アネガワ マサヨ', 'affiliation' => 'キッズ村・イコット広場担当（アネゴ）', 'is_active' => true, 'line_display_name' => 'アネゴ'],
            ['name' => '北川 慎一郎', 'kana' => 'キタガワ シンイチロウ', 'affiliation' => '保土ケ谷商店街振興会（北川駐車場・詰所責任者）', 'is_active' => true],
            ['name' => '吉田 浩介', 'kana' => 'ヨシダ コウスケ', 'affiliation' => '救護・衛生班 サブリーダー', 'is_active' => true],
            ['name' => '佐々木 直人', 'kana' => 'ササキ ナオト', 'affiliation' => '保土ケ谷商店街振興会・警備誘導担当', 'is_active' => true],

            // 青年団・地域団体
            ['name' => '渡辺 健司', 'kana' => 'ワタナベ ケンジ', 'affiliation' => '北町青年団（資機材運搬・コーン担当）', 'is_active' => true],
            ['name' => '山口 雄大', 'kana' => 'ヤマグチ ユウダイ', 'affiliation' => '北町青年団（コーン・撤収担当）', 'is_active' => true],

            // 学生ボランティア（青葉大学・LINEセルフ登録サンプル）
            ['name' => '高橋 美咲', 'kana' => 'タカハシ ミサキ', 'affiliation' => '青葉大学ボランティア部（衛生美化）', 'is_active' => true, 'line_display_name' => 'みさき🌸', 'registered_via' => 'self'],
            ['name' => '中村 亮', 'kana' => 'ナカムラ リョウ', 'affiliation' => '青葉大学ボランティア部（給水設備）', 'is_active' => true, 'line_display_name' => 'ryo_nakamura', 'registered_via' => 'self'],
            ['name' => '小林 翔太', 'kana' => 'コバヤシ ショウタ', 'affiliation' => '青葉大学ボランティア部（警備誘導）', 'is_active' => true, 'registered_via' => 'self'],
            ['name' => '鈴木 恵', 'kana' => 'スズキ メグミ', 'affiliation' => '青葉大学ボランティア部（衛生美化）', 'is_active' => true, 'registered_via' => 'self'],
            ['name' => '井上 蓮', 'kana' => 'イノウエ レン', 'affiliation' => '青葉大学ボランティア部（パレード誘導）', 'is_active' => true, 'registered_via' => 'self'],

            // 一般公募ボランティア
            ['name' => '伊藤 裕美', 'kana' => 'イトウ ユミ', 'affiliation' => '一般公募ボランティア（給水・救護）', 'is_active' => true, 'line_display_name' => 'ゆみ', 'registered_via' => 'self'],
            ['name' => '加藤 雅之', 'kana' => 'カトウ マサユキ', 'affiliation' => '一般公募ボランティア（衛生・巡回）', 'is_active' => true, 'registered_via' => 'self'],
            ['name' => '松本 さくら', 'kana' => 'マツモト サクラ', 'affiliation' => '一般公募ボランティア（衛生・巡回）', 'is_active' => true, 'registered_via' => 'self'],
        ];

        foreach ($staffMembers as $staff) {
            Staff::updateOrCreate(
                ['name' => $staff['name']],
                $staff
            );
        }
    }
}
