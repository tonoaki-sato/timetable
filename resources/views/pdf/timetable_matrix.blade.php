<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        body, table, th, td, h1, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            color: #0f172a;
            font-size: 7.5pt;
            line-height: 1.2;
            background-color: #ffffff;
        }
        .header-area {
            width: 100%;
            margin-bottom: 12px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .header-meta {
            font-size: 8pt;
            color: #475569;
            text-align: right;
            vertical-align: bottom;
        }

        /* テーブルコンテナ */
        .matrix-wrapper {
            width: 100%;
            background-color: #ffffff;
        }

        table.matrix-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1.5px solid #1e293b;
        }

        table.matrix-table thead {
            display: table-header-group;
        }

        table.matrix-table tbody {
            display: table-row-group;
        }

        table.matrix-table tr {
            page-break-inside: avoid;
        }

        /* 共通ヘッダー・セル罫線 */
        table.matrix-table th,
        table.matrix-table td {
            border-right: 1px solid #334155;
            border-bottom: 1px solid #334155;
            padding: 4px 5px;
            overflow: hidden;
            word-wrap: break-word;
            word-break: break-all;
        }

        /* テーブルヘッダー */
        table.matrix-table thead tr {
            border-bottom: 2px solid #1e293b;
        }
        table.matrix-table th {
            font-weight: bold;
            font-size: 7.5pt;
            vertical-align: middle;
            height: 24px;
            padding: 4px 6px;
        }

        /* 管理系列ヘッダー（日付・時間・道路・まつり: スレートグレー） */
        .th-control {
            background-color: #f1f5f9;
            color: #0f172a;
            text-align: center;
            padding-left: 1px;
            padding-right: 1px;
            white-space: nowrap;
        }

        /* 担当班列ヘッダー（オフホワイト・左寄せ） */
        .th-lane {
            background-color: #f8fafc;
            text-align: left;
            padding-left: 6px;
            padding-right: 6px;
        }
        .lane-header-title {
            font-size: 7.8pt;
            font-weight: bold;
            color: #0f172a;
        }
        .lane-header-lead {
            font-size: 7.2pt;
            color: #4338ca;
            font-weight: bold;
            margin-left: 2px;
        }
        .lane-header-icon {
            display: inline-block;
            margin-right: 2px;
            font-size: 8pt;
        }

        /* 列幅（固定％指定によりDomPDFのtable-layout: fixed環境下でも最小幅を維持し、担当班列を最大化） */
        .col-date {
            width: 3.5%;
        }
        .col-time {
            width: 4.2%;
        }
        .col-road {
            width: 4.8%;
        }
        .col-festival {
            width: 4.2%;
        }
        .col-boundary {
            border-right: 2px solid #1e293b !important;
        }

        /* 日付セル */
        .cell-date {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 7pt;
            background-color: #f1f5f9;
            color: #334155;
            padding-left: 1px;
            padding-right: 1px;
            white-space: nowrap;
        }

        /* 時間セル */
        .cell-time {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 7.8pt;
            color: #0f172a;
            background-color: #ffffff;
            padding-left: 1px;
            padding-right: 1px;
            white-space: nowrap;
        }

        /* 空セル */
        .cell-empty {
            text-align: center;
            vertical-align: middle;
            color: #cbd5e1;
            font-size: 8pt;
            background-color: #ffffff;
        }

        /* 道路・まつりステータス */
        .cell-road-closed {
            text-align: center;
            vertical-align: middle;
            color: #dc2626;
            background-color: #fee2e2;
            font-weight: bold;
            font-size: 7pt;
            padding-left: 1px;
            padding-right: 1px;
            white-space: nowrap;
        }
        .cell-festival-active {
            text-align: center;
            vertical-align: middle;
            color: #16a34a;
            background-color: #dcfce7;
            font-weight: bold;
            font-size: 7pt;
            padding-left: 1px;
            padding-right: 1px;
            white-space: nowrap;
        }

        /* タスクセル（添付画像のカードデザイン） */
        .cell-task {
            vertical-align: top;
            text-align: left;
            background-color: #ffffff;
            padding: 5px 6px;
        }
        .task-card {
            width: 100%;
        }

        /* タイトル ＋ 人員規模 */
        .task-header {
            margin-bottom: 3px;
        }
        .task-title {
            font-weight: bold;
            color: #0f172a;
            font-size: 7.5pt;
            line-height: 1.2;
        }
        .task-people {
            font-weight: bold;
            color: #4338ca;
            font-size: 7.2pt;
            margin-left: 2px;
        }

        /* 作業内容・箇条書き */
        .task-content {
            margin-top: 3px;
            margin-bottom: 3px;
            padding-left: 5px;
            border-left: 2px solid #cbd5e1;
            color: #334155;
            white-space: pre-line;
            font-size: 6.5pt;
            line-height: 1.2;
        }

        /* 場所情報（赤ピン付き） */
        .task-location-row {
            margin-top: 3px;
            margin-bottom: 3px;
            font-size: 6.8pt;
            color: #64748b;
        }
        .location-pin-icon {
            display: inline-block;
            vertical-align: -1px;
            margin-right: 1px;
        }

        /* ステータスバッジ（充足・不足） */
        .status-badge-satisfied {
            display: inline-block;
            background-color: #dcfce7;
            border: 0.6px solid #86efac;
            color: #15803d;
            font-weight: bold;
            font-size: 6.5pt;
            padding: 0.5px 3.5px;
            border-radius: 3px;
        }
        .status-badge-shortage {
            display: inline-block;
            background-color: #fef3c7;
            border: 0.6px solid #fcd34d;
            color: #92400e;
            font-weight: bold;
            font-size: 6.5pt;
            padding: 0.5px 3.5px;
            border-radius: 3px;
        }

        /* スタッフ名バッジ */
        .staff-badges-container {
            margin-top: 3px;
        }
        .staff-pill {
            display: inline-block;
            background-color: #f1f5f9;
            border: 0.6px solid #e2e8f0;
            color: #334155;
            font-size: 6.5pt;
            padding: 0.5px 3.5px;
            border-radius: 3px;
            margin-right: 2px;
            margin-bottom: 2px;
        }

        /* 右端列の右枠線消去（外枠と重複するため） */
        table.matrix-table tr > th:last-child,
        table.matrix-table tr > td:last-child {
            border-right: none;
        }
        /* 最終行の下枠線消去 */
        table.matrix-table tr:last-child > td {
            border-bottom: none;
        }
    </style>
</head>
<body>

    <!-- ヘッダー情報 -->
    <div class="header-area">
        <table class="header-table">
            <tr>
                <td class="header-title">
                    {{ $timetable->name }} 進行表
                </td>
                <td class="header-meta">
                    運行日: {{ $timetable->target_date ? $timetable->target_date->format('Y年m月d日') : '-' }}
                    ／ 出力日時: {{ now()->format('Y/m/d H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- テーブルコンテナ（濃紺外枠・角丸） -->
    <div class="matrix-wrapper">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th class="col-date th-control">日付</th>
                    <th class="col-time th-control {{ !$hasRoad && !$hasFestival ? 'col-boundary' : '' }}">時間</th>
                    @if($hasRoad)
                        <th class="col-road th-control {{ !$hasFestival ? 'col-boundary' : '' }}">道路</th>
                    @endif
                    @if($hasFestival)
                        <th class="col-festival th-control col-boundary">まつり</th>
                    @endif
                    @foreach($lanes as $lane)
                        @php
                            $leadStaff = $lane->leadStaff;
                            $leadName = $leadStaff ? (explode(' ', $leadStaff->name)[0] ?? $leadStaff->name) : '';
                            $leadSuffix = '';
                            if ($leadName) {
                                $leadSuffix = (str_ends_with($leadName, 'さん') || str_ends_with($leadName, '外')) ? "（{$leadName}）" : "（{$leadName}さん）";
                            }
                            $laneIcon = $lane->icon ?: '◆';
                        @endphp
                        <th class="th-lane">
                            <span class="lane-header-icon">{{ $laneIcon }}</span>
                            <span class="lane-header-title">{{ $lane->name }}</span>
                            @if($leadSuffix)
                                <span class="lane-header-lead">{{ $leadSuffix }}</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $sIdx => $slot)
                <tr>
                    <td class="cell-date">
                        {{ $dateLabel }}
                    </td>

                    <td class="cell-time">
                        {{ $slot['time'] }}
                    </td>

                    @if($hasRoad)
                    @if($slot['road']['status'] === 'closed')
                        <td class="cell-road-closed">{{ $slot['road']['label'] }}</td>
                    @else
                        <td class="cell-empty">-</td>
                    @endif
                    @endif

                    @if($hasFestival)
                    @if($slot['festival']['status'] === 'active')
                        <td class="cell-festival-active col-boundary">{{ $slot['festival']['label'] }}</td>
                    @else
                        <td class="cell-empty col-boundary">-</td>
                    @endif
                    @endif

                    @foreach($lanes as $lane)
                        @php
                            $cell = $grid[$sIdx][$lane->id] ?? ['type' => 'empty', 'rowspan' => 1];
                        @endphp

                        @if($cell['type'] === 'task')
                            @php
                                $task = $cell['task'];
                                $reqCount = $task->required_people_count;
                                $peopleLabel = $reqCount ? ($task->is_at_least ? "【{$reqCount}名以上】" : "【{$reqCount}名】") : '';
                            @endphp
                            <td rowspan="{{ $cell['rowspan'] }}" class="cell-task">
                                <div class="task-card">
                                    <!-- タイトル ＋ 人員規模 -->
                                    <div class="task-header">
                                        <span class="task-title">{{ $task->title }}</span>@if($peopleLabel)<span class="task-people">{{ $peopleLabel }}</span>@endif
                                    </div>

                                    <!-- 作業内容（箇条書き等） -->
                                    @if($task->content)
                                        <div class="task-content">{{ $task->content }}</div>
                                    @endif

                                    <!-- 場所（赤ピン付き） -->
                                    @if($task->location)
                                        <div class="task-location-row">
                                            <span class="location-pin-icon">
                                                <svg width="7" height="9" viewBox="0 0 24 24" fill="#ef4444" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                                </svg>
                                            </span>
                                            <span>{{ $task->location }}</span>
                                        </div>
                                    @endif

                                    <!-- 充足ステータスインジケーター -->
                                    <div>
                                        @if($task->is_satisfied)
                                            <span class="status-badge-satisfied">✔ 充足 ({{ $task->assigned_count }})</span>
                                        @else
                                            <span class="status-badge-shortage">⚠️ 不足 {{ $task->shortage_count }}名</span>
                                        @endif
                                    </div>

                                    <!-- アサインされたスタッフピルバッジ -->
                                    @if($task->assignments->isNotEmpty())
                                        <div class="staff-badges-container">
                                            @foreach($task->assignments as $assignment)
                                                @if($assignment->staff)
                                                    <span class="staff-pill">{{ $assignment->staff->name }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        @elseif($cell['type'] === 'empty')
                            <td class="cell-empty">-</td>
                        @elseif($cell['type'] === 'skip')
                            {{-- 上位セルで縦結合済みのスロット --}}
                        @endif
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</body>
</html>
