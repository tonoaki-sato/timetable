<x-layouts.app title="ユーザー編集">
<div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">✏️ ユーザー情報編集</h2>
        <a href="{{ route('users.show', $user) }}" class="text-xs text-slate-500 hover:text-slate-800 transition">
            ← 詳細に戻る
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
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- 氏名 -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">氏名 <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- メールアドレス -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 権限ロール (adminのみ変更可能、本人は表示のみ) -->
            @if(auth()->user()->isAdmin())
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">権限ロール <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                        <input type="radio" name="role" value="operator" {{ old('role', $user->role) === 'operator' ? 'checked' : '' }}
                            {{ $user->id === auth()->id() ? 'disabled' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2.5">
                            <span class="block text-xs font-bold text-slate-800">本部オペレーター</span>
                            <span class="block text-[11px] text-slate-500">タイムテーブル・機材管理・閲覧</span>
                        </div>
                    </label>

                    <label class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                        <input type="radio" name="role" value="admin" {{ old('role', $user->role) === 'admin' ? 'checked' : '' }} class="text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-2.5">
                            <span class="block text-xs font-bold text-slate-800">システム管理者</span>
                            <span class="block text-[11px] text-slate-500">全権限</span>
                        </div>
                    </label>
                </div>
                @if($user->id === auth()->id())
                <p class="text-[11px] text-amber-600 mt-1">※自分自身のアカウントのロールを降格することはできません。</p>
                @endif
            </div>

            <!-- アカウント有効化トグル -->
            <div>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                        {{ $user->id === auth()->id() ? 'disabled' : '' }} class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700">アカウントを有効にする</span>
                </label>
                @if($user->id === auth()->id())
                <p class="text-[11px] text-amber-600 mt-1">※自分自身のアカウントを停止することはできません。</p>
                @endif
            </div>
            @else
            <!-- operatorによる自身の編集時はロールを読み取り専用表示 -->
            <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                <span class="font-bold text-slate-500">現在の権限ロール:</span>
                <span class="font-bold text-slate-800 ml-2">本部オペレーター (operator)</span>
                <p class="text-[11px] text-slate-400">※ロールの変更はシステム管理者にお問い合わせください。</p>
            </div>
            @endif

            <!-- パスワード変更 (任意) -->
            <div class="pt-4 border-t border-slate-100 space-y-3">
                <span class="block text-xs font-bold text-slate-800">パスワード変更（変更する場合のみ入力）</span>
                <div>
                    <label for="password" class="block text-[11px] font-bold text-slate-600 mb-1">新しいパスワード (8文字以上)</label>
                    <input type="password" id="password" name="password"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="変更しない場合は空欄">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-[11px] font-bold text-slate-600 mb-1">新しいパスワード（確認用）</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="変更しない場合は空欄">
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-100">
                <a href="{{ route('users.show', $user) }}" class="px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                    キャンセル
                </a>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition">
                    更新を保存する
                </button>
            </div>
        </form>
    </div>

</div>
</x-layouts.app>