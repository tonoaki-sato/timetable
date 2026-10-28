<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>スタッフ登録 - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col justify-center py-6 px-4 font-sans antialiased bg-gradient-to-b from-indigo-900 via-slate-900 to-slate-900 text-slate-100">

    <div class="w-full max-w-md mx-auto">
        <!-- ヘッダー -->
        <div class="text-center mb-6">
            <span class="text-4xl block mb-2">🏮</span>
            <h1 class="text-2xl font-black tracking-tight text-white">スタッフ・ボランティア登録</h1>
            <p class="mt-2 text-xs text-slate-300">
                お名前をご入力ください（約15秒で完了）。<br>登録後、当日のタイムテーブル確認ページが開きます。
            </p>
        </div>

        <!-- 登録カード -->
        <div class="bg-white text-slate-900 p-6 sm:p-8 rounded-3xl shadow-2xl border border-slate-200">
            <!-- エラー表示 -->
            @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form id="staffJoinForm" action="{{ route('staff.join.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <!-- お名前（漢字） -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                            お名前（氏名） <span class="text-rose-500 font-bold">*必須</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               placeholder="例）山田 太郎"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-base text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>

                    <!-- フリガナ（カタカナ） -->
                    <div>
                        <label for="kana" class="block text-xs font-bold text-slate-700 mb-1.5">
                            フリガナ（全角カナ） <span class="text-rose-500 font-bold">*必須</span>
                        </label>
                        <input type="text" id="kana" name="kana" value="{{ old('kana') }}" required inputmode="katakana"
                               placeholder="例）ヤマダ タロウ"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-base text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>

                <!-- 登録ボタン -->
                <div class="mt-6">
                    <button type="submit" id="submitBtn"
                            class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold tracking-wide shadow-lg shadow-indigo-600/30 transition transform active:scale-[0.98] cursor-pointer flex items-center justify-center space-x-2">
                        <span>登録を完了してタイムテーブルを確認</span>
                        <span>→</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- すでに登録済みの方への案内 -->
        <div class="text-center mt-6">
            <p class="text-xs text-indigo-200">
                すでに登録済みでマイシフトURLがご不明な場合は、本部受付までお申し出ください。
            </p>
        </div>
    </div>
</body>
</html>
