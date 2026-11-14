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
            margin: 18mm 12mm 12mm 12mm;
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
            top: -14mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-bottom: 1.5pt solid #0f172a;
            padding-bottom: 2mm;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title-main {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
        }
        .header-subtitle {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1pt;
        }
        .header-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            vertical-align: bottom;
            white-space: nowrap;
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
        .schedule-table th.col-datetime { width: 22%; }
        .schedule-table th.col-name { width: 24%; }
        .schedule-table th.col-location { width: 15%; }
        .schedule-table th.col-staff { width: 12%; }
        .schedule-table th.col-description { width: 27%; }
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
                <th class="col-datetime">日時・期間</th>
                <th class="col-name">名前</th>
                <th class="col-location">場所</th>
                <th class="col-staff">担当</th>
                <th class="col-description">詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $schedule)
                <tr>
                    <td class="col-datetime">{{ $schedule->pdf_date_period }}</td>
                    <td class="col-name font-bold">{{ $schedule->title }}</td>
                    <td class="col-location">{{ $schedule->location ?: '-' }}</td>
                    <td class="col-staff">{{ $schedule->assignee?->name ?: '-' }}</td>
                    <td class="col-description">{!! $schedule->description ? nl2br(e($schedule->description)) : '-' !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 12pt; color: #64748b;">
                        該当するスケジュールはありません。
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
