<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>タイムテーブル確認・再検索 - まつり運行システム</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col justify-center py-6 px-4 font-sans antialiased bg-gradient-to-b from-indigo-950 via-slate-900 to-slate-900 text-slate-100">

    <div class="w-full max-w-md mx-auto">
        <!-- ヘッダー -->
        <div class="text-center mb-6">
            <span class="text-4xl block mb-2">🔍</span>
            <h1 class="text-2xl font-black tracking-tight text-white">タイムテーブル再検索</h1>
            <p class="mt-2 text-xs text-slate-300">
                登録済みのお名前を入力して、ご自身のタイムテーブル画面を開きます。
            </p>
        </div>

        <!-- 照合カード -->
        <div class="bg-white text-slate-900 p-6 sm:p-8 rounded-3xl shadow-2xl border border-slate-200">
            @if ($errors->any())
            <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(isset($candidates) && $candidates->isNotEmpty())
            <!-- 同姓同名の候補リスト -->
            <div class="space-y-4">
                <p class="text-xs text-slate-600 font-bold">
                    同姓同名のスタッフが複数見つかりました。ご自身のものを選択してください：
                </p>
                <div class="divide-y divide-slate-100 border border-slate-200 rounded-2xl overflow-hidden">
                    @foreach($candidates as $candidate)
                    <a href="{{ $candidate->my_shift_url }}"
                       class="p-4 block bg-white hover:bg-slate-50 transition flex items-center justify-between">
                        <div>
                            <div class="font-bold text-sm text-slate-800">{{ $candidate->name }}</div>
                            <div class="text-xs text-slate-500">
                                @if($candidate->line_display_name)
                                    LINE: {{ $candidate->line_display_name }}
                                @elseif($candidate->affiliation)
                                    所属: {{ $candidate->affiliation }}
                                @else
                                    登録日: {{ $candidate->created_at?->format('n月j日') }}
                                @endif
                            </div>
                        </div>
                        <span class="text-indigo-600 text-xs font-bold">選択 →</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @else
            <!-- 検索フォーム -->
            <form action="{{ route('staff.myshift.find.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                        お名前（氏名） <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           placeholder="例）山田 太郎"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-base text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <div>
                    <label for="kana" class="block text-xs font-bold text-slate-700 mb-1.5">
                        フリガナ（カタカナ） <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text" id="kana" name="kana" value="{{ old('kana') }}" required inputmode="katakana"
                           placeholder="例）ヤマダ タロウ"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-base text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/30 transition transform active:scale-[0.98] cursor-pointer flex items-center justify-center space-x-2">
                        <span>タイムテーブルを開く</span>
                        <span>→</span>
                    </button>
                </div>
            </form>
            @endif
        </div>

        <!-- 新規登録案内 -->
        <div class="text-center mt-6 space-y-2">
            <p class="text-xs text-slate-400">
                まだスタッフ登録がお済みでない方は
            </p>
            <a href="{{ route('staff.join') }}" class="inline-block text-xs text-indigo-300 hover:text-white underline font-bold transition">
                新規スタッフ登録フォームはこちら
            </a>
        </div>
    </div>

</body>
</html>
