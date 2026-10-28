<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>登録完了 - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col justify-center py-6 px-4 font-sans antialiased bg-gradient-to-b from-indigo-950 via-slate-900 to-slate-900 text-slate-100">

    <div class="w-full max-w-md mx-auto">
        <!-- 完了アイコン & ヘッダー -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto text-3xl mb-3 border border-emerald-500/40 shadow-lg shadow-emerald-500/10">
                ✓
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white">スタッフ登録が完了しました！</h1>
            <p class="mt-1 text-xs text-slate-300">
                {{ $staff->name }} さん、ご協力ありがとうございます！
            </p>
        </div>

        <!-- カード -->
        <div class="bg-white text-slate-900 p-6 sm:p-8 rounded-3xl shadow-2xl border border-slate-200 space-y-6">

            <!-- メインCTA: マイタイムテーブルを見る -->
            <div>
                <a href="{{ $staff->my_shift_url }}"
                   class="w-full py-4 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-base font-bold shadow-xl shadow-indigo-600/30 transition transform active:scale-[0.98] flex items-center justify-center space-x-2">
                    <span>📅</span>
                    <span>私のタイムテーブルを見る</span>
                    <span>→</span>
                </a>
            </div>

            <!-- URL保存のお願いセクション -->
            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 space-y-3">
                <div class="flex items-center space-x-2 text-amber-800 text-xs font-bold">
                    <span>⚠️</span>
                    <span>当日のためにURLを保存してください</span>
                </div>
                <p class="text-xs text-amber-900/80 leading-relaxed">
                    まつり当日、あなた専用のタイムテーブル確認用ページへアクセスできるよう、以下のボタンからURLをコピーしてメモ帳等に保存をお願いいたします。
                </p>

                <!-- 専用URLコピーボタン -->
                <button type="button" id="copyUrlBtn"
                        class="w-full py-3 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 shadow-sm cursor-pointer">
                    <span>📋</span>
                    <span>専用URLをコピーする</span>
                </button>
            </div>

            <!-- 登録情報確認 -->
            <div class="border-t border-slate-100 pt-4 text-xs text-slate-500 space-y-1.5">
                <div class="flex justify-between">
                    <span class="text-slate-400">お名前:</span>
                    <span class="font-bold text-slate-700">{{ $staff->name }} ({{ $staff->kana }})</span>
                </div>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-[11px] text-slate-400">
                ※ブラウザの「ホーム画面に追加」または「ブックマーク」もご利用いただけます。
            </p>
        </div>
    </div>

    <!-- 外部JS（CSP準拠） -->
    <script src="{{ asset('js/staff-myshift.js') }}" defer></script>
</body>
</html>
