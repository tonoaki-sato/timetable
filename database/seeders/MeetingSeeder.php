<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $staffMap = Staff::pluck('id', 'name')->all();

        $urushiharaId = $staffMap['漆原 拓也'] ?? Staff::first()->id;
        $hagiwaraId = $staffMap['萩原 誠'] ?? $urushiharaId;
        $satoYokoId = $staffMap['ヨーコ・サトウ'] ?? $urushiharaId;
        $miyanagaId = $staffMap['宮永 健一'] ?? $urushiharaId;
        $arieId = $staffMap['有江 浩二'] ?? $urushiharaId;
        $satoHidekiId = $staffMap['佐藤 秀樹'] ?? $urushiharaId;
        $anegawaId = $staffMap['姉川 雅代'] ?? $urushiharaId;

        $meetings = [
            // 開催予定の会議
            [
                'title' => '第1回 実行委員会 幹事会',
                'meeting_date' => now()->addDays(5)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => $urushiharaId,
                'description' => "・新年度役職選出案および各部門担当の仮決め協議\n・年間会議日程案（幹事会・総会・各部会）の策定\n・前年度運営課題の整理および申し送り事項の確認",
                'notice_note' => '出欠連絡は会議2日前までにこのLINEグループへ返信をお願いします。',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'ござ市部門 出店者募集・区画割当て打ち合わせ',
                'meeting_date' => now()->addDays(12)->toDateString(),
                'start_time' => '19:00',
                'end_time' => '20:30',
                'location' => 'コミュニティルーム 第2会議室',
                'staff_id' => $hagiwaraId,
                'description' => "・ござ敷設エリアおよび出店区画割当案の作成\n・参加規約改定案の確認（火気使用・搬入搬出ルール）\n・事前説明会の開催日程決定",
                'notice_note' => 'ござ市担当メンバーは必ずご参加ください。資料は当日配布します。',
                'created_by' => $admin->id,
            ],
            [
                'title' => '警備誘導・警察署事前協議 対策会議',
                'meeting_date' => now()->addDays(18)->toDateString(),
                'start_time' => '15:00',
                'end_time' => '16:30',
                'location' => '保土ケ谷警察署 交通規制課 打ち合わせ室',
                'staff_id' => $satoHidekiId,
                'description' => "・歩行者天国・車両通行止め区間の規制時間協議\n・緊急車両通行ルートの確保および警備員配置図の確認\n・道路使用許可申請本提出に向けた修正点すり合わせ",
                'notice_note' => '署内入館のため、身分証明書をご持参ください。',
                'created_by' => $admin->id,
            ],
            [
                'title' => '給水設備・衛生美化班 合同事前点検会議',
                'meeting_date' => now()->addDays(25)->toDateString(),
                'start_time' => '18:30',
                'end_time' => '20:00',
                'location' => 'まつり運行本部 詰所',
                'staff_id' => $miyanagaId,
                'description' => "・給水タンクおよび仮設手洗い場の設置場所確認\n・仮設トイレ設置場所およびゴミステーション動線整理\n・青葉大学学生ボランティアの配置・役割分担",
                'notice_note' => '給水・衛生のリーダーおよびサブリーダーはご出席をお願いします。',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'キッズ村 こども体験ブース企画検討会',
                'meeting_date' => now()->addDays(30)->toDateString(),
                'start_time' => '14:00',
                'end_time' => '16:00',
                'location' => 'コミュニティルーム',
                'staff_id' => $anegawaId,
                'description' => "・こども体験ブース（工作・縁日あそび）の出展内容検討\n・安全管理体制・迷子対応マニュアルの策定\n・資機材調達リストの作成",
                'notice_note' => '企画案をお持ちの方は事前に姉川までお知らせください。',
                'created_by' => $admin->id,
            ],

            // 過去の会議アーカイブ
            [
                'title' => '前年度 運行本部総括反省会',
                'meeting_date' => now()->subDays(20)->toDateString(),
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => $urushiharaId,
                'description' => "・前年度運行の全体総括および反省事項の抽出\n・各部門からの改善要望ヒアリング\n・陣羽織・資機材の回収状況確認",
                'notice_note' => '出欠報告完了済み。議事録は本部クラウドに格納。',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'カラーコーン・資機材在庫棚卸し打ち合わせ',
                'meeting_date' => now()->subDays(10)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '12:00',
                'location' => '第1倉庫（資機材置場）',
                'staff_id' => $satoYokoId,
                'description' => "・カラーコーン・コーンバー・看板の破損状況確認\n・新年度追加調達品のリストアップ\n・運搬用軽トラックの手配確認",
                'notice_note' => '作業着・軍手持参のこと。',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($meetings as $meeting) {
            Meeting::updateOrCreate(
                [
                    'title' => $meeting['title'],
                    'meeting_date' => $meeting['meeting_date'],
                ],
                $meeting
            );
        }
    }
}
