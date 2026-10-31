<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>セルフ登録受付停止中 - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col justify-center py-6 px-4 font-sans antialiased bg-gradient-to-b from-indigo-950 via-slate-900 to-slate-900 text-slate-100">

    <div class="w-full max-w-md mx-auto">
        <!-- ヘッダー -->
        <div class="text-center mb-6">
            <span class="text-4xl block mb-2">🏮</span>
            <h1 class="text-2xl font-black tracking-tight text-white">スタッフ・ボランティア登録</h1>
        </div>

        <!-- 案内カード -->
        <div class="bg-white text-slate-900 p-6 sm:p-8 rounded-3xl shadow-2xl border border-slate-200 text-center space-y-6">
            <!-- アイコン -->
            <div class="w-16 h-16 bg-amber-500/10 text-amber-500 rounded-full flex items-center justify-center mx-auto text-3xl border border-amber-500/20 shadow-sm">
                ⏳
            </div>

            <!-- ステータスタイトル -->
            <div>
                <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full mb-3">
                    {{ $status['label'] }}
                </span>
                <h2 class="text-lg font-bold text-slate-800">
                    現在、セルフ登録を受け付けておりません
                </h2>
            </div>

            <!-- 案内文 -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-600 leading-relaxed text-left">
                <p class="font-medium text-slate-800 mb-2">
                    {{ $status['message'] }}
                </p>
                <p class="text-[11px] text-slate-500">
                    ※スタッフ参加のご相談やご登録に関するお問い合わせは、まつり本部受付または担当役員まで直接ご連絡ください。
                </p>
            </div>

            <!-- すでに登録済みの方への案内 -->
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-500 mb-2">
                    すでにスタッフ登録がお済みの方は、お控えの専用マイタイムテーブルURLからご確認いただけます。
                </p>
            </div>
        </div>

        <!-- フッター -->
        <div class="text-center mt-6">
            <p class="text-[11px] text-slate-400">
                まつり運行本部システム
            </p>
        </div>
    </div>
</body>
</html>

