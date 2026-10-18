<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TimetableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        // 1. Day 1 タイムテーブル
        $timetableDay1 = Timetable::updateOrCreate(
            ['target_date' => '2026-10-17'],
            [
                'name' => '1日目 (Day 1) 本祭運行・パレード',
                'description' => '10:00〜17:30の車両通行止め規制に伴うカラーコーン設置、ござ市給水、パレード運行誘導。',
                'start_time' => '08:00:00',
                'end_time' => '18:30:00',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // 2. Day 2 タイムテーブル
        $timetableDay2 = Timetable::updateOrCreate(
            ['target_date' => '2026-10-18'],
            [
                'name' => '2日目 (Day 2) 例大祭神輿渡御・撤収',
                'description' => '午前中の式典参列誘導および午後の神輿渡御、規制解除後の機材回収・清掃。',
                'start_time' => '08:30:00',
                'end_time' => '19:00:00',
                'status' => 'draft',
                'created_by' => $admin->id,
            ]
        );

        // Day 1 のタスク群
        $tasksDataDay1 = [
            [
                'title' => '通行止め出入り口・支道接続点 最優先コーン設置',
                'content' => '10:00通行止め開始と同時に出入り口および支道接続点へ単管プレート・コーンバーを本設置する。',
                'lane' => 'カラーコーン班',
                'location' => '出入り口・支道接続点',
                'start_time' => '10:00:00',
                'end_time' => '10:45:00',
                'required_people_count' => 3,
                'is_at_least' => false,
                'description' => '最優先作業。設置完了次第、無線で本部へ報告必須。',
                'is_critical' => true,
                'requires_review' => false,
                'status' => 'completed',
            ],
            [
                'title' => '一般・センシティブ箇所本設置（ガスト前・金子クリーニング前）',
                'content' => '店舗営業者と事前協調しながらカラーコーン10本・バー5本を配置し、一般車両の誤進入を防ぐ。',
                'lane' => 'カラーコーン班',
                'location' => 'ガスト前・金子クリーニング前',
                'start_time' => '10:00:00',
                'end_time' => '11:30:00',
                'required_people_count' => 3,
                'is_at_least' => true, // 3名以上
                'description' => '店舗出入り口の動線に配慮し適宜微調整。',
                'is_critical' => false,
                'requires_review' => true,
                'status' => 'in_progress',
            ],
            [
                'title' => 'ござ市飲食店エリアへの給水設備本設置',
                'content' => '飲食店出店エリア5箇所へ足踏み式給水設備を運搬・給水ホース接続・動作確認。',
                'lane' => '給水設備班',
                'location' => '飲食店出店エリア',
                'start_time' => '10:15:00',
                'end_time' => '11:45:00',
                'required_people_count' => 2,
                'is_at_least' => false,
                'description' => '水漏れがないか確認し、蛇口拭き上げ用アルコールを設置。',
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'in_progress',
            ],
            [
                'title' => 'ゴミ箱チェック（圧縮・袋交換）',
                'content' => '全4箇所のゴミステーションを巡回し、溢れそうなゴミ袋を回収・新規袋セット。',
                'lane' => '衛生班',
                'location' => '全ゴミステーション',
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'required_people_count' => 3,
                'is_at_least' => true, // 3名以上
                'description' => '可燃・不燃・ペットボトルの分別が崩れている場合は整頓。',
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'title' => '給水設備 定期点検 (第1回)',
                'content' => '税務署前・北川駐車場の給水タンク残量および水圧チェック。',
                'lane' => '給水設備班',
                'location' => '税務署前・北川駐車場',
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'required_people_count' => 2,
                'is_at_least' => false,
                'description' => '残量1/3以下の場合は本部給水車へ補給要請。',
                'is_critical' => false,
                'requires_review' => false,
                'status' => 'not_started',
            ],
            [
                'title' => 'パレード運行沿道 観客誘導・安全確保',
                'content' => '神輿・山車通過時の沿道観客の車道はみ出し防止と緊急車両導線確保。',
                'lane' => '誘導・警備班',
                'location' => 'メイン通り特設会場一帯',
                'start_time' => '13:00:00',
                'end_time' => '15:30:00',
                'required_people_count' => 4,
                'is_at_least' => true, // 4名以上
                'description' => 'トラメガ携行。小さなお子様の飛び出しに厳重注意。',
                'is_critical' => true,
                'requires_review' => false,
                'status' => 'not_started',
            ],
        ];

        $staffList = Staff::all();

        foreach ($tasksDataDay1 as $index => $taskData) {
            $task = TimetableTask::updateOrCreate(
                [
                    'timetable_id' => $timetableDay1->id,
                    'title' => $taskData['title'],
                ],
                $taskData
            );

            // スタッフをアサイン（タスクごとに数名割り当て）
            if ($staffList->isNotEmpty()) {
                if ($index === 0) { // 3名割当 (必要3 -> 充足)
                    $this->assignStaff($task, $staffList->slice(0, 3));
                } elseif ($index === 1) { // 2名割当 (必要3名以上 -> 不足1名)
                    $this->assignStaff($task, $staffList->slice(3, 2));
                } elseif ($index === 2) { // 2名割当 (必要2 -> 充足)
                    $this->assignStaff($task, $staffList->slice(5, 2));
                } elseif ($index === 3) { // 2名割当 (必要3名以上 -> 不足1名)
                    $this->assignStaff($task, $staffList->slice(7, 2));
                } elseif ($index === 4) { // 2名割当 (必要2 -> 充足)
                    $this->assignStaff($task, $staffList->slice(9, 2));
                } elseif ($index === 5) { // 4名割当 (必要4名以上 -> 充足)
                    $this->assignStaff($task, $staffList->slice(0, 4));
                }
            }
        }
    }

    /**
     * スタッフの割り当てヘルパー
     *
     * @param  Collection<int, Staff>  $staffMembers
     */
    private function assignStaff(TimetableTask $task, $staffMembers): void
    {
        foreach ($staffMembers as $staff) {
            TaskStaffAssignment::updateOrCreate(
                [
                    'timetable_task_id' => $task->id,
                    'staff_id' => $staff->id,
                ],
                [
                    'attendance_status' => 'assigned',
                    'role_description' => '担当スタッフ',
                ]
            );
        }
    }
}
