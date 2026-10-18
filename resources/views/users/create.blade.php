<x-layouts.app title="新規ユーザー登録">
<div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">➕ 新規ユーザー登録</h2>
        <a href="{{ route('users.index') }}" class="text-xs text-slate-500 hover:text-slate-800 transition">
            ← 一覧に戻る
        </a>
    </div>

    <!-- エラー表示 -->
    @if ($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl space-y-1">
        <div class="font-bold">⚠️ 入力内容を確認してください</div>
        @foreach ($errors->all() as $error)
        <div>・{{ $error }}</div>
        @endforeach
    </div>
    @endif

    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('users.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- 氏名 -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">氏名 <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="山田 太郎">
            </div>

            <!-- メールアドレス -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="user@example.com">
            </div>

            <!-- 権限ロール -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">権限ロール <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                        <input type="radio" name="role" value="operator" {{ old('role', 'operator') === 'operator' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2.5">
                            <span class="block text-xs font-bold text-slate-800">本部オペレーター</span>
                            <span class="block text-[11px] text-slate-500">タイムテーブル・機材管理・閲覧</span>
                        </div>
                    </label>

                    <label class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                        <input type="radio" name="role" value="admin" {{ old('role') === 'admin' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2.5">
                            <span class="block text-xs font-bold text-slate-800">システム管理者</span>
                            <span class="block text-[11px] text-slate-500">ユーザー管理含む全権限</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- パスワード -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">初期パスワード <span class="text-red-500">* (8文字以上)</span></label>
                <input type="password" id="password" name="password" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="••••••••">
            </div>

            <!-- パスワード確認 -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1">パスワード（確認用） <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="••••••••">
            </div>

            <!-- アカウント有効化 -->
            <div class="pt-2">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700">アカウントを直ちに有効化する</span>
                </label>
            </div>

            <!-- 送信ボタン -->
            <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                    キャンセル
                </a>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition">
                    ユーザーを登録する
                </button>
            </div>
        </form>
    </div>

</div>
</x-layouts.app>