<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>スタッフ登録 - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- LIFF SDK -->
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
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

            <form id="staffJoinForm" action="{{ route('staff.join.store') }}" method="POST"
                  data-liff-id="{{ $liffId }}"
                  data-default-avatar="https://ui-avatars.com/api/?name=LINE&background=06C755&color=fff">
                @csrf

                <!-- LINE自動取得情報の隠しフィールド -->
                <input type="hidden" id="line_id_token" name="line_id_token" value="{{ old('line_id_token') }}">
                <input type="hidden" id="line_display_name" name="line_display_name" value="{{ old('line_display_name') }}">
                <input type="hidden" id="line_picture_url" name="line_picture_url" value="{{ old('line_picture_url') }}">

                <!-- LINE連携プレビューバッジ（JSで自動表示） -->
                <div id="lineProfilePreview" class="hidden mb-5 p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200 flex items-center space-x-3">
                    <img id="lineAvatar" class="w-11 h-11 rounded-full border-2 border-emerald-400 bg-white shadow-sm flex-shrink-0" src="" alt="LINE Avatar">
                    <div class="overflow-hidden">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 mb-0.5">
                            ● LINE連携完了
                        </span>
                        <div id="lineNameText" class="text-xs font-bold text-slate-800 truncate"></div>
                    </div>
                </div>

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

                    <!-- 非LINE環境用フォールバック入力（初期非表示） -->
                    <div id="manualLineInputArea" class="hidden pt-1">
                        <label for="manual_line_display_name" class="block text-xs font-bold text-slate-700 mb-1.5">
                            LINEグループでの表示名 <span class="text-slate-400 font-normal">（任意）</span>
                        </label>
                        <input type="text" id="manual_line_display_name" name="manual_line_display_name" value="{{ old('manual_line_display_name') }}"
                               placeholder="例）やまちゃん"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <p class="mt-1 text-[11px] text-slate-500">
                            ※LINEグループでのメンション指示出しに使用します。
                        </p>
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

    <!-- 外部JS（CSP準拠） -->
    <script src="{{ asset('js/staff-join-liff.js') }}" defer></script>
</body>
</html>
