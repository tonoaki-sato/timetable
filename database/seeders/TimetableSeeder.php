<?php

namespace Database\Seeders;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
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
        // 1. 宿場まつり 1日目 - 2026-10-11
        // =========================================================================
        $timetableDay1 = Timetable::updateOrCreate(
            ['target_date' => '2026-10-11'],
            [
                'name' => '宿場まつり 1日目',
                'description' => 'テスト',
                'start_time' => '07:00:00',
                'end_time' => '20:00:00',
                'status' => 'active',
                'road_periods' => [
                    0 => [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    0 => [
                        'label' => '受付開始',
                        'status' => 'active',
                        'end_time' => '09:30',
                        'start_time' => '09:00',
                    ],
                    1 => [
                        'label' => '開催中',
                        'status' => 'active',
                        'end_time' => '17:00',
                        'start_time' => '11:00',
                    ],
                ],
                'created_by' => $admin->id,
            ]
        );

        $lanesDay1Data = [
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 20,
                'lead_staff_id' => 27,
                'fixed_staff_ids' => [
                ],
                'description' => '仮設トイレ・ごみステーション設置・回収袋交換',
            ],
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 40,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [
                ],
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 50,
                'lead_staff_id' => 6,
                'fixed_staff_ids' => [
                    0 => 6,
                    1 => 18,
                    2 => 28,
                ],
                'description' => '給水タンク・仮設手洗い場・配管ホース設置',
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 60,
                'lead_staff_id' => 1,
                'fixed_staff_ids' => [
                ],
                'description' => '会場警備・迷子対応・防犯パトロール・動線監視',
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 70,
                'lead_staff_id' => 5,
                'fixed_staff_ids' => [
                ],
                'description' => 'こども体験ブース設営・運営・安全管理',
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 80,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [
                ],
                'description' => '提灯取付・配線点検・点灯確認・翌日回収',
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 90,
                'lead_staff_id' => 23,
                'fixed_staff_ids' => [
                ],
                'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携',
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 100,
                'lead_staff_id' => 19,
                'fixed_staff_ids' => [
                ],
                'description' => '本部運営・来賓応接・放送アナウンス・総合案内',
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 110,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃',
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 120,
                'lead_staff_id' => 1,
                'fixed_staff_ids' => [
                ],
                'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収',
            ],
        ];

        $lanesDay1 = [];
        foreach ($lanesDay1Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $lane = Lane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'name' => $laneItem['name'],
                ],
                [
                    'color' => $laneItem['color'],
                    'icon' => $laneItem['icon'],
                    'display_order' => $laneItem['display_order'],
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'description' => $laneItem['description'],
                ]
            );

            $lane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay1[$laneItem['name']] = $lane;
        }

        $tasksDay1 = [
            [
                'lane_name' => '衛生',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'キッズ村',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 5,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => 'ほげほが',
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '集合/全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '仮設置開始',
                'start_time' => '07:30:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => 'カラーコーン配置マップを参考にカラーコーンを設置位置近辺に仮設置していきます。
カラーコーンは税務署前駐車場にあります。',
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '事前準備',
                'start_time' => '07:30:00',
                'end_time' => '10:00:00',
                'location' => '北川製粉所横',
                'content' => 'ゴミ箱を準備します。
① 名札設置
② 袋設置
③ ブルーシート設置',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'キッズ村',
                'title' => '設営',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'location' => 'キッズ村',
                'content' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 5,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => '本陣受付開始直後の混雑対応（2名）
通行止め前の巡回（2名）',
                'required_people_count' => 4,
                'is_at_least' => false,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '設置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => '税務署前駐車場から各設置ポイントへ',
                'required_people_count' => 6,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => 'ゴミ箱配置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => '北川製粉所横',
                'content' => null,
                'required_people_count' => 4,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'title' => 'ちょうちん掲揚',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => 'ココカラファイン前',
                'content' => null,
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '本設置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => '通行止め開始後、支道の進入口を優先的にカラーコーンを本設置していきます。
設置後余ったカラーコーンはS&S駐車場にまとめておいておきます。',
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'キッズ村',
                'title' => 'アテンド',
                'start_time' => '10:30:00',
                'end_time' => '12:00:00',
                'location' => 'ココカラファイン前',
                'content' => null,
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 5,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '点検',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '給排水タンクの管理',
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => 'ゴミ箱チェック',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => 'ゴミ箱をチェックします。
① ゴミ圧縮、箸串回収
② ゴミ袋交換',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '定期巡回',
                'start_time' => '12:00:00',
                'end_time' => '14:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '定期巡回',
                'start_time' => '15:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '撤去',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '翌日の配置を考慮して撤去',
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '撤収作業',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '撤収作業
① ゴミ箱回収（段階的に）
② ゴミ袋収集',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '撤去開始',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => 'あまり重要ではない設置位置のカラーコーンを撤去していきます。
翌日の設置も考慮して保管位置を決めます。',
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '17:30:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => '通行止め解除前後の巡回',
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '完全撤去',
                'start_time' => '18:00:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => '通行止め解除と同時に支道の出入り口を塞いでいたカラーコーンを撤去します。',
                'required_people_count' => 3,
                'is_at_least' => false,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '清掃',
                'start_time' => '18:00:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '業者のごみ回収',
                'start_time' => '19:00:00',
                'end_time' => '19:30:00',
                'location' => '北川製粉所横',
                'content' => '業者のごみ回収を待ちます。
回収後はブルーシートを洗浄します。',
                'required_people_count' => 6,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
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
                    'required_people_count' => $taskData['required_people_count'],
                    'is_at_least' => $taskData['is_at_least'],
                    'is_critical' => $taskData['is_critical'],
                    'status' => $taskData['status'],
                ]
            );

            if (! empty($taskData['assignments'])) {
                foreach ($taskData['assignments'] as $assignData) {
                    $task->assignments()->updateOrCreate(
                        ['staff_id' => $assignData['staff_id']],
                        [
                            'role_description' => $assignData['role_description'],
                            'attendance_status' => $assignData['attendance_status'],
                        ]
                    );
                }
            } elseif ($lane->lead_staff_id) {
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
        // 2. 宿場まつり 2日目 - 2026-10-12
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
                    0 => [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    0 => [
                        'label' => 'ござ市受付開始',
                        'status' => 'active',
                        'end_time' => '11:00',
                        'start_time' => '09:00',
                    ],
                    1 => [
                        'label' => '開催中',
                        'status' => 'active',
                        'end_time' => '17:00',
                        'start_time' => '11:00',
                    ],
                ],
                'created_by' => $admin->id,
            ]
        );

        $lanesDay2Data = [
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 10,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 20,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '給水タンク・仮設手洗い場・配管ホース設置',
            ],
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 30,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '仮設トイレ・ごみステーション設置・回収袋交換',
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 40,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '会場警備・迷子対応・防犯パトロール・動線監視',
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 50,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => 'こども体験ブース設営・運営・安全管理',
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 60,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '提灯取付・配線点検・点灯確認・翌日回収',
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 70,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携',
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 80,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '本部運営・来賓応接・放送アナウンス・総合案内',
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 90,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃',
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 100,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収',
            ],
        ];

        $lanesDay2 = [];
        foreach ($lanesDay2Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $lane = Lane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay2->id,
                    'name' => $laneItem['name'],
                ],
                [
                    'color' => $laneItem['color'],
                    'icon' => $laneItem['icon'],
                    'display_order' => $laneItem['display_order'],
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'description' => $laneItem['description'],
                ]
            );

            $lane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay2[$laneItem['name']] = $lane;
        }

        // =========================================================================
        // 3. 幹事会反省会（デモ） - 2026-11-07
        // =========================================================================
        $timetableDay3 = Timetable::updateOrCreate(
            ['target_date' => '2026-11-07'],
            [
                'name' => '幹事会反省会（デモ）',
                'description' => '宿場まつりの反省会',
                'start_time' => '19:00:00',
                'end_time' => '23:00:00',
                'status' => 'draft',
                'road_periods' => null,
                'festival_periods' => null,
                'created_by' => $admin->id,
            ]
        );

        $lanesDay3Data = [
            [
                'name' => '幹事会',
                'color' => 'amber',
                'icon' => '📋',
                'display_order' => 10,
                'lead_staff_id' => 24,
                'fixed_staff_ids' => [
                    0 => 1,
                    1 => 30,
                    2 => 17,
                    3 => 24,
                    4 => 13,
                    5 => 5,
                    6 => 15,
                    7 => 26,
                    8 => 29,
                    9 => 2,
                    10 => 35,
                    11 => 22,
                    12 => 7,
                    13 => 8,
                    14 => 36,
                ],
                'description' => null,
            ],
        ];

        $lanesDay3 = [];
        foreach ($lanesDay3Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $lane = Lane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay3->id,
                    'name' => $laneItem['name'],
                ],
                [
                    'color' => $laneItem['color'],
                    'icon' => $laneItem['icon'],
                    'display_order' => $laneItem['display_order'],
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'description' => $laneItem['description'],
                ]
            );

            $lane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay3[$laneItem['name']] = $lane;
        }

        $tasksDay3 = [
            [
                'lane_name' => '幹事会',
                'title' => '反省会',
                'start_time' => '19:00:00',
                'end_time' => '21:00:00',
                'location' => '番所',
                'content' => '第34回保土ケ谷宿場まつりの反省まとめ',
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 1,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 30,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 17,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    4 => [
                        'staff_id' => 13,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    5 => [
                        'staff_id' => 5,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    6 => [
                        'staff_id' => 15,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    7 => [
                        'staff_id' => 26,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    8 => [
                        'staff_id' => 29,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    9 => [
                        'staff_id' => 2,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    10 => [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    11 => [
                        'staff_id' => 22,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    12 => [
                        'staff_id' => 7,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    13 => [
                        'staff_id' => 8,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    14 => [
                        'staff_id' => 36,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '幹事会',
                'title' => '飲み会',
                'start_time' => '21:00:00',
                'end_time' => '23:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 1,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 30,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 17,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    4 => [
                        'staff_id' => 13,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    5 => [
                        'staff_id' => 5,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    6 => [
                        'staff_id' => 15,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    7 => [
                        'staff_id' => 26,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    8 => [
                        'staff_id' => 29,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    9 => [
                        'staff_id' => 2,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    10 => [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    11 => [
                        'staff_id' => 22,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    12 => [
                        'staff_id' => 7,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    13 => [
                        'staff_id' => 8,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    14 => [
                        'staff_id' => 36,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
        ];

        foreach ($tasksDay3 as $taskData) {
            $lane = $lanesDay3[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay3->id,
                    'lane_id' => $lane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'end_time' => $taskData['end_time'],
                    'location' => $taskData['location'],
                    'content' => $taskData['content'],
                    'required_people_count' => $taskData['required_people_count'],
                    'is_at_least' => $taskData['is_at_least'],
                    'is_critical' => $taskData['is_critical'],
                    'status' => $taskData['status'],
                ]
            );

            if (! empty($taskData['assignments'])) {
                foreach ($taskData['assignments'] as $assignData) {
                    $task->assignments()->updateOrCreate(
                        ['staff_id' => $assignData['staff_id']],
                        [
                            'role_description' => $assignData['role_description'],
                            'attendance_status' => $assignData['attendance_status'],
                        ]
                    );
                }
            } elseif ($lane->lead_staff_id) {
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
        // 4. 第35回保土ケ谷宿場まつり（1日目） - 2027-10-10
        // =========================================================================
        $timetableDay4 = Timetable::updateOrCreate(
            ['target_date' => '2027-10-10'],
            [
                'name' => '第35回保土ケ谷宿場まつり（1日目）',
                'description' => null,
                'start_time' => '07:00:00',
                'end_time' => '20:00:00',
                'status' => 'draft',
                'road_periods' => [
                    0 => [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    0 => [
                        'label' => '開催中',
                        'status' => 'active',
                        'end_time' => '17:00',
                        'start_time' => '11:00',
                    ],
                ],
                'created_by' => $admin->id,
            ]
        );

        $lanesDay4Data = [
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 20,
                'lead_staff_id' => 33,
                'fixed_staff_ids' => [
                    0 => 33,
                ],
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 30,
                'lead_staff_id' => 6,
                'fixed_staff_ids' => [
                    0 => 6,
                    1 => 37,
                ],
                'description' => '給水タンク・仮設手洗い場・配管ホース設置',
            ],
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 40,
                'lead_staff_id' => 26,
                'fixed_staff_ids' => [
                    0 => 26,
                ],
                'description' => '仮設トイレ・ごみステーション設置・回収袋交換',
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 50,
                'lead_staff_id' => 1,
                'fixed_staff_ids' => [
                    0 => 1,
                    1 => 14,
                ],
                'description' => '会場警備・迷子対応・防犯パトロール・動線監視',
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 60,
                'lead_staff_id' => 5,
                'fixed_staff_ids' => [
                    0 => 5,
                ],
                'description' => 'こども体験ブース設営・運営・安全管理',
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 70,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [
                    0 => 35,
                ],
                'description' => '提灯取付・配線点検・点灯確認・翌日回収',
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 80,
                'lead_staff_id' => 22,
                'fixed_staff_ids' => [
                    0 => 22,
                ],
                'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携',
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 90,
                'lead_staff_id' => 19,
                'fixed_staff_ids' => [
                    0 => 19,
                ],
                'description' => '本部運営・来賓応接・放送アナウンス・総合案内',
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 100,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [
                ],
                'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃',
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 110,
                'lead_staff_id' => 21,
                'fixed_staff_ids' => [
                    0 => 21,
                ],
                'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収',
            ],
            [
                'name' => '直営店',
                'color' => 'indigo',
                'icon' => '🛒',
                'display_order' => 120,
                'lead_staff_id' => 28,
                'fixed_staff_ids' => [
                    0 => 28,
                    1 => 4,
                ],
                'description' => '直営店の企画・管理など',
            ],
            [
                'name' => 'イベント',
                'color' => 'orange',
                'icon' => '🎤',
                'display_order' => 130,
                'lead_staff_id' => 13,
                'fixed_staff_ids' => [
                    0 => 13,
                    1 => 12,
                ],
                'description' => 'ステージ、イベントブースの準備・出演者管理、運行など',
            ],
            [
                'name' => '宿場くん/宿場ちゃん',
                'color' => 'red',
                'icon' => '🏃‍♂️',
                'display_order' => 140,
                'lead_staff_id' => 15,
                'fixed_staff_ids' => [
                    0 => 10,
                    1 => 25,
                    2 => 23,
                    3 => 15,
                ],
                'description' => 'マスコットキャラのマネジメント、パフォーマンス',
            ],
            [
                'name' => '撮影記録',
                'color' => 'indigo',
                'icon' => '📸',
                'display_order' => 150,
                'lead_staff_id' => 17,
                'fixed_staff_ids' => [
                    0 => 17,
                ],
                'description' => '宣材・証跡用素材写真撮影記録',
            ],
            [
                'name' => 'IT',
                'color' => 'emerald',
                'icon' => '💻',
                'display_order' => 160,
                'lead_staff_id' => 7,
                'fixed_staff_ids' => [
                    0 => 7,
                ],
                'description' => null,
            ],
        ];

        $lanesDay4 = [];
        foreach ($lanesDay4Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $lane = Lane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay4->id,
                    'name' => $laneItem['name'],
                ],
                [
                    'color' => $laneItem['color'],
                    'icon' => $laneItem['icon'],
                    'display_order' => $laneItem['display_order'],
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'description' => $laneItem['description'],
                ]
            );

            $lane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay4[$laneItem['name']] = $lane;
        }

        $tasksDay4 = [
            [
                'lane_name' => 'カラーコーン',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => '出欠確認、申し合わせ',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '仮設置開始',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => '税務署前駐車場から各設置ポイントの通行の妨げにならない場所に設置',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '本設置開始',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => '重要度の高い支道出入り口から順に設置',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '仮撤去開始',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '優先度の低い順に可能な所から翌日の設置位置に近い拠点に撤去。',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'title' => '本撤去',
                'start_time' => '18:00:00',
                'end_time' => '19:00:00',
                'location' => null,
                'content' => '支道の出入り口など重要ポイントは確実に撤去。
翌日の設置位置に近い拠点に移動。
支道の出入り口など重要ポイントは確実に撤去。
翌日の設置位置に近い拠点に移動。',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => '出欠確認、申し合わせ',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '準備',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'location' => '税務署前駐車場',
                'content' => '石鹸、案内札、給排水ポリタンクの準備',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '設置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => '機材の設置、動作確認',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '定期巡回',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '給排水ポリタンクの交換など',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'title' => '撤去開始・完全撤去',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => "稼働率の低いポイントから順次撤去。通行止め解除前には完全撤去。\r\n2日目の設置ポイントを考慮して最寄りの拠点に機材を移動。",
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    2 => [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    3 => [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => '出欠確認、申し合わせ',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '事前準備',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => "ゴミ箱準備\r\n① 名札設置\r\n② 袋設置\r\n③ ブルーシート設置",
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => 'ゴミ箱配置',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 4,
                'is_at_least' => false,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => 'ゴミ箱チェック',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => "① ゴミ圧縮、箸串回収\r\n② ゴミ袋交換",
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '撤収作業',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => "① ゴミ箱回収（段階的に）\r\n② ゴミ袋収集",
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'title' => '業者のごみ回収',
                'start_time' => '19:00:00',
                'end_time' => '19:30:00',
                'location' => null,
                'content' => 'ブルーシート洗浄',
                'required_people_count' => 6,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => '出欠確認、申し合わせ',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '本陣前交通整理',
                'start_time' => '09:00:00',
                'end_time' => '09:30:00',
                'location' => '本陣',
                'content' => 'ござ市受付開始時の本陣前安全確保',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '09:30:00',
                'end_time' => '10:30:00',
                'location' => null,
                'content' => '通行止め前後の安全確保',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => "定期巡回\r\n① 私有地進入注意\r\n② 路上のごみ拾い",
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'title' => '巡回',
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'location' => null,
                'content' => "通行止め解除前後の安全確保\r\n① 解除前：ござ市出店者への注意呼びかけ\r\n② 解除後：通行の安全注意呼びかけ",
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    0 => [
                        'staff_id' => 1,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    1 => [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
        ];

        foreach ($tasksDay4 as $taskData) {
            $lane = $lanesDay4[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay4->id,
                    'lane_id' => $lane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'end_time' => $taskData['end_time'],
                    'location' => $taskData['location'],
                    'content' => $taskData['content'],
                    'required_people_count' => $taskData['required_people_count'],
                    'is_at_least' => $taskData['is_at_least'],
                    'is_critical' => $taskData['is_critical'],
                    'status' => $taskData['status'],
                ]
            );

            if (! empty($taskData['assignments'])) {
                foreach ($taskData['assignments'] as $assignData) {
                    $task->assignments()->updateOrCreate(
                        ['staff_id' => $assignData['staff_id']],
                        [
                            'role_description' => $assignData['role_description'],
                            'attendance_status' => $assignData['attendance_status'],
                        ]
                    );
                }
            } elseif ($lane->lead_staff_id) {
                $task->assignments()->firstOrCreate(
                    ['staff_id' => $lane->lead_staff_id],
                    [
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ]
                );
            }
        }

        // 班長からの応援要請・機材申請サンプルデータのシード化
        $day1ColorConeLane = Lane::where('timetable_id', $timetableDay1->id)->where('name', 'カラーコーン')->first();
        $day1HygieneLane = Lane::where('timetable_id', $timetableDay1->id)->where('name', '衛生')->first();
        $firstTask = TimetableTask::where('timetable_id', $timetableDay1->id)->first();

        if ($day1ColorConeLane) {
            LaneRequest::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'lane_id' => $day1ColorConeLane->id,
                    'request_type' => 'staffing',
                    'equipment_name' => null,
                ],
                [
                    'timetable_task_id' => $firstTask?->id,
                    'requested_by_staff_id' => $day1ColorConeLane->lead_staff_id,
                    'requested_count' => 2,
                    'reason' => '東口コーン設置の補助要請（通行止め直後の集中配置）',
                    'status' => 'pending',
                ]
            );

            LaneRequest::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'lane_id' => $day1ColorConeLane->id,
                    'request_type' => 'equipment',
                    'equipment_name' => 'トランシーバー',
                ],
                [
                    'timetable_task_id' => $firstTask?->id,
                    'requested_by_staff_id' => $day1ColorConeLane->lead_staff_id,
                    'requested_count' => 3,
                    'reason' => '東口と西口の連絡用に追加手配希望',
                    'status' => 'approved',
                ]
            );
        }

        if ($day1HygieneLane) {
            LaneRequest::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'lane_id' => $day1HygieneLane->id,
                    'request_type' => 'equipment',
                    'equipment_name' => 'ゴミ袋（特大）',
                ],
                [
                    'timetable_task_id' => null,
                    'requested_by_staff_id' => $day1HygieneLane->lead_staff_id,
                    'requested_count' => 5,
                    'reason' => '午後用の予備補充申請',
                    'status' => 'resolved',
                ]
            );
        }

        // シードされた全タイムテーブルをスケジュール管理（schedules）へ自動同期
        $scheduleRepository = app(ScheduleRepositoryInterface::class);
        foreach (Timetable::all() as $timetable) {
            $scheduleRepository->syncForTimetable($timetable);
        }
    }
}
