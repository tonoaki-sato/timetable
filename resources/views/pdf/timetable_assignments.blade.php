<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
        }
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .header-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
        }
        .header-meta {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        .header-stats {
            text-align: right;
            font-size: 9.5px;
            color: #334155;
            vertical-align: bottom;
        }
        .stat-badge {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .lane-section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .lane-header-table {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-bottom: none;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 11px;
            color: #1e293b;
        }
        .lane-lead {
            text-align: right;
            font-size: 9.5px;
            color: #475569;
        }
        .assignment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .assignment-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: left;
            font-weight: bold;
            color: #334155;
        }
        .assignment-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: top;
        }
        .col-time {
            width: 11%;
            white-space: nowrap;
        }
        .col-task {
            width: 25%;
        }
        .col-count {
            width: 10%;
            text-align: center;
            white-space: nowrap;
        }
        .col-staff {
            width: 38%;
        }
        .col-notes {
            width: 16%;
            color: #64748b;
            font-size: 8.5px;
        }
        .task-title {
            font-weight: bold;
            color: #0f172a;
        }
        .task-location {
            font-size: 8px;
            color: #64748b;
            margin-top: 1px;
        }
        .staff-tag {
            display: inline-block;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 1px 4px;
            margin: 1px 2px 1px 0;
            font-size: 8.5px;
            white-space: nowrap;
        }
        .staff-role {
            color: #64748b;
            font-size: 7.5px;
        }
        .count-shortage {
            color: #dc2626;
            font-weight: bold;
        }
        .count-ok {
            color: #16a34a;
            font-weight: bold;
        }
        .empty-task-row {
            text-align: center;
            color: #94a3b8;
            padding: 10px !important;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- ヘッダー -->
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">まつり運行 人員配置一覧表</div>
                <div class="header-meta">
                    運行枠: <strong>{{ $timetable->name }}</strong> ｜
                    運行日: <strong>{{ $timetable->target_date?->format('Y年m月d日') }}</strong> ｜
                    出力日時: {{ now()->format('Y/m/d H:i') }}
                </div>
            </td>
            <td class="header-stats">
                全体人員状況:
                <span class="stat-badge">
                    配置: <strong>{{ $totalAssigned }}名</strong> / 必要: <strong>{{ $totalRequired }}名</strong>
                </span>
            </td>
        </tr>
    </table>

    <!-- 担当班別セクション -->
    @forelse($timetable->lanes as $lane)
    <div class="lane-section">
        <table class="lane-header-table">
            <tr>
                <td>🏷️ {{ $lane->name }}</td>
                <td class="lane-lead">
                    班リーダー: <strong>{{ $lane->leadStaff?->name ?: '未指名' }}</strong>
                </td>
            </tr>
        </table>

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
                        {{ substr($task->start_time, 0, 5) }}〜{{ substr($task->end_time, 0, 5) }}
                    </td>
                    <td class="col-task">
                        <div class="task-title">{{ $task->title }}</div>
                        @if($task->location)
                        <div class="task-location">📍 {{ $task->location }}</div>
                        @endif
                    </td>
                    <td class="col-count">
                        <span class="{{ $task->assigned_count < $task->required_people_count ? 'count-shortage' : 'count-ok' }}">
                            {{ $task->required_people_count }}名 / {{ $task->assigned_count }}名
                        </span>
                    </td>
                    <td class="col-staff">
                        @forelse($task->assignments as $assignment)
                        <span class="staff-tag">
                            [ ] {{ $assignment->staff->name }}
                            @if($assignment->role_description)
                            <span class="staff-role">({{ $assignment->role_description }})</span>
                            @endif
                        </span>
                        @empty
                        <span style="color: #94a3b8; font-size: 8px;">（配置スタッフなし）</span>
                        @endforelse
                    </td>
                    <td class="col-notes">
                        {{ $task->notes ?: 'ー' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-task-row">この班に登録された作業タスクはありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @empty
    <div style="text-align: center; padding: 40px; color: #64748b;">
        <p>担当班が未登録です。「担当班管理」より班および作業タスクを登録してください。</p>
    </div>
    @endforelse
</body>
</html>

