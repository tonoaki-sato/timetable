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
            margin: 20mm 10mm 12mm 10mm;
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

        /* 全ページ共通固定ヘッダー (上部20mm余白内に配置) */
        .page-header {
            position: fixed;
            top: -15mm;
            left: 0;
            right: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5pt solid #0f172a;
            padding-bottom: 2mm;
        }
        .header-title-main {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }
        .header-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #475569;
            vertical-align: bottom;
            white-space: nowrap;
        }

        /* ページブロック制御 */
        .page-block {
            page-break-after: always;
        }
        .page-block:last-child {
            page-break-after: avoid;
        }

        /* 2段組スタッフ一覧テーブル */
        .staff-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 1mm;
        }
        .staff-table thead {
            display: table-header-group;
        }
        .staff-table tr {
            page-break-inside: avoid;
        }

        /* ヘッダーセル */
        .staff-table th {
            height: 16pt;
            line-height: 14pt;
            padding: 2.5pt 3.5pt;
            vertical-align: middle;
            white-space: nowrap;
            background-color: #f1f5f9;
            border-top: 0.8pt solid #cbd5e1;
            border-bottom: 0.8pt solid #cbd5e1;
            font-weight: bold;
            font-size: 7.5pt;
            color: #1e293b;
            text-align: left;
        }

        /* データセル */
        .staff-table td {
            height: 16pt;
            line-height: 14pt;
            padding: 2.5pt 3.5pt;
            vertical-align: middle;
            border-bottom: 0.5pt solid #e2e8f0;
            font-size: 7.5pt;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* 列幅指定（DomPDF規約: colgroup不使用） */
        .staff-table th.col-name,
        .staff-table td.col-name {
            width: 17.5%;
            font-weight: bold;
            color: #0f172a;
        }
        .staff-table th.col-kana,
        .staff-table td.col-kana {
            width: 15.5%;
            font-size: 7pt;
            color: #64748b;
        }
        .staff-table th.col-affiliation,
        .staff-table td.col-affiliation {
            width: 15%;
            color: #475569;
        }
        .staff-table th.col-divider,
        .staff-table td.col-divider {
            width: 4%;
            padding: 0;
            background-color: #ffffff;
            border-top: none;
            border-bottom: none;
            border-left: 1pt solid #cbd5e1;
            border-right: none;
        }

        /* 空行・空セルスタイル */
        .staff-table td.cell-empty {
            color: transparent;
            background-color: transparent;
        }
        .empty-state-cell {
            padding: 30pt 10pt;
            text-align: center;
            color: #94a3b8;
            font-size: 9pt;
            border-bottom: none;
        }
    </style>
</head>
<body>
    {{-- 全ページ共通固定ヘッダー --}}
    <div class="page-header">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="header-title-main">{{ $title }}</td>
                <td class="header-meta">
                    出力日時: {{ $generatedAt }} ｜ 対象: {{ $totalCount }}名 ({{ $filterText }})
                </td>
            </tr>
        </table>
    </div>

    {{-- ページ分割された2段組スタッフ一覧 --}}
    @forelse($pages as $page)
        <div class="page-block">
            <table class="staff-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="col-name">氏名</th>
                        <th class="col-kana">フリガナ</th>
                        <th class="col-affiliation">所属団体</th>
                        <th class="col-divider"></th>
                        <th class="col-name">氏名</th>
                        <th class="col-kana">フリガナ</th>
                        <th class="col-affiliation">所属団体</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < $page['rowCount']; $i++)
                        @php
                            $leftStaff = $page['left']->get($i);
                            $rightStaff = $page['right']->get($i);
                        @endphp
                        <tr>
                            {{-- 左段 --}}
                            @if($leftStaff)
                                <td class="col-name">{{ $leftStaff->name }}</td>
                                <td class="col-kana">{{ $leftStaff->kana ?: '-' }}</td>
                                <td class="col-affiliation">{{ $leftStaff->affiliation ?: '-' }}</td>
                            @else
                                <td class="col-name cell-empty">-</td>
                                <td class="col-kana cell-empty">-</td>
                                <td class="col-affiliation cell-empty">-</td>
                            @endif

                            {{-- 中央区切り線 --}}
                            <td class="col-divider"></td>

                            {{-- 右段 --}}
                            @if($rightStaff)
                                <td class="col-name">{{ $rightStaff->name }}</td>
                                <td class="col-kana">{{ $rightStaff->kana ?: '-' }}</td>
                                <td class="col-affiliation">{{ $rightStaff->affiliation ?: '-' }}</td>
                            @else
                                <td class="col-name cell-empty">-</td>
                                <td class="col-kana cell-empty">-</td>
                                <td class="col-affiliation cell-empty">-</td>
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @empty
        <div class="page-block">
            <table class="staff-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th class="col-name">氏名</th>
                        <th class="col-kana">フリガナ</th>
                        <th class="col-affiliation">所属団体</th>
                        <th class="col-divider"></th>
                        <th class="col-name">氏名</th>
                        <th class="col-kana">フリガナ</th>
                        <th class="col-affiliation">所属団体</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="empty-state-cell">
                            該当するスタッフは見つかりませんでした。
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforelse
</body>
</html>
