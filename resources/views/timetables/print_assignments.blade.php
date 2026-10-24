<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/print-assignments.css') }}">
    <script src="{{ asset('js/print-helper.js') }}" defer></script>
</head>
<body>

    <!-- 操作バー -->
    <div class="no-print">
        <div>
            <a href="{{ route('timetables.show', $timetable) }}" class="btn-back">← タイムテーブル詳細に戻る</a>
            <h2 class="page-title">{{ $title }}</h2>
        </div>
        <button type="button" data-action="print" class="btn-print">
            🖨️ 今すぐ印刷する (A4横向き)
        </button>
    </div>

    <!-- 印刷用紙シート -->
    <div class="sheet">
        <!-- 帳票ヘッダー -->
        <div class="sheet-header">
            <div class="header-title-area">
                <h1>まつり運行 人員配置一覧表</h1>
                <div class="header-meta">
                    運行枠: <strong>{{ $timetable->name }}</strong> ｜
                    運行日: <strong>{{ $timetable->target_date?->format('Y年m月d日') }}</strong> ｜
                    出力日時: {{ now()->format('Y/m/d H:i') }}
                </div>
            </div>
            <div class="header-stats">
                <span>全体人員状況:</span>
                <span class="stat-badge">
                    配置: <strong>{{ $totalAssigned }}名</strong> / 必要: <strong>{{ $totalRequired }}名</strong>
                </span>
            </div>
        </div>

        <!-- 担当班（レーン）別リスト -->
        @forelse($timetable->lanes as $lane)
        <div class="lane-section">
            <div class="lane-header">
                <span>🏷️ {{ $lane->name }}</span>
                <span class="lane-lead">
                    班リーダー: <strong>{{ $lane->leadStaff?->name ?: '未指名' }}</strong>
                </span>
            </div>

            <table class="assignment-table">
                <thead>
                    <tr>
                        <th class="col-time">時間帯</th>
                        <th class="col-task">タスク名・場所</th>
                        <th class="col-count">必要 / 配置</th>
                        <th class="col-staff">配置スタッフ (出欠確認 [ ])</th>
                        <th class="col-notes">特記事項</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lane->tasks as $task)
                    <tr>
                        <td class="col-time">
                            {{ substr($task->start_time, 0, 5) }} 〜 {{ substr($task->end_time, 0, 5) }}
                        </td>
                        <td class="col-task">
                            <strong class="task-title">{{ $task->title }}</strong>
                            @if($task->is_critical)
                            <span class="badge-critical">[最優先]</span>
                            @endif
                            @if($task->location)
                            <div class="task-location">
                                📍 {{ $task->location }}
                            </div>
                            @endif
                        </td>
                        <td class="col-count">
                            <span class="{{ $task->is_satisfied ? 'text-satisfied' : 'text-shortage' }}">
                                {{ $task->assigned_count }} / {{ $task->required_people_count }}
                            </span>
                        </td>
                        <td class="col-staff">
                            @forelse($task->assignments as $assignment)
                            <span class="staff-chip">
                                <span class="check-box"></span>
                                <strong>{{ $assignment->staff?->name }}</strong>
                                @if($assignment->role_description)
                                <span class="role-label">({{ $assignment->role_description }})</span>
                                @endif
                            </span>
                            @empty
                            <span class="unassigned-warning">
                                ⚠️ 未アサイン ({{ $task->required_people_count }}名不足)
                            </span>
                            @endforelse
                        </td>
                        <td class="col-notes">
                            {{ $task->description ?: '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-cell">
                            この担当班に登録された作業タスクはありません。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @empty
        <div class="empty-notice">
            担当班が登録されていません。
        </div>
        @endforelse
    </div>

</body>
</html>
