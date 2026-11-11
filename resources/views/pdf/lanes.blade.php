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
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 500;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 600;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body, table, th, td, h1, h2, h3, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #0f172a;
            font-size: 9pt;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 1.5pt solid #0f172a;
            padding-bottom: 3pt;
            margin-bottom: 5pt;
        }
        .header-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }
        .header-meta {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 2pt;
        }
        .header-stats {
            text-align: right;
            font-size: 7.5pt;
            color: #334155;
            padding-top: 2pt;
        }
        .stat-badge {
            display: inline-block;
            background-color: #f1f5f9;
            border: 0.75pt solid #cbd5e1;
            padding: 2pt 6pt;
            border-radius: 2.5pt;
            font-weight: bold;
            white-space: nowrap;
        }
        .lanes-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8.5pt;
        }
        .lanes-table thead {
            display: table-header-group;
        }
        .lanes-table th {
            background-color: #f1f5f9;
            border: 0.75pt solid #cbd5e1;
            padding: 4.5pt 6pt;
            text-align: left;
            font-weight: bold;
            color: #1e293b;
            font-size: 8pt;
        }
        .lanes-table td {
            border: 0.75pt solid #cbd5e1;
            padding: 3.5pt 6pt;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .lanes-table tr {
            page-break-inside: avoid;
        }
        @if($printData->uniformRowHeightPt !== null)
        .lanes-table tbody tr.lane-row td {
            height: {{ number_format($printData->uniformRowHeightPt, 2, '.', '') }}pt;
        }
        @endif
        .col-no {
            width: 6%;
            text-align: center !important;
            color: #475569;
            font-weight: bold;
        }
        .col-name {
            width: 24%;
        }
        .col-lead {
            width: 22%;
        }
        .col-description {
            width: 48%;
            color: #334155;
        }
        .lane-name {
            font-weight: bold;
            font-size: 9pt;
            color: #0f172a;
        }
        .color-dot {
            display: inline-block;
            width: 6pt;
            height: 6pt;
            border-radius: 50%;
            margin-right: 3.5pt;
            vertical-align: middle;
        }
        .dot-amber { background-color: #f59e0b; }
        .dot-sky { background-color: #0ea5e9; }
        .dot-emerald { background-color: #10b981; }
        .dot-purple { background-color: #a855f7; }
        .dot-pink { background-color: #ec4899; }
        .dot-red { background-color: #f43f5e; }
        .dot-orange { background-color: #f97316; }
        .dot-indigo { background-color: #6366f1; }
        .dot-teal { background-color: #14b8a6; }
        .dot-yellow { background-color: #eab308; }
        .dot-slate { background-color: #64748b; }
        .lead-name {
            font-weight: bold;
            font-size: 9pt;
            color: #0f172a;
        }
        .lead-unset {
            color: #94a3b8;
        }
        .affiliation-text {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 1pt;
            line-height: 1.2;
        }
        .empty-row {
            text-align: center;
            padding: 20pt !important;
            color: #64748b;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="header-title">まつり運行 担当班一覧表</div>
                <div class="header-meta">
                    運行枠: <strong>{{ $printData->timetable->name }}</strong> ｜
                    運行日: <strong>{{ $printData->timetable->target_date?->format('Y年m月d日') ?? '未設定' }}</strong> ｜
                    運行時間: {{ substr((string) $printData->timetable->start_time, 0, 5) }}〜{{ substr((string) $printData->timetable->end_time, 0, 5) }} ｜
                    出力日時: {{ $printData->generatedAt }}
                </div>
            </td>
        </tr>
        <tr>
            <td class="header-stats">
                <span class="stat-badge">
                    総班数: <strong>{{ $printData->totalLanes }}班</strong>
                </span>
            </td>
        </tr>
    </table>

    <table class="lanes-table">
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-name">班名</th>
                <th class="col-lead">班長（リーダー）</th>
                <th class="col-description">役割概要・担当エリア</th>
            </tr>
        </thead>
        <tbody>
            @forelse($printData->rows as $row)
            <tr class="lane-row">
                <td class="col-no">{{ $row->orderNumber }}</td>
                <td class="col-name">
                    <span class="color-dot dot-{{ $row->colorKey }}"></span>
                    <span class="lane-name">{{ $row->name }}</span>
                </td>
                <td class="col-lead">
                    @if($row->hasLead)
                        <div class="lead-name">{{ $row->leadName }}</div>
                        @if($row->leadAffiliation !== '')
                        <div class="affiliation-text">{{ $row->leadAffiliation }}</div>
                        @endif
                    @else
                        <span class="lead-unset">未設定</span>
                    @endif
                </td>
                <td class="col-description">
                    @if($row->description !== '')
                        {!! nl2br(e($row->description)) !!}
                    @else
                        —
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="empty-row">担当班がまだ登録されていません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

