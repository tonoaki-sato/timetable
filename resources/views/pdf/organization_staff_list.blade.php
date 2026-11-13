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
            margin: 12mm 10mm;
        }
        *, body, table, th, td, h1, h2, h3, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #0f172a;
            font-size: 8pt;
            line-height: 1.3;
            background-color: #ffffff;
        }

        /* ページヘッダー */
        .header-table {
            width: 100%;
            border-bottom: 1.5pt solid #0f172a;
            padding-bottom: 4pt;
            margin-bottom: 6pt;
        }
        .header-title-main {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }
        .header-meta {
            text-align: right;
            font-size: 8pt;
            color: #475569;
            vertical-align: bottom;
            white-space: nowrap;
        }

        /* リストテーブル */
        .staff-list-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .staff-list-table tr {
            page-break-inside: avoid;
        }

        /* ヘッダー行: 高さ1行分 */
        .staff-list-table th {
            height: 16pt;
            line-height: 14pt;
            padding: 2pt 4pt;
            vertical-align: middle;
            white-space: nowrap;
            background-color: #f1f5f9;
            border: 0.8pt solid #cbd5e1;
            font-weight: bold;
            font-size: 8.5pt;
            color: #1e293b;
            text-align: left;
        }
        .staff-list-table th.col-no {
            text-align: center;
        }

        /* データ行: 高さ2行分 */
        .staff-list-table td {
            height: 26pt;
            line-height: 13pt;
            padding: 2pt 4pt;
            vertical-align: middle;
            border: 0.8pt solid #cbd5e1;
            font-size: 8pt;
            color: #334155;
            word-wrap: break-word;
            overflow: hidden;
        }
        .staff-list-table td.col-no {
            text-align: center;
            color: #64748b;
        }

        /* 第1階層（Root）行の背景色強調 */
        .staff-list-table tr.row-root td {
            background-color: #f8fafc;
        }

        /* 名前列の階層字下げインデント */
        .indent-level-0 {
            padding-left: 3pt;
            font-weight: bold;
            color: #0f172a;
        }
        .indent-level-1 {
            padding-left: 13pt;
            font-weight: bold;
            color: #1e293b;
        }
        .indent-level-2 {
            padding-left: 23pt;
            color: #334155;
        }
        .indent-level-3 {
            padding-left: 33pt;
            color: #475569;
        }
    </style>
</head>
<body>
    {{-- 固定ヘッダー --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="header-title-main">{{ $title }}</td>
            <td class="header-meta">出力日時: {{ $generatedAt }}</td>
        </tr>
    </table>

    {{-- 組織別所属スタッフ一覧テーブル --}}
    <table class="staff-list-table" cellpadding="0" cellspacing="0">
        <colgroup>
            <col style="width: {{ $columnWidths['no'] }};">
            <col style="width: {{ $columnWidths['name'] }};">
            <col style="width: {{ $columnWidths['staff'] }};">
            <col style="width: {{ $columnWidths['description'] }};">
        </colgroup>
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th>名前</th>
                <th>所属スタッフ</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($units as $unit)
                @php
                    $depth = (int) ($unit->depth ?? 0);
                    $indentClass = 'indent-level-' . min($depth, 3);
                    $rowClass = $depth === 0 ? 'row-root' : '';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="col-no">{{ $loop->iteration }}</td>
                    <td>
                        <div class="{{ $indentClass }}">{{ $unit->name }}</div>
                    </td>
                    <td>{{ $unit->staffNamesDisplay ?: '-' }}</td>
                    <td>{{ $unit->cleaned_description ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

