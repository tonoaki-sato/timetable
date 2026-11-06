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
            size: A4 portrait;
            margin: 7mm 6mm;
        }
        *, body, table, th, td, h1, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            color: #000000;
            font-size: 7.5pt;
            line-height: 1.15;
            background-color: #ffffff;
        }
        .page-title {
            font-size: 13pt;
            font-weight: bold;
            color: #000000;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        table.matrix-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1.2px solid #000000;
        }
        table.matrix-table th,
        table.matrix-table td {
            border: 0.8px solid #000000;
            padding: 2px 2.5px;
            overflow: hidden;
            word-wrap: break-word;
            word-break: break-all;
        }
        table.matrix-table th {
            background-color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            text-align: center;
            vertical-align: middle;
            height: 18px;
        }
        .col-date {
            width: 36px;
        }
        .col-time {
            width: 36px;
        }
        .col-road {
            width: 44px;
        }
        .col-festival {
            width: 44px;
        }
        .cell-date {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 8.5pt;
            letter-spacing: 1px;
        }
        .bg-day1 {
            background-color: #fed7aa;
        }
        .bg-day2 {
            background-color: #bae6fd;
        }
        .bg-default {
            background-color: #e2e8f0;
        }
        .cell-time {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            font-size: 7.5pt;
            font-family: monospace, 'JapaneseFont', sans-serif !important;
            background-color: #ffffff;
        }
        .cell-status-empty {
            text-align: center;
            vertical-align: middle;
            color: #475569;
            font-size: 7.5pt;
        }
        .cell-road-closed {
            text-align: center;
            vertical-align: middle;
            color: #dc2626;
            background-color: #fee2e2;
            font-weight: bold;
            font-size: 7pt;
        }
        .cell-festival-active {
            text-align: center;
            vertical-align: middle;
            color: #16a34a;
            background-color: #dcfce7;
            font-weight: bold;
            font-size: 7pt;
        }
        .cell-task {
            vertical-align: top;
            text-align: left;
            background-color: #ffffff;
            font-size: 6.8pt;
            line-height: 1.15;
            padding: 2px 3px;
        }
        .task-title {
            font-weight: bold;
            color: #000000;
            font-size: 7.2pt;
        }
        .task-count {
            font-weight: bold;
            color: #1e3a8a;
            margin-left: 1px;
        }
        .task-content {
            margin-top: 1px;
            color: #0f172a;
            white-space: pre-line;
            font-size: 6.5pt;
            line-height: 1.1;
        }
        .task-location {
            margin-top: 1px;
            color: #475569;
            font-size: 6.2pt;
        }
    </style>
</head>
<body>

    <div class="page-title">{{ $title }}</div>

    <table class="matrix-table">
        <thead>
            <tr>
                <th class="col-date">日付</th>
                <th class="col-time">時間</th>
                @if($hasRoad)
                    <th class="col-road">道路</th>
                @endif
                @if($hasFestival)
                    <th class="col-festival">まつり</th>
                @endif
                @foreach($lanes as $lane)
                    @php
                        $leadStaff = $lane->leadStaff;
                        $leadName = $leadStaff ? (explode(' ', $leadStaff->name)[0] ?? $leadStaff->name) : '';
                        $leadSuffix = '';
                        if ($leadName) {
                            $leadSuffix = (str_ends_with($leadName, 'さん') || str_ends_with($leadName, '外')) ? "（{$leadName}）" : "（{$leadName}さん）";
                        }
                    @endphp
                    <th>{{ $lane->name }}{{ $leadSuffix }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($timeSlots as $sIdx => $slot)
            <tr>
                @if($loop->first)
                <td rowspan="{{ count($timeSlots) }}" class="cell-date {{ $dateTheme === 'day2' ? 'bg-day2' : ($dateTheme === 'day1' ? 'bg-day1' : 'bg-default') }}">
                    {{ $dateLabel }}
                </td>
                @endif

                <td class="cell-time">
                    {{ $slot['time'] }}
                </td>

                @if($hasRoad)
                @if($slot['road']['status'] === 'closed')
                    <td class="cell-road-closed">{{ $slot['road']['label'] }}</td>
                @else
                    <td class="cell-status-empty">-</td>
                @endif
                @endif

                @if($hasFestival)
                @if($slot['festival']['status'] === 'active')
                    <td class="cell-festival-active">{{ $slot['festival']['label'] }}</td>
                @else
                    <td class="cell-status-empty">-</td>
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
                            <span class="task-title">{{ $task->title }}</span>@if($peopleLabel)<span class="task-count">{{ $peopleLabel }}</span>@endif
                            @if($task->content)
                                <div class="task-content">{{ $task->content }}</div>
                            @endif
                            @if($task->location)
                                <div class="task-location">{{ $task->location }}</div>
                            @endif
                        </td>
                    @elseif($cell['type'] === 'empty')
                        <td class="cell-status-empty">-</td>
                    @elseif($cell['type'] === 'skip')
                        {{-- 上位セルで縦結合済みのスロット --}}
                    @endif
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>

