<?php

namespace Database\Seeders;

use App\Models\Meeting;
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

        $meetings = [
            // 開催予定の会議
            [
                'title' => '幹事会反省会 in居酒屋',
                'meeting_date' => '2026-11-07',
                'start_time' => '19:00',
                'end_time' => '20:00',
                'location' => '未定',
                'staff_id' => null,
                'description' => null,
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '総会／反省会・慰労会（打ち上げ）',
                'meeting_date' => '2026-11-21',
                'start_time' => '18:00',
                'end_time' => '20:30',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門の反省・総括報告、次年度への申し送り事項まとめ、各スタッフで洗濯・乾燥した陣羽織の回収・点検。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第1回幹事会（年間日程案策定）',
                'meeting_date' => '2027-01-02',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '年間会議日程案（幹事会・総会・各部会）の策定、前年度運営課題の整理および申し送り事項の確認。決算取り決め。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第2回幹事会（総会議案・役職選出案協議）',
                'meeting_date' => '2027-02-06',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '第1回総会に向けた議案整理、新年度役職（正副委員長・議長・書記・会計・顧問）選出案および各部門担当の仮決め協議。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第1回総会（新年度発足・役員選出・前年度会計報告）',
                'meeting_date' => '2027-02-27',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '前年度実行委員会の解散宣言および新年度発足宣言。新幹事・役職の選出と承認、前年度会計報告の承認、部門担当の仮決めと業務内容周知。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第3回幹事会（部門担当本決め調整）',
                'meeting_date' => '2027-03-06',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門（ござ市、イベント、キッズ村、衛生、企画、記録、警備、広報等）の担当本決めに向けた調整、第2回総会議案の整理。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第2回総会（部門担当本決め・開催日程通達）',
                'meeting_date' => '2027-03-27',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門担当責任者の正式決定、今年度まつり開催日程（10月第2日曜・翌月曜祝日）の通達と共有。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第4回幹事会（キャッチフレーズ・新聞チラシ準備）',
                'meeting_date' => '2027-04-03',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => 'キャッチフレーズ事前募集状況の集約、タブロイド新聞および広告募集チラシの構成案検討。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第3回総会（キャッチフレーズ決定・ポスター制作依頼）',
                'meeting_date' => '2027-04-24',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '投票結果に基づく今年度キャッチフレーズの決定、確定キャッチフレーズを反映したポスター・チラシ制作依頼の承認。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第5回幹事会（企画内容検討・新聞広告担当割当）',
                'meeting_date' => '2027-05-01',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各イベント・企画内容の検討、新聞広告募集の目標枠数設定および担当割り振り案の策定。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第4回総会（企画周知・新聞広告募集割当）',
                'meeting_date' => '2027-05-29',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各イベント・企画内容の全体周知、宿場まつり新聞広告募集の説明および各部会・幹事への営業担当割り振り。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第6回幹事会（資機材抽出・企画予算確認）',
                'meeting_date' => '2027-06-05',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門の必要資機材の洗い出し、企画詳細の精査、中間予算配分の確認。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第5回総会（資機材抽出・予算承認）',
                'meeting_date' => '2027-06-26',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '抽出された必要資機材のリスト確認、各企画の実施詳細および中間予算の全体承認。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第7回幹事会（資機材概算確認・地域連携協議）',
                'meeting_date' => '2027-07-03',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => 'レンタル機材・購入備品の概算費用確認、地域自治会長等の来賓招待および連携方針の協議。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第6回総会（資機材概算・自治会招待確認）',
                'meeting_date' => '2027-07-31',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '必要資機材の概算見積確認、地域自治会長等の招待リスト確定、新聞広告募集の締切直前進捗共有。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第8回幹事会（資機材最終決定・新聞原稿確認）',
                'meeting_date' => '2027-08-07',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門進捗確認、レンタル機材（給水・テント等）および自前資材の最終決定、宿場まつり新聞掲載原稿・協賛名一覧の確認。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第7回総会（各部門進捗確認・資機材最終決定）',
                'meeting_date' => '2027-08-28',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門の準備進捗確認、必要資機材の最終手配リスト決定、ボランティア応募状況の共有。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第9回幹事会［最終］（ポスティング・捨て看設置・ポスター張り替え）',
                'meeting_date' => '2027-09-04',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム / 会場通り一帯',
                'staff_id' => null,
                'description' => '各部門の最終確認、通行止め予告チラシのポスティング（1回目）、道路沿い捨て看板の設置、告知ポスターの張り替え作業。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '第8回総会［最終］（陣羽織準備・新聞駅前配布）',
                'meeting_date' => '2027-09-25',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'location' => 'コミュニティルーム / 保土ケ谷駅前',
                'staff_id' => null,
                'description' => '各部門最終確認、スタッフ用陣羽織の仕分け準備、夕刻の保土ケ谷駅前での宿場まつり新聞街頭配布。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => '総会／反省会・慰労会（打ち上げ）',
                'meeting_date' => '2027-11-27',
                'start_time' => '18:00',
                'end_time' => '20:30',
                'location' => 'コミュニティルーム',
                'staff_id' => null,
                'description' => '各部門の反省・総括報告、次年度への申し送り事項まとめ、各スタッフで洗濯・乾燥した陣羽織の回収・点検。',
                'notice_note' => null,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($meetings as $meeting) {
            $existing = Meeting::where('title', $meeting['title'])
                ->whereDate('meeting_date', $meeting['meeting_date'])
                ->first();

            if ($existing) {
                $existing->update($meeting);
            } else {
                Meeting::create($meeting);
            }
        }
    }
}
