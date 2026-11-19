<x-layouts.app title="ユーザー管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ヘッダーエリア -->
    <x-page-header
        title="ユーザー管理"
        icon="👥"
        description="本部管理者およびオペレーターアカウントの閲覧・管理">
        <x-slot:breadcrumbs>
            <span class="text-slate-800 font-bold shrink-0">ユーザー管理</span>
        </x-slot:breadcrumbs>
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                全 {{ $users->total() }} アカウント
            </span>
        </x-slot:badges>
        @can('create', App\Models\User::class)
        <x-slot:actions>
            <x-ui.button variant="header-action" :href="route('users.create')">
                <span>➕</span>
                <span>新規ユーザー登録</span>
            </x-ui.button>
        </x-slot:actions>
        @endcan
    </x-page-header>

    <!-- 検索・絞り込みフォーム -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('direction'))
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <!-- キーワード検索 -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">氏名 / メールアドレス</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="検索キーワード...">
            </div>

            <!-- 権限ロール -->
            <div>
                <label for="role" class="block text-[11px] font-bold text-slate-600 mb-1">権限ロール</label>
                <select id="role" name="role" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべてのロール</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>管理者 (admin)</option>
                    <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>オペレーター (operator)</option>
                </select>
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">アカウント状態</label>
                <select id="status" name="status" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての状態</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>有効</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>

            <!-- 検索・クリア・表示件数ボタン -->
            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition whitespace-nowrap cursor-pointer">
                    🔍 絞り込み
                </button>
                <a href="{{ route('users.index') }}" class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition text-center whitespace-nowrap">
                    リセット
                </a>
                <x-ui.per-page-select :default="15" />
            </div>
        </form>
    </div>

    <!-- ユーザー一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold select-none">
                    <tr>
                        <x-ui.sortable-th column="id" label="ID" defaultSort="id" defaultDirection="desc" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="name" label="氏名" defaultSort="id" defaultDirection="desc" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="email" label="メールアドレス" defaultSort="id" defaultDirection="desc" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="role" label="ロール" defaultSort="id" defaultDirection="desc" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="is_active" label="状態" defaultSort="id" defaultDirection="desc" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="last_login_at" label="最終ログイン" defaultSort="id" defaultDirection="desc" class="px-3.5 py-2.5" />
                        <th class="px-3.5 py-2.5 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-3.5 py-2.5 font-mono text-slate-400 whitespace-nowrap">#{{ $user->id }}</td>
                        <td class="px-3.5 py-2.5 font-bold whitespace-nowrap">
                            <a href="{{ route('users.show', $user) }}" class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition">
                                {{ $user->name }}
                            </a>
                            @if($user->id === auth()->id())
                            <span class="ml-1 text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-normal">あなた</span>
                            @endif
                        </td>
                        <td class="px-3.5 py-2.5 font-mono text-slate-600">{{ $user->email }}</td>
                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                            @if($user->isAdmin())
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                🛡️ 管理者
                            </span>
                            @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                オペレーター
                            </span>
                            @endif
                        </td>
                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                            @if($user->is_active)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ● 有効
                            </span>
                            @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                × 停止中
                            </span>
                            @endif
                        </td>
                        <td class="px-3.5 py-2.5 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                            {{ $user->last_login_at?->format('Y/m/d H:i') ?? '未ログイン' }}
                        </td>
                        <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1.5">
                                @can('update', $user)
                                <x-ui.button variant="edit" size="xs" :href="route('users.edit', $user)">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </x-ui.button>
                                @endcan

                                @can('delete', $user)
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" data-confirm="ユーザー「{{ $user->name }}」を削除しますか？">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button variant="delete" size="xs" type="submit">
                                        <span>🗑️</span>
                                        <span>削除</span>
                                    </x-ui.button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <x-ui.empty-state icon="👥" title="条件に一致するユーザーは見つかりませんでした。" />
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