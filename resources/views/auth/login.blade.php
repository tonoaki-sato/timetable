<x-layouts.guest title="本部ログイン - まつり総合管理システム">

    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold tracking-tight text-slate-900">
            運行本部 ログイン認証
        </h2>
        <p class="mt-1 text-xs text-slate-500">
            実行委員会 管理者および本部オペレーター専用
        </p>
    </div>

    <!-- ログアウトや完了メッセージ -->
    @if(session('status'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-medium flex items-center space-x-2">
        <span>✔</span>
        <span>{{ session('status') }}</span>
    </div>
    @endif

    <!-- 全体エラー表示 -->
    @if ($errors->any())
    <div class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
        <div class="font-bold flex items-center space-x-1.5 text-red-800">
            <span>⚠️</span>
            <span>ログインできませんでした</span>
        </div>
        @foreach ($errors->all() as $error)
        <div class="pl-5">・{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
        @csrf

        <!-- メールアドレス -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700">
                メールアドレス
            </label>
            <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required autofocus
                    value="{{ old('email') }}"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border @error('email') border-red-400 @else border-slate-300 @enderror rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    placeholder="admin@example.com">
            </div>
        </div>

        <!-- パスワード -->
        <div>
            <label for="password" class="block text-xs font-bold text-slate-700">
                パスワード
            </label>
            <div class="mt-1">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border @error('password') border-red-400 @else border-slate-300 @enderror rounded-xl text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    placeholder="••••••••">
            </div>
        </div>

        <!-- ログイン状態を保持する -->
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center space-x-2 cursor-pointer select-none">
                <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                <span class="text-xs text-slate-600 font-medium">ログイン状態を保持する</span>
            </label>
        </div>

        <!-- ログインボタン -->
        <div class="pt-2">
            <button type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-sm rounded-xl shadow-md transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer">
                ログイン
            </button>
        </div>
    </form>

    <!-- デモ・開発用アカウント案内 -->
    <div class="mt-6 p-3 bg-slate-50 border border-slate-200 rounded-xl text-[11px] text-slate-600">
        <div class="font-bold text-slate-700 mb-1 flex items-center space-x-1">
            <span>🔑</span>
            <span>テスト用初期アカウント（共通パスワード: <code class="bg-white px-1 py-0.5 rounded border border-slate-300 text-slate-800 font-mono">password</code>）</span>
        </div>
        <div class="grid grid-cols-1 gap-1 text-[10px] mt-1.5 text-slate-500 font-mono">
            <div>・管理者: <span class="text-slate-800 font-bold select-all">admin@example.com</span></div>
            <div>・オペレーター: <span class="text-slate-800 font-bold select-all">operator@example.com</span></div>
        </div>
    </div>

    <!-- 戻るリンク -->
    <div class="mt-5 text-center border-t border-slate-100 pt-4">
        <a href="{{ route('home') }}" class="text-xs text-slate-500 hover:text-indigo-600 transition font-medium">
            ← トップページに戻る
        </a>
    </div>

</x-layouts.guest>