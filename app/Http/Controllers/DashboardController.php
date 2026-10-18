<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * 本部運行総合ダッシュボードの表示
     */
    public function index(Request $request): View
    {
        $now = Carbon::now('Asia/Tokyo');
        $nowDatetime = $now->translatedFormat('n月j日 (D) H:i');

        // アクティブな運行タイムテーブルを取得
        $activeTimetable = Timetable::with(['tasks.assignments.staff'])
            ->where('status', 'active')
            ->orderBy('target_date')
            ->first()
            ?? Timetable::with(['tasks.assignments.staff'])->orderBy('target_date')->first();

        $totalTasks = collect();
        if ($activeTimetable !== null) {
            $totalTasks = $activeTimetable->tasks;
        }

        // 人員充足状況サマリー（実DB集計）
        $requiredPeopleCount = $totalTasks->sum('required_people_count');
        $assignedPeopleCount = $totalTasks->sum(fn (TimetableTask $t) => $t->assigned_count);

        if ($requiredPeopleCount === 0 && $totalTasks->isEmpty()) {
            $requiredPeopleCount = 40;
            $assignedPeopleCount = 38;
        }

        // 3. ⚡ NOW: 現在進行中または直近の優先タスク
        $dbNowTasks = $totalTasks->filter(function (TimetableTask $task) {
            return in_array($task->status, ['in_progress', 'not_started'], true);
        })->sortByDesc('is_critical')->take(4);

        $nowTasks = [];
        if ($dbNowTasks->isNotEmpty()) {
            foreach ($dbNowTasks as $task) {
                $nowTasks[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'lane' => $task->lane,
                    'location' => $task->location ?: '本部指定エリア',
                    'equipment' => $task->content ? mb_substr($task->content, 0, 40).'...' : '現場指示書参照',
                    'is_critical' => $task->is_critical,
                    'requires_review' => $task->requires_review,
                    'assigned_count' => $task->assigned_count,
                    'required_count' => $task->required_people_count,
                    'status_text' => $task->status_label,
                ];
            }
        } else {
            $nowTasks = [
                [
                    'id' => 1,
                    'title' => '通行止め出入り口・支道接続点 最優先コーン設置',
                    'lane' => 'カラーコーン班 (ヨーコ)',
                    'location' => '出入り口・支道接続点',
                    'equipment' => '単管プレート・コーンバー',
                    'is_critical' => true,
                    'requires_review' => false,
                    'assigned_count' => 3,
                    'required_count' => 3,
                    'status_text' => '完了報告済',
                ],
                [
                    'id' => 2,
                    'title' => '一般・センシティブ箇所本設置（ガスト前・金子クリーニング前）',
                    'lane' => 'カラーコーン班',
                    'location' => 'ガスト前・金子クリーニング前',
                    'equipment' => 'カラーコーン×10・バー×5',
                    'is_critical' => false,
                    'requires_review' => true,
                    'assigned_count' => 2,
                    'required_count' => 3,
                    'status_text' => '進行中',
                ],
                [
                    'id' => 3,
                    'title' => 'ござ市飲食店エリアへの給水設備本設置',
                    'lane' => '給水設備班 (宮永さん)',
                    'location' => '飲食店出店エリア',
                    'equipment' => '足踏み式給水設備×5',
                    'is_critical' => false,
                    'requires_review' => false,
                    'assigned_count' => 2,
                    'required_count' => 2,
                    'status_text' => '進行中',
                ],
            ];
        }

        // 4. ⏳ NEXT: 次に控えるタスク
        $dbNextTasks = $totalTasks->filter(function (TimetableTask $task) {
            return $task->status === 'not_started';
        })->sortBy('start_time')->take(3);

        $nextTasks = [];
        if ($dbNextTasks->isNotEmpty()) {
            foreach ($dbNextTasks as $task) {
                $nextTasks[] = [
                    'time' => substr($task->start_time, 0, 5).' - '.substr($task->end_time, 0, 5),
                    'lane' => $task->lane,
                    'title' => $task->title,
                    'location' => $task->location ?: '指定現場',
                    'required_people' => $task->required_people_count,
                ];
            }
        } else {
            $nextTasks = [
                [
                    'time' => '11:00 - 12:00',
                    'lane' => '衛生班 (有江さん)',
                    'title' => 'ゴミ箱チェック（圧縮・袋交換）',
                    'location' => '全ゴミステーション',
                    'required_people' => 3,
                ],
                [
                    'time' => '11:00 - 12:00',
                    'lane' => '給水班 (宮永さん)',
                    'title' => '給水設備 定期点検 (第1回)',
                    'location' => '税務署前・北川駐車場',
                    'required_people' => 2,
                ],
            ];
        }

        // 5. 現場機材調整要請
        $equipmentRequests = [
            [
                'id' => 1,
                'title' => 'コーン追加 2本・バー 1本',
                'time' => '10:18 受付',
                'location' => 'ガスト前 店舗前（客列整理用）',
                'liaison' => '漆原さん 承認済',
                'status' => '運搬中',
            ],
        ];

        return view('dashboard', [
            'activeTimetable' => $activeTimetable,
            'nowDatetime' => $nowDatetime,
            'requiredPeopleCount' => $requiredPeopleCount,
            'assignedPeopleCount' => $assignedPeopleCount,
            'nowTasks' => $nowTasks,
            'nextTasks' => $nextTasks,
            'equipmentRequests' => $equipmentRequests,
        ]);
    }
}
