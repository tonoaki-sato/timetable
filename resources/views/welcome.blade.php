<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-50 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>まつり総合管理システム 2026</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full flex flex-col font-sans antialiased">

    <!-- ナビゲーションバー -->
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">🏮</span>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-base tracking-tight text-white">まつり総合管理システム</span>
                            <span class="bg-indigo-600 text-indigo-100 text-[10px] px-2 py-0.5 rounded font-mono font-bold">2026年度</span>
                        </div>
                        <p class="text-[11px] text-slate-400">運行本部・タイムテーブル・人員機材動態・年間工程</p>
                    </div>
                </div>
                <div>
                    @auth
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition shadow-sm">
                            📊 本部ダッシュボードへ
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg text-xs font-medium transition cursor-pointer">
                                ログアウト
                            </button>
                        </form>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition shadow-sm">
                        🔐 本部スタッフログイン
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- ヒーローセクション -->
    <main class="flex-1">
        <div class="relative bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white py-16 sm:py-24 overflow-hidden border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 mb-4">
                    2026年10月16日(金) 〜 10月19日(月) 開催
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight mb-4">
                    まつりの安心・円滑な現場運行を、<br class="hidden sm:inline">リアルタイムに統合管理。
                </h1>
                <p class="max-w-2xl mx-auto text-sm sm:text-base text-slate-300 mb-8">
                    時間軸マトリクスによる運行把握、外部業者レンタル・自前資材のライフサイクル追跡、貸し切り駐車場制約監視、および現場スマホでのマイシフト確認を一元化。
                </p>

                <!-- ログイン / 案内アクションボタン -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    @auth
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-6 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg transition">
                        📊 本部運行ダッシュボードを開く
                    </a>
                    <a href="{{ route('timetable.index') }}" class="w-full sm:w-auto px-6 py-3.5 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-xl text-sm font-bold border border-slate-600 transition">
                        ⏱️ タイムテーブル（当日進行表）
                    </a>
                    @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold shadow-lg transition">
                        🔐 本部オペレーター ログイン
                    </a>
                    <a href="#field-guide" class="w-full sm:w-auto px-6 py-3.5 bg-slate-700/80 hover:bg-slate-600 text-slate-200 rounded-xl text-sm font-bold border border-slate-600 transition">
                        📱 現場スタッフ・ボランティアの方へ
                    </a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- 3大機能ハイライト -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- タイムテーブル -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-4">
                        ⏱️
                    </div>
                    <h3 class="font-bold text-base text-slate-900 mb-2">タイムテーブル（当日進行表）</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        時間スロット×担当レーンの俯瞰グリッドで運行状況を即座に把握。交通規制（通行止め）やござ市開催ステータスと完全連動。
                    </p>
                </div>

                <!-- 機材ライフサイクル -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-4">
                        🚚
                    </div>
                    <h3 class="font-bold text-base text-slate-900 mb-2">機材動態・駐車場制約監視</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        北川駐車場ほか3箇所の貸し切り時間外配置を自動検知。給水設備やカラーコーンの分散仮置き・最優先本設置を管理。
                    </p>
                </div>

                <!-- 現場マイシフト -->
                <div id="field-guide" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-4">
                        📱
                    </div>
                    <h3 class="font-bold text-base text-slate-900 mb-2">現場スマホ向けマイシフト</h3>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        ボランティア・現場スタッフはログイン不要の専用URL/QRコードから、自分の担当タスクのみを縦タイムラインで確認・完了報告可能。
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- フッター -->
    <footer class="bg-white border-t border-slate-200 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            &copy; 2026 まつり実行委員会 運行本部システム
        </div>
    </footer>
</body>

</html>