<?php

namespace Database\Seeders;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimetableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // =========================================================================
        // 1. 宿場まつり 1日目 - 2026-10-11 (日)
        // =========================================================================
        $timetableDay1 = Timetable::updateOrCreate(
            ['target_date' => '2026-10-11'],
            [
                'name' => '宿場まつり 1日目',
                'description' => null,
                'start_time' => '07:00:00',
                'end_time' => '20:00:00',
                'status' => 'active',
                'road_periods' => [
                    ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
                ],
                'festival_periods' => [
                    ['start_time' => '09:00', 'end_time' => '09:30', 'label' => '受付開始', 'status' => 'active'],
                    ['start_time' => '11:00', 'end_time' => '17:00', 'label' => '開催中', 'status' => 'active'],
                ],
                'created_by' => $admin->id,
            ]
        );

        // 1日目レーン定義（登録済み表示順序・主担当スタッフを完全再現）
        $lanesDay1Data = [
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 20,
                'lead_staff_id' => 27, // 有エ 喜一郎
                'description' => '仮設トイレ・ごみステーション設置・回収袋交換',
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 40,
                'lead_staff_id' => 35, // Jouko Karvonen
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 50,
                'lead_staff_id' => 6, // 宮永 彩夫
                'description' => '給水タンク・仮設手洗い場・配管ホース設置',
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 60,
                'lead_staff_id' => 1, // 佐藤 外亮
                'description' => '会場警備・迷子対応・防犯パトロール・動線監視',
                'is_core' => true,
                'is_active' => true,
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 70,
                'lead_staff_id' => 5, // 岡本 隆良
                'description' => 'こども体験ブース設営・運営・安全管理',
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 80,
                'lead_staff_id' => 37, // 森 和成
                'description' => '提灯取付・配線点検・点灯確認・翌日回収',
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 90,
                'lead_staff_id' => 23, // 漆原 功
                'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携',
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 100,
                'lead_staff_id' => 19, // 原 美知子
                'description' => '本部運営・来賓応接・放送アナウンス・総合案内',
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 110,
                'lead_staff_id' => null,
                'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃',
                'is_core' => false,
                'is_active' => true,
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 120,
                'lead_staff_id' => 1, // 佐藤 外亮
                'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収',
                'is_core' => true,
                'is_active' => true,
            ],
        ];

        $lanesDay1 = [];
        foreach ($lanesDay1Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $lanesDay1[$laneItem['name']] = Lane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'name' => $laneItem['name'],
                ],
                $laneItem
            );
        }

        // 1日目タスク一覧（登録済み実データ24件）
        $tasksDay1 = [
            // --- カラーコーン (5件) ---
            [
                'lane_name' => 'カラーコーン',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'description' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '仮設置開始',
                'start_time' => '07:30:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => "カラーコーン配置マップを参考にカラーコーンを設置位置近辺に仮設置していきます。\r\nカラーコーンは税務署前駐車場にあります。",
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '本設置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => "通行止め開始後、支道の進入口を優先的にカラーコーンを本設置していきます。\r\n設置後余ったカラーコーンはS&S駐車場にまとめておいておきます。",
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '撤去開始',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => "あまり重要ではない設置位置のカラーコーンを撤去していきます。\r\n翌日の設置も考慮して保管位置を決めます。",
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '完全撤去',
                'start_time' => '18:00:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => '通行止め解除と同時に支道の出入り口を塞いでいたカラーコーンを撤去します。',
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => true,
                'requires_review' => true,
                'status' => 'not_started',
            ],

            // --- 衛生 (7件) ---
            [
                'lane_name' => '衛生',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'description' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '衛生',
                'title' => '事前準備',
                'start_time' => '07:30:00',
                'end_time' => '10:00:00',
                'location' => '北川製粉所横',
                'content' => "ゴミ箱を準備します。\r\n① 名札設置\r\n② 袋設置\r\n③ ブルーシート設置",
                'description' => null,
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '衛生',
                'title' => 'ゴミ箱配置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => '北川製粉所横',
                'content' => null,
                'description' => null,
                'required_people_count' => 4,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '衛生',
                'title' => 'ゴミ箱チェック',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => "ゴミ箱をチェックします。\r\n① ゴミ圧縮、箸串回収\r\n② ゴミ袋交換",
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '衛生',
                'title' => '撤収作業',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => "撤収作業\r\n① ゴミ箱回収（段階的に）\r\n② ゴミ袋収集",
                'description' => null,
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '衛生',
                'title' => '清掃',
                'start_time' => '18:00:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => null,
                'description' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '衛生',
                'title' => '業者のごみ回収',
                'start_time' => '19:00:00',
                'end_time' => '19:30:00',
                'location' => '北川製粉所横',
                'content' => "業者のごみ回収を待ちます。\r\n回収後はブルーシートを洗浄します。",
                'description' => null,
                'required_people_count' => 6,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],

            // --- 給水設備 (4件) ---
            [
                'lane_name' => '給水設備',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'description' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '給水設備',
                'title' => '設置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => '税務署前駐車場から各設置ポイントへ',
                'description' => null,
                'required_people_count' => 6,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '給水設備',
                'title' => '点検',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '給排水タンクの管理',
                'description' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '給水設備',
                'title' => '撤去',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '翌日の配置を考慮して撤去',
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],

            // --- 巡回 (4件) ---
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => "本陣受付開始直後の混雑対応（2名）\r\n通行止め前の巡回（2名）",
                'description' => null,
                'required_people_count' => 4,
                'is_at_least' => false,
                'is_critical' => true,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '巡回',
                'title' => '定期巡回',
                'start_time' => '12:00:00',
                'end_time' => '14:00:00',
                'location' => null,
                'content' => null,
                'description' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '巡回',
                'title' => '定期巡回',
                'start_time' => '15:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => null,
                'description' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '17:30:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => '通行止め解除前後の巡回',
                'description' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => true,
                'requires_review' => false,
                'status' => 'not_started',
            ],

            // --- キッズ村 (3件) ---
            [
                'lane_name' => 'キッズ村',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'description' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'lane_name' => 'キッズ村',
                'title' => '設営',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'location' => 'キッズ村',
                'content' => null,
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],
            [
                'lane_name' => 'キッズ村',
                'title' => 'アテンド',
                'start_time' => '10:30:00',
                'end_time' => '12:00:00',
                'location' => 'ココカラファイン前',
                'content' => null,
                'description' => null,
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],

            // --- ちょうちん (1件) ---
            [
                'lane_name' => 'ちょうちん',
                'title' => 'ちょうちん掲揚',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => 'ココカラファイン前',
                'content' => null,
                'description' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'not_started',
            ],
        ];

        foreach ($tasksDay1 as $taskData) {
            $lane = $lanesDay1[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'lane_id' => $lane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'end_time' => $taskData['end_time'],
                    'location' => $taskData['location'],
                    'content' => $taskData['content'],
                    'description' => $taskData['description'],
                    'required_people_count' => $taskData['required_people_count'],
                    'is_at_least' => $taskData['is_at_least'],
                    'is_critical' => $taskData['is_critical'],
                    'requires_review' => $taskData['requires_review'],
                    'status' => $taskData['status'],
                ]
            );

            if ($lane->lead_staff_id) {
                $task->assignments()->firstOrCreate(
                    ['staff_id' => $lane->lead_staff_id],
                    [
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ]
                );
            }
        }

        // =========================================================================
        // 2. 宿場まつり 2日目 - 2026-10-12 (月)
        // =========================================================================
        $timetableDay2 = Timetable::updateOrCreate(
            ['target_date' => '2026-10-12'],
            [
                'name' => '宿場まつり 2日目',
                'description' => null,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'status' => 'completed',
                'road_periods' => [
                    ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
                ],
                'festival_periods' => [
                    ['start_time' => '09:00', 'end_time' => '11:00', 'label' => 'ござ市受付開始', 'status' => 'active'],
                    ['start_time' => '11:00', 'end_time' => '17:00', 'label' => '開催中', 'status' => 'active'],
                ],
                'created_by' => $admin->id,
            ]
        );

        // 2日目のデフォルトレーン生成（登録済み初期順序）
        $timetableDay2->createDefaultLanes();
    }
}
