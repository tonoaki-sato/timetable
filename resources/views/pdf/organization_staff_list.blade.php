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
            font-size: 8pt;
            line-height: 1.3;
            background-color: #ffffff;
        }

        /* 全ページ共通固定ヘッダー (上部18mm余白内に配置) */
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
            margin-top: 1mm;
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
            padding-left: 2pt;
            font-weight: bold;
            color: #0f172a;
        }
        .indent-level-1 {
            padding-left: 10pt;
            font-weight: bold;
            color: #1e293b;
        }
        .indent-level-2 {
            padding-left: 18pt;
            color: #334155;
        }
        .indent-level-3 {
            padding-left: 26pt;
            color: #475569;
        }
    </style>
    @php
        $styleOpen = '<' . 'style>';
        $styleClose = '</' . 'style>';
        $dynamicColumnCss = "
            .staff-list-table th.col-no { width: {$columnWidths['no']}; }
            .staff-list-table th.col-name { width: {$columnWidths['name']}; }
            .staff-list-table th.col-staff { width: {$columnWidths['staff']}; }
            .staff-list-table th.col-description { width: {$columnWidths['description']}; }
        ";
    @endphp
    {!! $styleOpen . $dynamicColumnCss . $styleClose !!}
</head>
<body>
    {{-- 全ページ共通固定ヘッダー --}}
    <div class="page-header">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="header-title-main">{{ $title }}</td>
                <td class="header-meta">出力日時: {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    {{-- 組織別所属スタッフ一覧テーブル --}}
    <table class="staff-list-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-name">名前</th>
                <th class="col-staff">所属スタッフ</th>
                <th class="col-description">詳細</th>
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
