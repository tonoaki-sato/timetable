<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/print-cards.css') }}">
    <script src="{{ asset('js/print-helper.js') }}" defer></script>
</head>
<body>

    <!-- 操作バー -->
    <div class="no-print">
        <div>
            <a href="{{ route('staff.index') }}" class="btn-back">← スタッフ管理一覧に戻る</a>
            <h2 class="page-title">{{ $title }}</h2>
        </div>
        <button type="button" data-action="print" class="btn-print">
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
                    <div class="badge-header-right">
                        @if($staff->is_reissued)
                        <span class="reissue-tag">再発行 (Ver.{{ $staff->reissue_count + 1 }})</span>
                        @endif
                        <span class="role-tag">現場STAFF</span>
                    </div>
                </div>

                <!-- ボディ -->
                <div class="badge-body">
                    <div class="info-col">
                        <div class="staff-kana">{{ $staff->kana }}</div>
                        <div class="staff-name">{{ $staff->name }}</div>
                        <div class="staff-affiliation">
                            🏢 {{ $staff->affiliation ?: '本部直属 / 一般公募' }}
                        </div>
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
            <div class="empty-notice">
                印刷対象のスタッフが存在しません。
            </div>
            @endforelse
        </div>
    </div>

</body>
</html>
