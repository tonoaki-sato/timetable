<x-layouts.app title="現場スタッフ・ボランティア管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ヘッダー -->
    <x-page-header
        title="現場スタッフ・ボランティア管理"
        icon="👷"
        description="まつり運行を支える現場スタッフ・ボランティアの台帳管理および担当タスクの確認を行います。">
        <x-slot:breadcrumbs>
            <span class="text-slate-800 font-bold shrink-0">スタッフ管理</span>
        </x-slot:breadcrumbs>
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                登録 {{ $staffMembers->total() }} 名
            </span>
        </x-slot:badges>
        <x-slot:actions>
            <!-- 名札一括PDFダウンロード -->
            <a href="{{ route('staff.print-cards') }}"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0"
                title="全スタッフの名札カードをA4面付けPDFでダウンロード">
                <span>📄</span>
                <span>名札PDF</span>
            </a>

            <!-- CSVエクスポート -->
            <a href="{{ route('staff.export-csv') }}"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>📥</span>
                <span>CSV出力</span>
            </a>

            <!-- 登録案内文コピーボタン -->
            <button type="button" id="copyInviteBtn" data-join-url="{{ route('staff.join') }}"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer whitespace-nowrap shrink-0">
                <span>📋</span>
                <span>登録案内文コピー</span>
            </button>

            @can('create', App\Models\Staff::class)
            <a href="{{ route('staff.create') }}"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規スタッフ登録</span>
            </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <!-- 検索・絞り込みフォーム -->
    <div class="bg-white p-3 sm:p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- フリーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="氏名・フリガナ・所属"
                    class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">稼働状態</label>
                <select id="status" name="status" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての状態</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>稼働可能</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>

            <!-- ボタン -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition whitespace-nowrap">
                    🔍 絞り込み
                </button>
                <a href="{{ route('staff.index') }}"
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-medium transition text-center whitespace-nowrap">
                    リセット
                </a>
            </div>
        </form>
    </div>

    <!-- スタッフ一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 select-none">
                    <tr>
                        <th class="px-3.5 py-2 font-bold whitespace-nowrap">ID</th>
                        <th class="px-3.5 py-2 font-bold">氏名</th>
                        <th class="px-3.5 py-2 font-bold whitespace-nowrap">フリガナ</th>
                        <th class="px-3.5 py-2 font-bold">所属団体・地域会等</th>
                        <th class="px-3.5 py-2 font-bold whitespace-nowrap">稼働状態</th>
                        <th class="px-3.5 py-2 font-bold text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staffMembers as $staff)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="px-3.5 py-2 font-mono text-slate-400 whitespace-nowrap">#{{ $staff->id }}</td>
                        <td class="px-3.5 py-2 font-bold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('staff.show', $staff) }}" class="hover:text-indigo-600 transition">
                                {{ $staff->name }}
                            </a>
                        </td>
                        <td class="px-3.5 py-2 text-slate-500 font-mono text-[11px] whitespace-nowrap">{{ $staff->kana ?? '-' }}</td>
                        <td class="px-3.5 py-2 text-slate-700">{{ $staff->affiliation ?? '未設定' }}</td>
                        <td class="px-3.5 py-2 whitespace-nowrap">
                            @if($staff->is_active)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ● 稼働可能
                            </span>
                            @else
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                停止中
                            </span>
                            @endif
                        </td>
                        <td class="px-3.5 py-2 text-right space-x-1 whitespace-nowrap">
                            @can('update', $staff)
                            <a href="{{ route('staff.edit', $staff) }}"
                                class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition whitespace-nowrap">
                                編集
                            </a>
                            @endcan

                            @can('delete', $staff)
                            <form action="{{ route('staff.destroy', $staff) }}" method="POST" class="inline"
                                data-confirm="スタッフ「{{ $staff->name }}」を削除しますか？&#10;（関連するタスクのアサインも解除されます）">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-[11px] font-medium transition cursor-pointer whitespace-nowrap">
                                    削除
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">
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

<!-- 外部JS（CSP準拠） -->
<script src="{{ asset('js/staff-management.js') }}" defer></script>
</x-layouts.app>

