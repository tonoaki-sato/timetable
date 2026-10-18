<x-layouts.app title="現場スタッフ・ボランティア管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- ヘッダー -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">👷 現場スタッフ・ボランティア管理</h2>
            <p class="text-xs text-slate-500 mt-1">
                まつり運行を支える現場スタッフ・ボランティアの台帳管理および担当タスクの確認を行います。
            </p>
        </div>

        @can('create', App\Models\Staff::class)
        <a href="{{ route('staff.create') }}"
            class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
            <span>➕</span>
            <span>新規スタッフ登録</span>
        </a>
        @endcan
    </div>

    <!-- 検索・絞り込みフォーム -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- フリーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="氏名・フリガナ・所属団体"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">稼働状態</label>
                <select id="status" name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての状態</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>稼働可能</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>

            <!-- ボタン -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition">
                    🔍 絞り込み
                </button>
                <a href="{{ route('staff.index') }}"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- スタッフ一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-4 py-3 font-bold">ID</th>
                        <th class="px-4 py-3 font-bold">氏名</th>
                        <th class="px-4 py-3 font-bold">フリガナ</th>
                        <th class="px-4 py-3 font-bold">所属団体・地域会等</th>
                        <th class="px-4 py-3 font-bold">稼働状態</th>
                        <th class="px-4 py-3 font-bold">割当タスク数</th>
                        <th class="px-4 py-3 font-bold text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staffMembers as $staff)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="px-4 py-3 font-mono text-slate-400">#{{ $staff->id }}</td>
                        <td class="px-4 py-3 font-bold text-slate-900">
                            <a href="{{ route('staff.show', $staff) }}" class="hover:text-indigo-600 transition">
                                {{ $staff->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-[11px]">{{ $staff->kana ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $staff->affiliation ?? '未設定' }}</td>
                        <td class="px-4 py-3">
                            @if($staff->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ● 稼働可能
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                停止中
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-700">
                            {{ $staff->assignments_count ?? 0 }} <span class="text-[10px] text-slate-400">件</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                            <a href="{{ route('staff.show', $staff) }}"
                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-medium transition">
                                詳細
                            </a>

                            @can('update', $staff)
                            <a href="{{ route('staff.edit', $staff) }}"
                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition">
                                編集
                            </a>
                            @endcan

                            @can('delete', $staff)
                            <form action="{{ route('staff.destroy', $staff) }}" method="POST" class="inline"
                                onsubmit="return confirm('スタッフ「{{ $staff->name }}」を削除しますか？\n（関連するタスクのアサインも解除されます）');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-600 rounded text-[11px] font-medium transition cursor-pointer">
                                    削除
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                            <span class="text-3xl block mb-2">👷</span>
                            登録されているスタッフは見つかりませんでした。
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staffMembers->hasPages())
        <div class="p-4 border-t border-slate-200 bg-slate-50/50">
            {{ $staffMembers->links() }}
        </div>
        @endif
    </div>

</div>
</x-layouts.app>
