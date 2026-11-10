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
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 800;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
            font-weight: 900;
            font-style: normal;
        }
        @page {
            size: A4 portrait;
            margin: 8mm 6mm;
        }
        *, body, table, th, td, h1, h2, h3, div, span, strong, b, p {
            font-family: 'JapaneseFont', sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'JapaneseFont', sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.3;
        }
        .page {
            page-break-after: always;
            height: 100%;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .card-cell {
            width: 50%;
            vertical-align: top;
            padding: 3mm 4mm;
        }
        .badge-card {
            border: 1.5px dashed #94a3b8;
            border-radius: 8px;
            padding: 5px 8px 3px 8px;
            background-color: #ffffff;
            height: 62mm;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 3px;
            background-color: #1e40af;
            color: #ffffff;
        }
        .event-title {
            font-size: 11px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .header-right {
            text-align: right;
            font-size: 9px;
            vertical-align: middle;
        }
        .reissue-tag {
            background-color: #fef3c7;
            color: #92400e;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
            margin-right: 3px;
            white-space: nowrap;
        }
        .body-table {
            width: 100%;
            height: 45mm;
            border-collapse: collapse;
        }
        .info-col {
            vertical-align: middle;
            width: 60%;
            padding-left: 6px;
            padding-right: 4px;
        }
        .staff-kana {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .staff-name {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
            word-break: break-all;
        }
        .qr-col {
            width: 40%;
            text-align: center;
            vertical-align: middle;
        }
        .qr-wrapper {
            display: inline-block;
            text-align: center;
        }
        .qr-img {
            width: 92px;
            height: 92px;
            display: block;
            margin: 0 auto;
        }
        .qr-caption {
            font-size: 8.5px;
            color: #475569;
            margin-top: 3px;
            text-align: center;
            line-height: 1;
            font-weight: bold;
        }
        .badge-footer {
            font-size: 8.5px;
            color: #64748b;
            text-align: right;
            border-top: 1px solid #f1f5f9;
            padding-top: 3px;
            margin-top: 1px;
        }
    </style>
</head>
<body>
    @forelse($cards->chunk(8) as $pageCards)
    <div class="page">
        <table class="grid-table">
            @foreach($pageCards->chunk(2) as $rowCards)
            <tr>
                @foreach($rowCards as $card)
                <td class="card-cell">
                    <div class="badge-card">
                        <!-- ヘッダー -->
                        <table class="header-table">
                            <tr>
                                <td class="event-title">保土ケ谷宿場まつり STAFF</td>
                                <td class="header-right">
                                    @if($card['staff']->is_reissued)
                                    <span class="reissue-tag">再発行(V.{{ $card['staff']->reissue_count + 1 }})</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <!-- ボディ -->
                        <table class="body-table">
                            <tr>
                                <td class="info-col">
                                    <div class="staff-kana">{{ $card['staff']->kana ?: 'ー' }}</div>
                                    <div class="staff-name">{{ $card['staff']->name }}</div>
                                </td>
                                <td class="qr-col">
                                    <div class="qr-wrapper">
                                        <img src="{{ $card['qr_data_uri'] }}" class="qr-img" alt="マイシフトQR">
                                        <div class="qr-caption">マイシフト確認</div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- フッター -->
                        <div class="badge-footer">
                            発行: 保土ケ谷宿場まつり実行委員会
                        </div>
                    </div>
                </td>
                @endforeach

                {{-- 行に1枚しかない場合の空白埋め --}}
                @if($rowCards->count() === 1)
                <td class="card-cell"></td>
                @endif
            </tr>
            @endforeach
        </table>
    </div>
    @empty
    <div style="text-align: center; padding: 40px;">
        <p>対象のスタッフが存在しません。</p>
    </div>
    @endforelse
</body>
</html>
