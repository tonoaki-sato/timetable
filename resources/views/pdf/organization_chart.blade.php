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
        table, th, td, h1, h2, h3, div, span, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #0f172a;
            font-size: 8pt;
            line-height: 1.25;
            background-color: #ffffff;
        }

        /* 全ページ共通固定ヘッダー (上部余白内に配置) */
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
        .header-title {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }
        .header-meta {
            font-size: 8pt;
            color: #64748b;
            text-align: right;
            vertical-align: bottom;
        }

        /* ツリー本体コンテナ */
        .chart-container {
            margin-top: 0;
            width: 100%;
        }
        .root-header-section {
            margin-bottom: 7pt;
            padding-bottom: 5pt;
            border-bottom: 1.2pt solid #94a3b8;
        }
        table.root-header-table {
            border-collapse: collapse;
        }
        td.root-leader-td {
            vertical-align: middle;
            width: 85pt;
            padding: 0;
        }
        td.root-bridge-td {
            vertical-align: middle;
            width: 12pt;
            padding: 0;
        }
        td.root-officers-td {
            vertical-align: middle;
            padding: 0;
        }
        .officer-card-wrapper {
            display: inline-block;
            margin-right: 5pt;
            vertical-align: middle;
        }
        .branch-section {
            margin-bottom: 6pt;
            page-break-inside: avoid;
        }
        .standalone-roots-container {
            margin-top: 9pt;
            padding-top: 5pt;
            border-top: 1pt dashed #94a3b8;
            page-break-inside: avoid;
        }
        .standalone-roots-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #475569;
            margin-bottom: 4pt;
        }
        .standalone-card-item {
            display: inline-block;
            margin-right: 6pt;
            margin-bottom: 4pt;
            vertical-align: top;
        }

        /* テーブル構造による階層ツリー */
        table.node-table, table.children-table {
            border-collapse: collapse;
            width: 100%;
        }
        td.node-card-td {
            vertical-align: top;
            width: 85pt;
            padding: 0;
        }
        td.bridge-td {
            vertical-align: top;
            width: 8pt;
            padding-top: 8pt;
            padding-left: 0;
            padding-right: 0;
        }
        .stem-bridge {
            width: 8pt;
            height: 1.2pt;
            background: #64748b;
        }
        td.children-td {
            vertical-align: top;
            padding: 0;
        }
        td.child-content-td {
            vertical-align: top;
            padding-bottom: 3.5pt;
            padding-left: 0;
        }
        td.connector-td {
            vertical-align: top;
            width: 8pt;
            position: relative;
            padding: 0;
        }
        td.connector-stem-border {
            border-left: 1.2pt solid #64748b;
        }

        /* コネクタ線 */
        .connector-mask-top {
            position: absolute;
            left: -1.5pt;
            top: 0;
            width: 3pt;
            height: 8pt;
            background: #ffffff;
        }
        .connector-horizontal {
            position: absolute;
            left: 0;
            top: 8pt;
            width: 8pt;
            height: 1.2pt;
            background: #64748b;
        }
        .connector-vertical-top {
            position: absolute;
            left: 0;
            top: 0;
            width: 1.2pt;
            height: 8.6pt;
            background: #64748b;
        }
        .connector-line-direct {
            position: absolute;
            left: 0;
            top: 8pt;
            width: 8pt;
            height: 1.2pt;
            background: #64748b;
        }

        /* 組織カード */
        .card {
            border: 0.8pt solid #cbd5e1;
            border-radius: 3pt;
            padding: 2.5pt 4pt;
            background: #ffffff;
            width: 80pt;
        }
        .card-level-1 {
            border-color: #047857;
            background-color: #ecfdf5;
        }
        .card-officer {
            border-color: #0284c7;
            background-color: #f0f9ff;
            width: 80pt;
        }
        .card-level-2 {
            border-color: #2563eb;
            background-color: #eff6ff;
        }
        .card-level-3 {
            border-color: #64748b;
            background-color: #f8fafc;
        }
        .card-title {
            font-weight: bold;
            font-size: 8pt;
            color: #0f172a;
            line-height: 1.2;
        }
        .card-staff {
            font-size: 6.5pt;
            color: #4338ca;
            margin-top: 1pt;
            line-height: 1.15;
        }

        /* 末端組織グループまとめ枠 */
        .leaf-group {
            display: inline-block;
            max-width: 100%;
            border: 0.8pt dashed #94a3b8;
            border-radius: 3pt;
            padding: 2pt;
            background-color: #f8fafc;
        }
        .leaf-card {
            display: inline-block;
            border: 0.8pt solid #cbd5e1;
            border-radius: 2pt;
            padding: 2pt 3pt;
            margin: 1.5pt;
            background-color: #ffffff;
            vertical-align: top;
            width: 60pt;
        }
        .leaf-title {
            font-weight: bold;
            font-size: 6.5pt;
            color: #1e293b;
            line-height: 1.15;
        }
        .leaf-staff {
            font-size: 6pt;
            color: #4338ca;
            line-height: 1.1;
        }
    </style>
</head>
<body>
    <!-- 全ページ共通固定ヘッダー -->
    <div class="page-header">
        <table class="header-table">
            <tr>
                <td class="header-title">{{ $title }}</td>
                <td class="header-meta">出力日時: {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <!-- 組織ツリー本体 -->
    <div class="chart-container">
        @php
            $rootWithChildren = $treeUnits->filter(fn ($u) => $u->children && $u->children->isNotEmpty());
            $standaloneRoots = $treeUnits->filter(fn ($u) => ! $u->children || $u->children->isEmpty());
        @endphp

        @if($treeUnits->isEmpty())
            <div style="text-align: center; padding: 30pt; color: #94a3b8;">
                登録されている組織・係がありません
            </div>
        @else
            {{-- 子を持つルートノード（実行委員長等）の展開 --}}
            @foreach($rootWithChildren as $root)
                @php
                    // 直属の本部幹部（福実行委員長、会計等: 子組織を持たず、委員長/会計等の役職名を含む組織）
                    $executiveOfficers = $root->children->filter(function ($c) {
                        return (! $c->children || $c->children->isEmpty())
                            && (str_contains($c->name, '委員長') || str_contains($c->name, '会計') || str_contains($c->name, '事務局長'));
                    });
                    // 各担当部門（総務、渉外、イベント、広報等）
                    $departmentUnits = $root->children->reject(function ($c) use ($executiveOfficers) {
                        return $executiveOfficers->contains('id', $c->id);
                    });
                @endphp

                <!-- 本部役員（実行委員長 + 直属幹部）セクション -->
                <div class="root-header-section">
                    <table class="root-header-table" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="root-leader-td">
                                <div class="card card-level-1">
                                    <div class="card-title">{{ $root->name }}</div>
                                    @if($root->staff->isNotEmpty())
                                        <div class="card-staff">{{ $root->staffNamesDisplay }}</div>
                                    @endif
                                </div>
                            </td>
                            @if($executiveOfficers->isNotEmpty())
                                <td class="root-bridge-td">
                                    <div class="stem-bridge"></div>
                                </td>
                                <td class="root-officers-td">
                                    @foreach($executiveOfficers as $officer)
                                        <div class="officer-card-wrapper">
                                            <div class="card card-officer">
                                                <div class="card-title">{{ $officer->name }}</div>
                                                @if($officer->staff->isNotEmpty())
                                                    <div class="card-staff">{{ $officer->staffNamesDisplay }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </td>
                            @endif
                        </tr>
                    </table>
                </div>

                {{-- 各部門・ブランチ（総務、渉外、イベント、広報等）を独立ブロックとして描画（自動改ページ対応） --}}
                @foreach($departmentUnits as $child)
                    <div class="branch-section">
                        @include('pdf.components.tree-node', ['unit' => $child, 'level' => 2])
                    </div>
                @endforeach
            @endforeach

            {{-- 単独ルートノード群（顧問、相談役等） --}}
            @if($standaloneRoots->isNotEmpty())
                <div class="standalone-roots-container">
                    <div class="standalone-roots-title">【顧問・相談役】</div>
                    @foreach($standaloneRoots as $standalone)
                        <div class="standalone-card-item">
                            @include('pdf.components.tree-node', ['unit' => $standalone, 'level' => 1])
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</body>
</html>
