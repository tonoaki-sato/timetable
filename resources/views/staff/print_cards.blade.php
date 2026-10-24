<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        /* ベーススタイル */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Hiragino Sans", "Noto Sans JP", sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 20px;
        }

        /* 画面上部コントロールバー（印刷時は非表示） */
        .no-print {
            max-width: 900px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-print {
            background-color: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background-color: #4338ca;
        }
        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .btn-back:hover {
            color: #1e293b;
        }

        /* A4印刷用紙シート */
        .print-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm 10mm;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        /* 名札グリッド: 2列 × 4行 = 8枚/ページ */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, 92mm);
            grid-auto-rows: 64mm;
            gap: 6mm;
            justify-content: center;
        }

        /* 名札カード個別枠（キリトリ線） */
        .badge-card {
            border: 1.5px dashed #94a3b8;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            page-break-inside: avoid;
            position: relative;
        }

        /* カードヘッダー */
        .badge-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .event-title {
            font-size: 10px;
            font-weight: 800;
            color: #4338ca;
            letter-spacing: 0.5px;
        }
        .role-tag {
            font-size: 9px;
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }

        /* カードボディ */
        .badge-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 6px 0;
            gap: 8px;
        }
        .info-col {
            flex: 1;
            min-width: 0;
        }
        .staff-kana {
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .staff-name {
            font-size: 21px;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.15;
            letter-spacing: -0.5px;
            word-break: break-all;
        }
        .staff-affiliation {
            font-size: 10.5px;
            color: #334155;
            margin-top: 4px;
            font-weight: 600;
        }
        .staff-line-name {
            font-size: 9.5px;
            color: #059669;
            margin-top: 3px;
            font-weight: 700;
        }

        /* QRコードエリア */
        .qr-col {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 3px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .qr-col svg {
            width: 100%;
            height: 100%;
        }

        /* カードフッター */
        .badge-footer {
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
            line-height: 1.2;
        }

        /* 印刷スタイル */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-sheet {
                width: 100%;
                min-height: auto;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            .badge-card {
                border-color: #cbd5e1;
            }
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>

    <!-- 操作バー -->
    <div class="no-print">
        <div>
            <a href="{{ route('staff.index') }}" class="btn-back">← スタッフ管理一覧に戻る</a>
            <h2 style="font-size: 16px; margin-top: 4px; font-weight: bold;">{{ $title }}</h2>
        </div>
        <button type="button" onclick="window.print()" class="btn-print">
            🖨️ 今すぐ印刷する (A4用紙)
        </button>
    </div>

    <!-- 印刷用紙シート -->
    <div class="print-sheet">
        <div class="card-grid">
            @forelse($cards as $card)
            @php $staff = $card['staff']; @endphp
            <div class="badge-card">
                <!-- ヘッダー -->
                <div class="badge-header">
                    <span class="event-title">まつり運行スタッフ証</span>
                    <span class="role-tag">現場STAFF</span>
                </div>

                <!-- ボディ -->
                <div class="badge-body">
                    <div class="info-col">
                        <div class="staff-kana">{{ $staff->kana }}</div>
                        <div class="staff-name">{{ $staff->name }}</div>
                        <div class="staff-affiliation">
                            🏢 {{ $staff->affiliation ?: '本部直属 / 一般公募' }}
                        </div>
                        @if($staff->line_display_name)
                        <div class="staff-line-name">
                            💬 LINE: {{ $staff->line_display_name }}
                        </div>
                        @endif
                    </div>

                    <!-- QRコード -->
                    <div class="qr-col">
                        {!! $card['qr_svg'] !!}
                    </div>
                </div>

                <!-- フッター -->
                <div class="badge-footer">
                    スマートフォンでQRコードを読取 ➡ 当日のマイシフト・運行表を確認
                </div>
            </div>
            @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                印刷対象のスタッフが存在しません。
            </div>
            @endforelse
        </div>
    </div>

</body>
</html>
