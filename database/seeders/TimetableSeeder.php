<?php

namespace Database\Seeders;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableLane;
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
        if (Lane::count() === 0) {
            $this->call(LaneSeeder::class);
        }

        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $routineTitles = ['巡回', '定期巡回', '巡回・見回り', 'ゴミ箱チェック', '点検', '本陣業務', '管理・イベント運営'];
        $resolveTaskType = static fn (string $title): string => in_array($title, $routineTitles, true) ? 'routine' : 'spot';

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
                'status' => 'completed',
                'road_periods' => [
                    [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    [
                        'label' => '受付開始',
                        'status' => 'active',
                        'end_time' => '09:30',
                        'start_time' => '09:00',
                    ],
                    [
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
                'fixed_staff_ids' => [],
                'description' => '仮設トイレ・ごみステーション設置・回収袋交換',
            ],
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 40,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [],
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 50,
                'lead_staff_id' => 6,
                'fixed_staff_ids' => [6, 18, 28],
                'description' => '給水タンク・仮設手洗い場・配管ホース設置',
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 60,
                'lead_staff_id' => 1,
                'fixed_staff_ids' => [],
                'description' => '会場警備・迷子対応・防犯パトロール・動線監視',
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 70,
                'lead_staff_id' => 5,
                'fixed_staff_ids' => [],
                'description' => 'こども体験ブース設営・運営・安全管理',
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 80,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [],
                'description' => '提灯取付・配線点検・点灯確認・翌日回収',
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 90,
                'lead_staff_id' => 23,
                'fixed_staff_ids' => [],
                'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携',
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 100,
                'lead_staff_id' => 19,
                'fixed_staff_ids' => [],
                'description' => '本部運営・来賓応接・放送アナウンス・総合案内',
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 110,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃',
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 120,
                'lead_staff_id' => 1,
                'fixed_staff_ids' => [],
                'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収',
            ],
        ];

        $lanesDay1 = [];
        foreach ($lanesDay1Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $master = Lane::where('name', $laneItem['name'])->firstOrFail();

            $timetableLane = TimetableLane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'lane_id' => $master->id,
                ],
                [
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'display_order' => $laneItem['display_order'],
                    'notes' => null,
                ]
            );

            $timetableLane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay1[$laneItem['name']] = $timetableLane;
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
                    [
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
                    [
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
                    [
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
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
                    [
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
                    [
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
                    [
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
                    [
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
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
                    [
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
                    [
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
                    [
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
                    [
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
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
                    [
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
                    [
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
                    [
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
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
                    [
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
                    [
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
                    [
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
                    [
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
                    [
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
                    [
                        'staff_id' => 27,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
        ];

        foreach ($tasksDay1 as $taskData) {
            $timetableLane = $lanesDay1[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'timetable_lane_id' => $timetableLane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'task_type' => $taskData['task_type'] ?? $resolveTaskType($taskData['title']),
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
                    if (Staff::where('id', $assignData['staff_id'])->exists()) {
                        $task->assignments()->updateOrCreate(
                            ['staff_id' => $assignData['staff_id']],
                            [
                                'role_description' => $assignData['role_description'],
                                'attendance_status' => $assignData['attendance_status'],
                            ]
                        );
                    }
                }
            } elseif ($lane->lead_staff_id && Staff::where('id', $lane->lead_staff_id)->exists()) {
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
                    [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    [
                        'label' => 'ござ市受付開始',
                        'status' => 'active',
                        'end_time' => '11:00',
                        'start_time' => '09:00',
                    ],
                    [
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
                'fixed_staff_ids' => [],
                'description' => '路上規制バー・コーン搬入・本設置・撤去',
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 20,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '給水タンク・仮設手洗い場・配管ホース設置',
            ],
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 30,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '仮設トイレ・ごみステーション設置・回収袋交換',
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 40,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '会場警備・迷子対応・防犯パトロール・動線監視',
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 50,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => 'こども体験ブース設営・運営・安全管理',
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 60,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '提灯取付・配線点検・点灯確認・翌日回収',
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 70,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携',
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 80,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '本部運営・来賓応接・放送アナウンス・総合案内',
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 90,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃',
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 100,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収',
            ],
        ];

        $lanesDay2 = [];
        foreach ($lanesDay2Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $master = Lane::where('name', $laneItem['name'])->firstOrFail();

            $timetableLane = TimetableLane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay2->id,
                    'lane_id' => $master->id,
                ],
                [
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'display_order' => $laneItem['display_order'],
                    'notes' => null,
                ]
            );

            $timetableLane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay2[$laneItem['name']] = $timetableLane;
        }

        $tasksDay2 = [
        ];

        foreach ($tasksDay2 as $taskData) {
            $timetableLane = $lanesDay2[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay2->id,
                    'timetable_lane_id' => $timetableLane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'task_type' => $taskData['task_type'] ?? $resolveTaskType($taskData['title']),
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
                    if (Staff::where('id', $assignData['staff_id'])->exists()) {
                        $task->assignments()->updateOrCreate(
                            ['staff_id' => $assignData['staff_id']],
                            [
                                'role_description' => $assignData['role_description'],
                                'attendance_status' => $assignData['attendance_status'],
                            ]
                        );
                    }
                }
            } elseif ($lane->lead_staff_id && Staff::where('id', $lane->lead_staff_id)->exists()) {
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
        // 3. 幹事会反省会（デモ） - 2026-11-07
        // =========================================================================
        $timetableDay3 = Timetable::updateOrCreate(
            ['target_date' => '2026-11-07'],
            [
                'name' => '幹事会反省会（デモ）',
                'description' => '宿場まつりの反省会',
                'start_time' => '19:00:00',
                'end_time' => '23:00:00',
                'status' => 'completed',
                'road_periods' => [],
                'festival_periods' => [],
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
                    1,
                    2,
                    5,
                    7,
                    8,
                    13,
                    15,
                    17,
                    22,
                    24,
                    26,
                    29,
                    30,
                    35,
                    36,
                ],
                'description' => null,
            ],
        ];

        $lanesDay3 = [];
        foreach ($lanesDay3Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $master = Lane::where('name', $laneItem['name'])->firstOrFail();

            $timetableLane = TimetableLane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay3->id,
                    'lane_id' => $master->id,
                ],
                [
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'display_order' => $laneItem['display_order'],
                    'notes' => null,
                ]
            );

            $timetableLane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay3[$laneItem['name']] = $timetableLane;
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
                    [
                        'staff_id' => 1,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 2,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 5,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 7,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 8,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 13,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 15,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 17,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 22,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 29,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 30,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
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
                    [
                        'staff_id' => 1,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 2,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 5,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 7,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 8,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 13,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 15,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 17,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 22,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 29,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 30,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 36,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
        ];

        foreach ($tasksDay3 as $taskData) {
            $timetableLane = $lanesDay3[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay3->id,
                    'timetable_lane_id' => $timetableLane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'task_type' => $taskData['task_type'] ?? $resolveTaskType($taskData['title']),
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
                    if (Staff::where('id', $assignData['staff_id'])->exists()) {
                        $task->assignments()->updateOrCreate(
                            ['staff_id' => $assignData['staff_id']],
                            [
                                'role_description' => $assignData['role_description'],
                                'attendance_status' => $assignData['attendance_status'],
                            ]
                        );
                    }
                }
            } elseif ($lane->lead_staff_id && Staff::where('id', $lane->lead_staff_id)->exists()) {
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
                    [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    [
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
                'fixed_staff_ids' => [33],
                'notes' => null,
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 30,
                'lead_staff_id' => 6,
                'fixed_staff_ids' => [6, 37],
                'notes' => null,
            ],
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 40,
                'lead_staff_id' => 26,
                'fixed_staff_ids' => [26],
                'notes' => null,
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 50,
                'lead_staff_id' => 43,
                'fixed_staff_ids' => [14, 43],
                'notes' => null,
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 60,
                'lead_staff_id' => 5,
                'fixed_staff_ids' => [5, 15],
                'notes' => null,
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 70,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [35, 39],
                'notes' => null,
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 80,
                'lead_staff_id' => 22,
                'fixed_staff_ids' => [22, 35],
                'notes' => null,
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 90,
                'lead_staff_id' => 19,
                'fixed_staff_ids' => [19, 20],
                'notes' => null,
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 100,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'notes' => null,
            ],
            [
                'name' => '直営店',
                'color' => 'indigo',
                'icon' => '🛒',
                'display_order' => 120,
                'lead_staff_id' => 28,
                'fixed_staff_ids' => [4, 28],
                'notes' => null,
            ],
            [
                'name' => '税務署ステージ',
                'color' => 'orange',
                'icon' => '🎤',
                'display_order' => 130,
                'lead_staff_id' => 13,
                'fixed_staff_ids' => [12, 13, 41, 44],
                'notes' => null,
            ],
            [
                'name' => '宿場くん/宿場ちゃん',
                'color' => 'red',
                'icon' => '🏃‍♂️',
                'display_order' => 140,
                'lead_staff_id' => 15,
                'fixed_staff_ids' => [10, 15, 23, 25, 32, 42],
                'notes' => null,
            ],
            [
                'name' => 'S&S',
                'color' => 'orange',
                'icon' => '🏛️',
                'display_order' => 170,
                'lead_staff_id' => 38,
                'fixed_staff_ids' => [38],
                'notes' => null,
            ],
            [
                'name' => '企業ブース',
                'color' => 'pink',
                'icon' => '🏢',
                'display_order' => 180,
                'lead_staff_id' => 24,
                'fixed_staff_ids' => [24],
                'notes' => null,
            ],
        ];

        $lanesDay4 = [];
        foreach ($lanesDay4Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $master = Lane::where('name', $laneItem['name'])->firstOrFail();

            $timetableLane = TimetableLane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay4->id,
                    'lane_id' => $master->id,
                ],
                [
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'display_order' => $laneItem['display_order'],
                    'notes' => $laneItem['notes'],
                ]
            );

            $timetableLane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay4[$laneItem['name']] = $timetableLane;
        }

        $tasksDay4 = [
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'S&S',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 38,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'キッズ村',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 5,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 15,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 39,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '道路清掃',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '直営店',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 4,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '税務署ステージ',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 12,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 13,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 41,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 44,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '宿場くん/宿場ちゃん',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 10,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 15,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 23,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 25,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 32,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 42,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'S&S',
                'task_type' => 'routine',
                'title' => '管理・イベント運営',
                'start_time' => '07:30:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => 'S&S建物内外の管理、主催イベントの運営を一任',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 38,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
                'title' => '事前準備',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => 'ゴミ箱準備
① 名札設置
② 袋設置
③ ブルーシート設置',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '貸出機材準備',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '仮設営',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'location' => null,
                'content' => '駐車場内にテント設置。放送設備設置など。',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'spot',
                'title' => '搬入対応',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => '長島駐車場（旧北川駐車場）',
                'content' => '企業ブース出展者の搬入対応・挨拶',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '受付・機材貸出',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'routine',
                'title' => '本陣業務',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => 'ござ市受付（出店料受領と出店許可証、領収書発行、貸出機材の案内など）',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'routine',
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
                    [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'routine',
                'title' => '巡回・見回り',
                'start_time' => '10:00:00',
                'end_time' => '17:00:00',
                'location' => '長島駐車場（旧北川駐車場）',
                'content' => '浜銀駐車場（1時間に1回程度）企業ブース中心に全体見回り',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'task_type' => 'spot',
                'title' => '掲揚',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => 'ちょうちんの紐を結ぶ人
梯子を抑える人
通行人に注意喚起する人',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 39,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'routine',
                'title' => '巡回',
                'start_time' => '10:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '出店違反の見回りなど',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '本設営',
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'location' => null,
                'content' => 'テントを通りに移動',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'routine',
                'title' => '本陣業務',
                'start_time' => '10:30:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '来場者対応、案内放送など',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'routine',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'routine',
                'title' => 'ゴミ箱チェック',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '① ゴミ圧縮、箸串回収
② ゴミ袋交換',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'routine',
                'title' => '巡回',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '定期巡回
① 私有地進入注意
② 路上のごみ拾い',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
                'title' => '撤去開始・完全撤去',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '稼働率の低いポイントから順次撤去。通行止め解除前には完全撤去。
2日目の設置ポイントを考慮して最寄りの拠点に機材を移動。',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 6,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 18,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 28,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
                'title' => '撤収作業',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '① ゴミ箱回収（段階的に）
② ゴミ袋収集',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'spot',
                'title' => '搬出対応',
                'start_time' => '17:00:00',
                'end_time' => '18:30:00',
                'location' => '長島駐車場（旧北川駐車場）',
                'content' => '企業ブース出展者の搬出対応',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 24,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'task_type' => 'spot',
                'title' => '降納',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 35,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 39,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '貸出機材回収',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '椅子・テーブル・テント・ウェイトの回収と夜間保管場所への移動',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => 'テント移動',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '駐車場内にテントを移動。業務継続',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'routine',
                'title' => '巡回',
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'location' => null,
                'content' => '通行止め解除前後の安全確保
① 解除前：ござ市出店者への注意呼びかけ
② 解除後：通行の安全注意呼びかけ',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 14,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '夜間保管準備',
                'start_time' => '18:00:00',
                'end_time' => '19:00:00',
                'location' => null,
                'content' => '2日目に使うものを盗難に注意して保管',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 26,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
        ];

        foreach ($tasksDay4 as $taskData) {
            $timetableLane = $lanesDay4[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay4->id,
                    'timetable_lane_id' => $timetableLane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'task_type' => $taskData['task_type'] ?? $resolveTaskType($taskData['title']),
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
                    if (Staff::where('id', $assignData['staff_id'])->exists()) {
                        $task->assignments()->updateOrCreate(
                            ['staff_id' => $assignData['staff_id']],
                            [
                                'role_description' => $assignData['role_description'],
                                'attendance_status' => $assignData['attendance_status'],
                            ]
                        );
                    }
                }
            } elseif (! array_key_exists('assignments', $taskData) && $timetableLane->lead_staff_id && Staff::where('id', $timetableLane->lead_staff_id)->exists()) {
                $task->assignments()->firstOrCreate(
                    ['staff_id' => $timetableLane->lead_staff_id],
                    [
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ]
                );
            }
        }

        // =========================================================================
        // 5. 第35回保土ケ谷宿場まつり（2日目） - 2027-10-11
        // =========================================================================
        $timetableDay5 = Timetable::updateOrCreate(
            ['target_date' => '2027-10-11'],
            [
                'name' => '第35回保土ケ谷宿場まつり（2日目）',
                'description' => null,
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'status' => 'draft',
                'road_periods' => [
                    [
                        'label' => '通行止め',
                        'status' => 'closed',
                        'end_time' => '18:00',
                        'start_time' => '10:00',
                    ],
                ],
                'festival_periods' => [
                    [
                        'label' => '開催中',
                        'status' => 'active',
                        'end_time' => '17:00',
                        'start_time' => '11:00',
                    ],
                ],
                'created_by' => $admin->id,
            ]
        );

        $lanesDay5Data = [
            [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'display_order' => 20,
                'lead_staff_id' => 33,
                'fixed_staff_ids' => [33],
                'notes' => null,
            ],
            [
                'name' => '給水設備',
                'color' => 'sky',
                'icon' => '🚰',
                'display_order' => 30,
                'lead_staff_id' => 6,
                'fixed_staff_ids' => [6, 37],
                'notes' => null,
            ],
            [
                'name' => '衛生',
                'color' => 'emerald',
                'icon' => '🚻',
                'display_order' => 40,
                'lead_staff_id' => 26,
                'fixed_staff_ids' => [26],
                'notes' => null,
            ],
            [
                'name' => '巡回',
                'color' => 'purple',
                'icon' => '🚨',
                'display_order' => 50,
                'lead_staff_id' => 43,
                'fixed_staff_ids' => [14, 43],
                'notes' => null,
            ],
            [
                'name' => 'キッズ村',
                'color' => 'pink',
                'icon' => '🎈',
                'display_order' => 60,
                'lead_staff_id' => 5,
                'fixed_staff_ids' => [5, 15],
                'notes' => null,
            ],
            [
                'name' => 'ちょうちん',
                'color' => 'red',
                'icon' => '🏮',
                'display_order' => 70,
                'lead_staff_id' => 35,
                'fixed_staff_ids' => [35, 39],
                'notes' => null,
            ],
            [
                'name' => 'ござ市',
                'color' => 'orange',
                'icon' => '🧺',
                'display_order' => 80,
                'lead_staff_id' => 22,
                'fixed_staff_ids' => [22, 35],
                'notes' => null,
            ],
            [
                'name' => '本陣',
                'color' => 'indigo',
                'icon' => '🏛️',
                'display_order' => 90,
                'lead_staff_id' => 19,
                'fixed_staff_ids' => [19, 20],
                'notes' => null,
            ],
            [
                'name' => '道路清掃',
                'color' => 'teal',
                'icon' => '🧹',
                'display_order' => 100,
                'lead_staff_id' => null,
                'fixed_staff_ids' => [],
                'notes' => null,
            ],
            [
                'name' => '直営店',
                'color' => 'indigo',
                'icon' => '🛒',
                'display_order' => 120,
                'lead_staff_id' => 28,
                'fixed_staff_ids' => [4, 28],
                'notes' => null,
            ],
            [
                'name' => '税務署ステージ',
                'color' => 'orange',
                'icon' => '🎤',
                'display_order' => 130,
                'lead_staff_id' => 13,
                'fixed_staff_ids' => [12, 13, 41, 44],
                'notes' => null,
            ],
            [
                'name' => '宿場くん/宿場ちゃん',
                'color' => 'red',
                'icon' => '🏃‍♂️',
                'display_order' => 140,
                'lead_staff_id' => 15,
                'fixed_staff_ids' => [10, 15, 23, 25, 32, 42],
                'notes' => null,
            ],
            [
                'name' => 'S&S',
                'color' => 'orange',
                'icon' => '🏛️',
                'display_order' => 170,
                'lead_staff_id' => 38,
                'fixed_staff_ids' => [38],
                'notes' => null,
            ],
            [
                'name' => '企業ブース',
                'color' => 'pink',
                'icon' => '🏢',
                'display_order' => 180,
                'lead_staff_id' => 24,
                'fixed_staff_ids' => [24],
                'notes' => null,
            ],
            [
                'name' => 'ステカン',
                'color' => 'yellow',
                'icon' => '🪧',
                'display_order' => 190,
                'lead_staff_id' => 8,
                'fixed_staff_ids' => [8, 12, 21],
                'notes' => null,
            ],
        ];

        $lanesDay5 = [];
        foreach ($lanesDay5Data as $laneItem) {
            if (isset($laneItem['lead_staff_id']) && ! Staff::where('id', $laneItem['lead_staff_id'])->exists()) {
                $laneItem['lead_staff_id'] = null;
            }

            $master = Lane::where('name', $laneItem['name'])->firstOrFail();

            $timetableLane = TimetableLane::updateOrCreate(
                [
                    'timetable_id' => $timetableDay5->id,
                    'lane_id' => $master->id,
                ],
                [
                    'lead_staff_id' => $laneItem['lead_staff_id'],
                    'display_order' => $laneItem['display_order'],
                    'notes' => $laneItem['notes'],
                ]
            );

            $timetableLane->fixedStaff()->sync($laneItem['fixed_staff_ids']);

            $lanesDay5[$laneItem['name']] = $timetableLane;
        }

        $tasksDay5 = [
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => 'S&S',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => 'キッズ村',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '道路清掃',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '直営店',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '税務署ステージ',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '宿場くん/宿場ちゃん',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'spot',
                'title' => '全体ミーティング',
                'start_time' => '07:00:00',
                'end_time' => '07:30:00',
                'location' => '本陣',
                'content' => null,
                'required_people_count' => 0,
                'is_at_least' => false,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 26,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 14,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'S&S',
                'task_type' => 'routine',
                'title' => '管理・イベント運営',
                'start_time' => '07:30:00',
                'end_time' => '18:30:00',
                'location' => null,
                'content' => 'S&S建物内外の管理、主催イベントの運営を一任',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 38,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '班ミーティング',
                'start_time' => '07:30:00',
                'end_time' => '08:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
                'title' => '事前準備',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => 'ゴミ箱準備
① 名札設置
② 袋設置
③ ブルーシート設置',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 16,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 27,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '貸出機材準備',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '仮設営',
                'start_time' => '08:00:00',
                'end_time' => '09:00:00',
                'location' => null,
                'content' => '駐車場内にテント設置。放送設備設置など。',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 14,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'spot',
                'title' => '搬入対応',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => '長島駐車場（旧北川駐車場）',
                'content' => '企業ブース出展者の搬入対応・挨拶',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 24,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '受付・機材貸出',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 22,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'routine',
                'title' => '本陣業務',
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'location' => null,
                'content' => 'ござ市受付（出店料受領と出店許可証、領収書発行、貸出機材の案内など）',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'routine',
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
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 16,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 27,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'routine',
                'title' => '巡回・見回り',
                'start_time' => '10:00:00',
                'end_time' => '17:00:00',
                'location' => '長島駐車場（旧北川駐車場）',
                'content' => '浜銀駐車場（1時間に1回程度）企業ブース中心に全体見回り',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 24,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'task_type' => 'spot',
                'title' => '掲揚',
                'start_time' => '10:00:00',
                'end_time' => '11:00:00',
                'location' => null,
                'content' => 'ちょうちんの紐を結ぶ人
梯子を抑える人
通行人に注意喚起する人',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 39,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'routine',
                'title' => '巡回',
                'start_time' => '10:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '出店違反の見回りなど',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '本設営',
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'location' => null,
                'content' => 'テントを通りに移動',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'routine',
                'title' => '本陣業務',
                'start_time' => '10:30:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '来場者対応、案内放送など',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 20,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'routine',
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
                    [
                        'staff_id' => 6,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'routine',
                'title' => 'ゴミ箱チェック',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '① ゴミ圧縮、箸串回収
② ゴミ袋交換',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 16,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 27,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'routine',
                'title' => '巡回',
                'start_time' => '11:00:00',
                'end_time' => '17:00:00',
                'location' => null,
                'content' => '定期巡回
① 私有地進入注意
② 路上のごみ拾い',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 14,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '給水設備',
                'task_type' => 'spot',
                'title' => '撤去開始・完全撤去',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '稼働率の低いポイントから順次撤去。通行止め解除前には完全撤去。
2日目の設置ポイントを考慮して最寄りの拠点に機材を移動。',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 6,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 37,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
                'title' => '撤収作業',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '① ゴミ箱回収（段階的に）
② ゴミ袋収集',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 16,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 27,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '企業ブース',
                'task_type' => 'spot',
                'title' => '搬出対応',
                'start_time' => '17:00:00',
                'end_time' => '18:30:00',
                'location' => '長島駐車場（旧北川駐車場）',
                'content' => '企業ブース出展者の搬出対応',
                'required_people_count' => 1,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 24,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ちょうちん',
                'task_type' => 'spot',
                'title' => '降納',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => null,
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ござ市',
                'task_type' => 'spot',
                'title' => '貸出機材回収',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '椅子・テーブル・テント・ウェイトの回収と夜間保管場所への移動',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 22,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 35,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => 'テント移動',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '駐車場内にテントを移動。業務継続',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'ステカン',
                'task_type' => 'spot',
                'title' => '撤収',
                'start_time' => '17:00:00',
                'end_time' => '18:00:00',
                'location' => null,
                'content' => '外回り撤去（要車両）
内回り撤去
ステカンはコミュニティに保管、ポスターは剥がして廃棄（プラダンは残したまま）',
                'required_people_count' => 4,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 8,
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 12,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 21,
                        'role_description' => '固定スタッフ',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '巡回',
                'task_type' => 'routine',
                'title' => '巡回',
                'start_time' => '17:30:00',
                'end_time' => '19:30:00',
                'location' => null,
                'content' => '通行止め解除前後の安全確保
① 解除前：ござ市出店者への注意呼びかけ
② 解除後：通行の安全注意呼びかけ',
                'required_people_count' => 3,
                'is_at_least' => true,
                'is_critical' => true,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 14,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 43,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => 'カラーコーン',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 33,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '本陣',
                'task_type' => 'spot',
                'title' => '夜間保管準備',
                'start_time' => '18:00:00',
                'end_time' => '19:00:00',
                'location' => null,
                'content' => '2日目に使うものを盗難に注意して保管',
                'required_people_count' => 2,
                'is_at_least' => true,
                'is_critical' => false,
                'status' => 'not_started',
                'assignments' => [
                    [
                        'staff_id' => 19,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
            [
                'lane_name' => '衛生',
                'task_type' => 'spot',
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
                    [
                        'staff_id' => 16,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 26,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                    [
                        'staff_id' => 27,
                        'role_description' => '一般担当',
                        'attendance_status' => 'assigned',
                    ],
                ],
            ],
        ];

        foreach ($tasksDay5 as $taskData) {
            $timetableLane = $lanesDay5[$taskData['lane_name']];

            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay5->id,
                    'timetable_lane_id' => $timetableLane->id,
                    'title' => $taskData['title'],
                    'start_time' => $taskData['start_time'],
                ],
                [
                    'task_type' => $taskData['task_type'] ?? $resolveTaskType($taskData['title']),
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
                    if (Staff::where('id', $assignData['staff_id'])->exists()) {
                        $task->assignments()->updateOrCreate(
                            ['staff_id' => $assignData['staff_id']],
                            [
                                'role_description' => $assignData['role_description'],
                                'attendance_status' => $assignData['attendance_status'],
                            ]
                        );
                    }
                }
            } elseif (! array_key_exists('assignments', $taskData) && $timetableLane->lead_staff_id && Staff::where('id', $timetableLane->lead_staff_id)->exists()) {
                $task->assignments()->firstOrCreate(
                    ['staff_id' => $timetableLane->lead_staff_id],
                    [
                        'role_description' => '班長',
                        'attendance_status' => 'assigned',
                    ]
                );
            }
        }

        // =========================================================================
        // レーン間支援要請（LaneRequest）のシードデータ
        // =========================================================================
        $day1ColorConeLane = $lanesDay1['カラーコーン'] ?? null;
        $day1HygieneLane = $lanesDay1['衛生'] ?? null;
        $firstTask = $timetableDay1->tasks()->first();

        if ($day1ColorConeLane) {
            LaneRequest::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'timetable_lane_id' => $day1ColorConeLane->id,
                    'request_type' => 'staffing',
                    'equipment_name' => null,
                ],
                [
                    'timetable_task_id' => $firstTask?->id,
                    'requested_by_staff_id' => $day1ColorConeLane->lead_staff_id,
                    'requested_count' => 2,
                    'reason' => '東口コーン設置の補助要請（通行止め直後の集中配置）',
                    'status' => 'resolved',
                    'resolved_by_user_id' => $admin->id,
                    'resolved_at' => '2026-11-22 03:02:20',
                ]
            );

            LaneRequest::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'timetable_lane_id' => $day1ColorConeLane->id,
                    'request_type' => 'equipment',
                    'equipment_name' => 'トランシーバー',
                ],
                [
                    'timetable_task_id' => $firstTask?->id,
                    'requested_by_staff_id' => $day1ColorConeLane->lead_staff_id,
                    'requested_count' => 3,
                    'reason' => '東口と西口の連絡用に追加手配希望',
                    'status' => 'resolved',
                    'resolved_by_user_id' => $admin->id,
                    'resolved_at' => '2026-11-22 03:02:08',
                ]
            );
        }

        if ($day1HygieneLane) {
            LaneRequest::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'timetable_lane_id' => $day1HygieneLane->id,
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
