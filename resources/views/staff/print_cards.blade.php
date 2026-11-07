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
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ url()->current() }}" class="btn-print" style="text-decoration: none; display: inline-block;">
                📄 PDFをダウンロード (A4縦)
            </a>
            <button type="button" data-action="print" class="btn-print" style="background-color: #64748b;">
                🖨️ ブラウザ印刷
            </button>
        </div>
    </div>

    <!-- 印刷用紙シート -->
    <div class="print-sheet">
        <div class="card-grid">
            @forelse($cards as $card)
            @php $staff = $card['staff']; @endphp
            <div class="badge-card">
                <!-- ヘッダー -->
                <div class="badge-header {{ $staff->is_leader ? 'header-leader' : 'header-staff' }}">
                    <span class="event-title">保土ケ谷宿場まつり STAFF</span>
                    <div class="badge-header-right">
                        @if($staff->is_reissued)
                        <span class="reissue-tag">再発行 (Ver.{{ $staff->reissue_count + 1 }})</span>
                        @endif
                        @if($staff->is_leader)
                            @foreach($staff->led_lane_names as $laneName)
                            <span class="lane-tag">{{ $laneName }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- ボディ -->
                <div class="badge-body">
                    <div class="info-col">
                        <div class="staff-kana">{{ $staff->kana ?: 'ー' }}</div>
                        <div class="staff-name">{{ $staff->name }}</div>
                    </div>

                    <!-- QRコード -->
                    <div class="qr-col">
                        <img src="{{ $card['qr_data_uri'] }}" class="qr-img" alt="マイシフトQR">
                        <div class="qr-caption">マイシフト確認</div>
                    </div>
                </div>

                <!-- フッター -->
                <div class="badge-footer">
                    発行: 保土ケ谷宿場まつり実行委員会
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
