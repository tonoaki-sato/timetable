<x-layouts.app title="ユーザー詳細">
<div class="max-w-3xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">ユーザー詳細情報</h2>
            <p class="text-xs text-slate-500 mt-1">ID: #{{ $user->id }} のアカウント情報</p>
        </div>
        <a href="{{ route('users.index') }}" class="text-xs text-slate-500 hover:text-slate-800 transition">
            ← 一覧に戻る
        </a>
    </div>

    <!-- 成功通知メッセージ -->
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-medium">
        ✔ {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 space-y-6">
            <!-- 基本プロフィール -->
            <div class="flex items-center space-x-4 pb-6 border-b border-slate-100">
                <div class="w-14 h-14 bg-indigo-100 text-indigo-700 rounded-2xl flex items-center justify-center text-2xl font-bold">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                        <span>{{ $user->name }}</span>
                        @if($user->id === auth()->id())
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-normal">あなた</span>
                        @endif
                    </h3>
                    <p class="text-xs font-mono text-slate-500 mt-0.5">{{ $user->email }}</p>
                </div>
            </div>

            <!-- アカウント詳細属性 -->
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-3 bg-slate-50 rounded-xl">
                    <dt class="font-bold text-slate-500 mb-1">権限ロール</dt>
                    <dd class="text-sm font-bold text-slate-900">
                        @if($user->isAdmin())
                        <span class="text-indigo-600">🛡️ システム管理者 (admin)</span>
                        @else
                        <span class="text-slate-700">本部オペレーター (operator)</span>
                        @endif
                    </dd>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl">
                    <dt class="font-bold text-slate-500 mb-1">アカウント状態</dt>
                    <dd class="text-sm font-bold">
                        @if($user->is_active)
                        <span class="text-emerald-600">● 有効 (ログイン可能)</span>
                        @else
                        <span class="text-red-600">× 停止中 (アクセス遮断)</span>
                        @endif
                    </dd>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl">
                    <dt class="font-bold text-slate-500 mb-1">最終ログイン日時</dt>
                    <dd class="text-xs font-mono font-bold text-slate-800">
                        {{ $user->last_login_at?->format('Y/m/d H:i:s') ?? '未ログイン' }}
                    </dd>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl">
                    <dt class="font-bold text-slate-500 mb-1">アカウント作成日時</dt>
                    <dd class="text-xs font-mono font-bold text-slate-800">
                        {{ $user->created_at?->format('Y/m/d H:i') ?? '-' }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- フッターアクションボタン -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <a href="{{ route('users.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 hover:bg-slate-100 transition">
                一覧に戻る
            </a>

            <div class="flex items-center space-x-2">
                @can('update', $user)
                <a href="{{ route('users.edit', $user) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-sm transition">
                    ✏️ 編集する
                </a>
                @endcan

                @can('delete', $user)
                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('ユーザー「{{ $user->name }}」を完全に削除しますか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        🗑️ 削除
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>

</div>
</x-layouts.app>