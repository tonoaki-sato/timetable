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
            ['id' => 1, 'name' => '佐藤 外亮', 'kana' => 'サトウトノアキ'],
            ['id' => 2, 'name' => '松木 新太郎', 'kana' => 'マツキ シンタロウ'],
            ['id' => 3, 'name' => '田島 実', 'kana' => 'タジマ ミノル'],
            ['id' => 4, 'name' => '山本 能之', 'kana' => 'ヤマモト ヨシユキ'],
            ['id' => 5, 'name' => '岡本 隆良', 'kana' => 'オカモト ツヨシ'],
            ['id' => 6, 'name' => '宮永 彩夫', 'kana' => 'ミヤナガ アキオ'],
            ['id' => 7, 'name' => '舩越 彩香', 'kana' => 'フナコシ アヤカ'],
            ['id' => 8, 'name' => '萩原 繁夫', 'kana' => 'ハギワラ シゲオ'],
            ['id' => 9, 'name' => '木原 繁', 'kana' => 'キハラ シゲル'],
            ['id' => 10, 'name' => '九十九澤 ほのか', 'kana' => 'ツクモサワ ホノカ'],
            ['id' => 11, 'name' => '高野 直子', 'kana' => 'タカノ ナオコ'],
            ['id' => 12, 'name' => '荘司 稔', 'kana' => 'ショウジ ミノル'],
            ['id' => 13, 'name' => '山道 在明', 'kana' => 'ヤマミチ アリアキ'],
            ['id' => 14, 'name' => '高林 和幸', 'kana' => 'タカバヤシ カズユキ'],
            ['id' => 15, 'name' => '斎藤 寿美江', 'kana' => 'サイトウ スミエ'],
            ['id' => 16, 'name' => '大久保 安洋', 'kana' => 'オオクボ ヤスヒロ'],
            ['id' => 17, 'name' => '堀 祐典', 'kana' => 'ホリ ユウスケ'],
            ['id' => 18, 'name' => '佐藤 俊二', 'kana' => 'サトウ シュンジ'],
            ['id' => 19, 'name' => '原 美知子', 'kana' => 'ハラ ミチコ'],
            ['id' => 20, 'name' => '諏訪間 一希', 'kana' => 'スワマ カズキ'],
            ['id' => 21, 'name' => '原 瞳', 'kana' => 'ハラ ヒトミ'],
            ['id' => 22, 'name' => '長澤 友希', 'kana' => 'ナガサワ トモキ'],
            ['id' => 23, 'name' => '漆原 功', 'kana' => 'ウルシバラ イサオ'],
            ['id' => 24, 'name' => '斎藤 みずほ', 'kana' => 'サイトウ ミズホ'],
            ['id' => 25, 'name' => '寺井 智之', 'kana' => 'テライ トモユキ'],
            ['id' => 26, 'name' => '志田 勝信', 'kana' => 'シダ カツノブ'],
            ['id' => 27, 'name' => '有エ 喜一郎', 'kana' => 'アリエ キイチロウ'],
            ['id' => 28, 'name' => '有エ 文栄', 'kana' => 'アリエ フミエ'],
            ['id' => 29, 'name' => '田村 信義', 'kana' => 'タムラ ノブヨシ'],
            ['id' => 30, 'name' => '望月 聖子', 'kana' => 'モチヅキ セイコ'],
            ['id' => 31, 'name' => '太川 莉子', 'kana' => 'タガワ リコ'],
            ['id' => 32, 'name' => '八ツ橋 良光', 'kana' => 'ヤツハシ ヨシミツ'],
            ['id' => 33, 'name' => '岸本 光司', 'kana' => 'キシモト コウジ'],
            ['id' => 34, 'name' => '山本 歩実', 'kana' => 'ヤマモト アユミ'],
            ['id' => 35, 'name' => 'Jouko Karvonen', 'kana' => 'ヨウコ カルヴォネン'],
            ['id' => 36, 'name' => '加藤 千晴', 'kana' => 'カトウ チハル'],
        ];

        foreach ($staffMembers as $data) {
            $staff = Staff::find($data['id']) ?? new Staff;
            $staff->id = $data['id'];
            $staff->name = $data['name'];
            $staff->kana = $data['kana'];
            $staff->is_active = $data['is_active'] ?? true;
            $staff->save();
        }
    }
}
