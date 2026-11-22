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
            margin: 24mm 12mm 12mm 12mm;
        }
        table, th, td, h1, h2, h3, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #0f172a;
            font-size: 7.5pt;
            line-height: 1.35;
            background-color: #ffffff;
        }
        .page-header {
            position: fixed;
            top: -19mm;
            left: 0;
            right: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5pt solid #0f172a;
        }
        .header-table td {
            padding-bottom: 3mm;
        }
        .header-title-main {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 1.5mm;
        }
        .header-subtitle {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.2;
        }
        .header-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            vertical-align: bottom;
            white-space: nowrap;
            line-height: 1.2;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 1mm;
        }
        .schedule-table thead {
            display: table-header-group;
        }
        .schedule-table tr {
            page-break-inside: avoid;
        }
        .schedule-table th {
            height: 16pt;
            line-height: 14pt;
            padding: 2pt 3.5pt;
            vertical-align: middle;
            white-space: nowrap;
            background-color: #f1f5f9;
            border: 0.8pt solid #cbd5e1;
            font-weight: bold;
            font-size: 8pt;
            color: #1e293b;
            text-align: left;
        }
        .schedule-table td {
            padding: 3pt 3.5pt;
            vertical-align: top;
            border: 0.8pt solid #cbd5e1;
            font-size: 7.5pt;
            color: #334155;
            word-wrap: break-word;
            overflow: hidden;
        }
        .schedule-table th.col-date { width: 18%; }
        .schedule-table th.col-time { width: 11%; }
        .schedule-table th.col-name { width: 20%; }
        .schedule-table th.col-location { width: 14%; }
        .schedule-table th.col-staff { width: 11%; }
        .schedule-table th.col-description { width: 26%; }
    </style>
</head>
<body>
    <div class="page-header">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="header-title-main">{{ $title }}</div>
                    @if(!empty($subtitle))
                        <div class="header-subtitle">{{ $subtitle }}</div>
                    @endif
                </td>
                <td class="header-meta">出力日時: {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <table class="schedule-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="col-date">日付</th>
                <th class="col-time">時間</th>
                <th class="col-name">名前</th>
                <th class="col-location">場所</th>
                <th class="col-staff">担当</th>
                <th class="col-description">詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td class="col-date">{{ $schedule->presenter()->pdfDate() }}</td>
                    <td class="col-time">{{ $schedule->presenter()->pdfTime() }}</td>
                    <td class="col-name font-bold">{{ $schedule->title }}</td>
                    <td class="col-location">{{ $schedule->location ?: '-' }}</td>
                    <td class="col-staff">{{ $schedule->assignee?->name ?: '-' }}</td>
                    <td class="col-description">{!! $schedule->description ? nl2br(e($schedule->description)) : '-' !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 12pt; color: #64748b;">
                        該当するスケジュールはありません。
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
