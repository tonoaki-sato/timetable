<x-layouts.app title="ユーザー管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダーエリア -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">👥 ユーザー管理</h2>
            <p class="text-xs text-slate-500 mt-1">本部管理者およびオペレーターアカウントの閲覧・管理</p>
        </div>

        @can('create', App\Models\User::class)
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
            ➕ 新規ユーザー登録
        </a>
        @endcan
    </div>

    <!-- 成功通知メッセージ -->
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-medium">
        ✔ {{ session('success') }}
    </div>
    @endif

    <!-- 検索・絞り込みフォーム -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- キーワード検索 -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">氏名 / メールアドレス</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="検索キーワード...">
            </div>

            <!-- 権限ロール -->
            <div>
                <label for="role" class="block text-[11px] font-bold text-slate-600 mb-1">権限ロール</label>
                <select id="role" name="role" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべてのロール</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>管理者 (admin)</option>
                    <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>オペレーター (operator)</option>
                </select>
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">アカウント状態</label>
                <select id="status" name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての状態</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>有効</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>

            <!-- 検索・クリアボタン -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition">
                    🔍 絞り込み
                </button>
                <a href="{{ route('users.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- ユーザー一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold">ID</th>
                        <th class="px-4 py-3 font-bold">氏名</th>
                        <th class="px-4 py-3 font-bold">メールアドレス</th>
                        <th class="px-4 py-3 font-bold">ロール</th>
                        <th class="px-4 py-3 font-bold">状態</th>
                        <th class="px-4 py-3 font-bold">最終ログイン</th>
                        <th class="px-4 py-3 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="px-4 py-3 font-mono text-slate-400">#{{ $user->id }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                            <span class="ml-1 text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-normal">あなた</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if($user->isAdmin())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                🛡️ 管理者
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                オペレーター
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ● 有効
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                × 停止中
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-[11px]">
                            {{ $user->last_login_at?->format('Y/m/d H:i') ?? '未ログイン' }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <a href="{{ route('users.show', $user) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-medium transition">
                                詳細
                            </a>

                            @can('update', $user)
                            <a href="{{ route('users.edit', $user) }}" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                編集
                            </a>
                            @endcan

                            @can('delete', $user)
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" data-confirm="ユーザー「{{ $user->name }}」を削除しますか？">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 rounded text-[11px] font-bold transition">
                                    削除
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400 text-xs">
                            条件に一致するユーザーは見つかりませんでした。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>
</x-layouts.app>