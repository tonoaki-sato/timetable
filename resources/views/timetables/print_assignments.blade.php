<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Hiragino Sans", "Noto Sans JP", sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 16px;
            font-size: 11px;
        }

        /* 画面用コントロールバー */
        .no-print {
            max-width: 1000px;
            margin: 0 auto 16px auto;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-print {
            background-color: #0284c7;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-print:hover {
            background-color: #0369a1;
        }
        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }

        /* A4横向き印刷シート */
        .sheet {
            max-width: 280mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 10mm;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        /* 帳票ヘッダー */
        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-title-area h1 {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.3px;
        }
        .header-meta {
            font-size: 11px;
            color: #475569;
            margin-top: 2px;
        }
        .header-stats {
            text-align: right;
            font-size: 11px;
            font-weight: 600;
        }
        .stat-badge {
            display: inline-block;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 6px;
            border: 1px solid #cbd5e1;
        }

        /* レーン（班）セクション */
        .lane-section {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .lane-header {
            background: #1e293b;
            color: #ffffff;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px 4px 0 0;
        }
        .lane-lead {
            font-size: 10px;
            font-weight: normal;
            color: #94a3b8;
        }

        /* テーブル */
        table.assignment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin-bottom: 8px;
        }
        table.assignment-table th,
        table.assignment-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            vertical-align: top;
        }
        table.assignment-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }
        .col-time { width: 95px; white-space: nowrap; font-family: monospace; font-weight: 700; }
        .col-task { width: 220px; }
        .col-count { width: 70px; text-align: center; }
        .col-staff { width: auto; }
        .col-notes { width: 140px; color: #64748b; font-size: 9.5px; }
        .text-satisfied { color: #059669; font-weight: bold; }
        .text-shortage { color: #d97706; font-weight: bold; }

        .staff-chip {
            display: inline-block;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 6px;
            margin: 2px 4px 2px 0;
            font-size: 10px;
            white-space: nowrap;
        }
        .check-box {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #64748b;
            border-radius: 2px;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* 印刷スタイル */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .sheet {
                max-width: 100%;
                box-shadow: none;
                padding: 0;
            }
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
            .lane-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- 操作バー -->
    <div class="no-print">
        <div>
            <a href="{{ route('timetables.show', $timetable) }}" class="btn-back">← タイムテーブル詳細に戻る</a>
            <h2 style="font-size: 15px; margin-top: 3px; font-weight: bold;">{{ $title }}</h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print">
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
                            <strong style="color: #0f172a;">{{ $task->title }}</strong>
                            @if($task->is_critical)
                            <span style="color: #e11d48; font-weight: bold; font-size: 9px; margin-left: 2px;">[最優先]</span>
                            @endif
                            @if($task->location)
                            <div style="font-size: 9px; color: #64748b; margin-top: 1px;">
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
                                <span style="color: #64748b; font-size: 9px;">({{ $assignment->role_description }})</span>
                                @endif
                            </span>
                            @empty
                            <span style="color: #d97706; font-style: italic; font-size: 9.5px;">
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
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 10px;">
                            この担当班に登録された作業タスクはありません。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @empty
        <div style="text-align: center; padding: 40px; color: #94a3b8;">
            担当班が登録されていません。
        </div>
        @endforelse
    </div>

</body>
</html>
