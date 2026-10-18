<?php

namespace Database\Seeders;

use App\Models\AnnualSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnualScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $schedules = [
            [
                'target_year' => 2026,
                'title' => '第1回 実行委員会・基本運営方針策定',
                'category' => 'committee',
                'start_date' => '2026-04-15',
                'end_date' => null,
                'location' => '市民交流センター 3F大会議室',
                'status' => 'completed',
                'description' => '今年度の基本方針・予算案および各部会の役割分担の決定。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '警察・消防・行政合同 道路使用事前協議',
                'category' => 'permit',
                'start_date' => '2026-06-10',
                'end_date' => null,
                'location' => '中央警察署 交通規制課',
                'status' => 'completed',
                'description' => '運行ルートにおける通行規制時間帯（10:00〜17:30）および緊急車両動線の事前すり合わせ。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => 'ボランティアスタッフ・運行補助員 募集期間',
                'category' => 'briefing',
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-31',
                'location' => 'オンライン募集 / 市役所案内窓口',
                'status' => 'completed',
                'description' => '誘導係・美化係・給水係の募集（定員40名）。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '参加団体・露店出店者 安全衛生説明会',
                'category' => 'briefing',
                'start_date' => '2026-08-20',
                'end_date' => null,
                'location' => '市民会館 大ホール',
                'status' => 'completed',
                'description' => '火気使用基準、給水設備利用ルール、ゴミ分別・搬出時間の周知。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '道路使用許可申請書 本提出',
                'category' => 'permit',
                'start_date' => '2026-09-05',
                'end_date' => null,
                'location' => '中央警察署',
                'status' => 'completed',
                'description' => '本祭2日間の道路使用許可申請手続き完了。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '本部運行機材・通信機器 最終点検',
                'category' => 'operation',
                'start_date' => '2026-10-01',
                'end_date' => null,
                'location' => 'まつり運行本部 倉庫',
                'status' => 'completed',
                'description' => 'トランシーバー15台充電点検、カラーコーン50本・コーンバー25本状態確認。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '前日会場設営・通行止め予告看板設置・リハーサル',
                'category' => 'operation',
                'start_date' => '2026-10-16',
                'end_date' => null,
                'location' => 'まつり運行メイン通り一帯',
                'status' => 'completed',
                'description' => '予告看板の配置、本部テント設営、救護所導線確認。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => 'まつり本番運行 1日目',
                'category' => 'operation',
                'start_date' => '2026-10-17',
                'end_date' => null,
                'location' => '市内特設メイン会場',
                'status' => 'in_progress',
                'description' => '10:00〜車両通行止め開始。午前・午後パレード運行。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => 'まつり本番運行 2日目・例大祭神輿渡御',
                'category' => 'ceremony',
                'start_date' => '2026-10-18',
                'end_date' => null,
                'location' => '神社境内 〜 メイン通り',
                'status' => 'planned',
                'description' => '本祭式典、神輿渡御、閉会セレモニー。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '機材完全撤収・主要道路清掃・原状回復',
                'category' => 'operation',
                'start_date' => '2026-10-19',
                'end_date' => null,
                'location' => '運行エリア全域',
                'status' => 'planned',
                'description' => 'コーン・看板回収、ゴミ集積場撤収、清掃点検。',
                'created_by' => $admin->id,
            ],
            [
                'target_year' => 2026,
                'title' => '第2回 総括反省会・収支決算報告会',
                'category' => 'committee',
                'start_date' => '2026-11-15',
                'end_date' => null,
                'location' => '市民交流センター 3F大会議室',
                'status' => 'planned',
                'description' => '各部会からの運行報告、収支決算確認、次年度への申し送り事項まとめ。',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($schedules as $schedule) {
            AnnualSchedule::updateOrCreate(
                [
                    'target_year' => $schedule['target_year'],
                    'title' => $schedule['title'],
                ],
                $schedule
            );
        }
    }
}
