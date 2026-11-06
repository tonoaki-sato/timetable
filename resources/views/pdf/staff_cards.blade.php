<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ $japaneseFontPath }}') format('truetype');
        }
        @page {
            size: A4 portrait;
            margin: 8mm 6mm;
        }
        * {
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
            padding: 8px 10px;
            background-color: #ffffff;
            height: 62mm;
        }
        .header-table {
            width: 100%;
            border-bottom: 1.5px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .event-title {
            font-size: 9px;
            font-weight: bold;
            color: #4338ca;
            letter-spacing: 0.5px;
        }
        .header-right {
            text-align: right;
            font-size: 8px;
        }
        .role-tag {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .reissue-tag {
            background-color: #f59e0b;
            color: #ffffff;
            padding: 1px 4px;
            border-radius: 3px;
            font-weight: bold;
            margin-right: 3px;
        }
        .body-table {
            width: 100%;
            margin-bottom: 4px;
        }
        .info-col {
            vertical-align: middle;
            width: 66%;
        }
        .staff-kana {
            font-size: 8px;
            color: #64748b;
            margin-bottom: 1px;
        }
        .staff-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
            white-space: nowrap;
        }
        .staff-affiliation {
            font-size: 8px;
            color: #475569;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 2px 4px;
        }
        .qr-col {
            width: 34%;
            text-align: center;
            vertical-align: middle;
        }
        .qr-wrapper {
            display: inline-block;
        }
        .qr-wrapper svg {
            width: 75px;
            height: 75px;
        }
        .badge-footer {
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
            border-top: 1px solid #f1f5f9;
            padding-top: 4px;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    @php
        $pages = $cards->chunk(8);
    @endphp

    @forelse($pages as $pageCards)
    <div class="page">
        <table class="grid-table">
            @foreach($pageCards->chunk(2) as $rowCards)
            <tr>
                @foreach($rowCards as $card)
                @php $staff = $card['staff']; @endphp
                <td class="card-cell">
                    <div class="badge-card">
                        <!-- ヘッダー -->
                        <table class="header-table">
                            <tr>
                                <td class="event-title">まつり運行スタッフ証</td>
                                <td class="header-right">
                                    @if($staff->is_reissued)
                                    <span class="reissue-tag">再発行(V.{{ $staff->reissue_count + 1 }})</span>
                                    @endif
                                    <span class="role-tag">現場STAFF</span>
                                </td>
                            </tr>
                        </table>

                        <!-- ボディ -->
                        <table class="body-table">
                            <tr>
                                <td class="info-col">
                                    <div class="staff-kana">{{ $staff->kana ?: 'ー' }}</div>
                                    <div class="staff-name">{{ $staff->name }}</div>
                                    <div class="staff-affiliation">
                                        🏢 {{ $staff->affiliation ?: '本部直属 / 一般公募' }}
                                    </div>
                                </td>
                                <td class="qr-col">
                                    <div class="qr-wrapper">
                                        {!! $card['qr_svg'] !!}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- フッター -->
                        <div class="badge-footer">
                            スマホでQR読取 ➡ 当日のマイシフト・運行表を確認
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

