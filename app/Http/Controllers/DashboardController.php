<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * 本部運行総合ダッシュボードの表示
     */
    public function index(Request $request): View
    {
        // 1. 現在の日時情報
        $nowDatetime = '10月17日 (土) 10:25';

        // 2. 人員充足状況サマリー
        $requiredPeopleCount = 40;
        $assignedPeopleCount = 38;

        // 3. ⚡ NOW: 現在進行中のタスク (10:00〜10:45)
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

        // 4. ⏳ NEXT: 次の30分〜1時間で開始するタスク (11:00〜)
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
            'nowDatetime' => $nowDatetime,
            'requiredPeopleCount' => $requiredPeopleCount,
            'assignedPeopleCount' => $assignedPeopleCount,
            'nowTasks' => $nowTasks,
            'nextTasks' => $nextTasks,
            'equipmentRequests' => $equipmentRequests,
        ]);
    }
}
