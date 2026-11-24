<x-layouts.app title="現場スタッフ・ボランティア管理">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ヘッダー -->
    <x-page-header
        title="現場スタッフ・ボランティア管理"
        icon="👷"
        description="現場スタッフ・ボランティアの台帳と担当タスクを管理します。">
        <x-slot:breadcrumbs>
            <span class="text-slate-800 font-bold shrink-0">スタッフ管理</span>
        </x-slot:breadcrumbs>
        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                登録 {{ $staffMembers->total() }} 名
            </span>
        </x-slot:badges>
        <x-slot:actions>
            <!-- スタッフ一覧PDFダウンロード -->
            <x-ui.button variant="header-action" id="btn-download-staff-pdf" :href="route('staff.pdf', request()->query())" title="現在の検索・絞り込み条件でスタッフ一覧をA4縦PDFダウンロード">
                <span>📄</span>
                <span>スタッフ一覧PDF</span>
            </x-ui.button>

            <!-- 名札一括PDFダウンロード -->
            <x-ui.button variant="header-action" :href="route('staff.print-cards')" title="全スタッフの名札カードをA4面付けPDFでダウンロード">
                <span>📄</span>
                <span>名札PDF</span>
            </x-ui.button>

            <!-- CSVエクスポート -->
            <x-ui.button variant="header-action" :href="route('staff.export-csv')">
                <span>📥</span>
                <span>CSV出力</span>
            </x-ui.button>

            <!-- 登録案内文コピーボタン -->
            <x-ui.button variant="header-action" id="copyInviteBtn" data-join-url="{{ route('staff.join') }}">
                <span>📋</span>
                <span>登録案内文コピー</span>
            </x-ui.button>

            @can('create', App\Models\Staff::class)
            <x-ui.button variant="header-action" :href="route('staff.create')">
                <span>➕</span>
                <span>新規スタッフ登録</span>
            </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <!-- 検索・絞り込みフォーム -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('direction'))
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <!-- フリーワード -->
            <div>
                <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">フリーワード</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="氏名・フリガナ・所属"
                    class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- 属性 -->
            <div>
                <label for="staff_attribute" class="block text-[11px] font-bold text-slate-600 mb-1">属性</label>
                <select id="staff_attribute" name="staff_attribute" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての属性</option>
                    @foreach($staffAttributeFilterOptions as $value => $label)
                        <option value="{{ $value }}" {{ ($staffAttribute ?? request('staff_attribute')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- ステータス -->
            <div>
                <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">稼働状態</label>
                <select id="status" name="status" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">すべての状態</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>稼働可能</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停止中</option>
                </select>
            </div>

            <!-- ボタン & 表示件数 -->
            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition whitespace-nowrap cursor-pointer">
                    🔍 絞り込み
                </button>
                <a href="{{ route('staff.index') }}"
                    class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition text-center whitespace-nowrap">
                    リセット
                </a>
                <x-ui.per-page-select :default="15" />
            </div>
        </form>
    </div>

    <!-- スタッフ一覧テーブル -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold select-none">
                    <tr>
                        <x-ui.sortable-th column="id" label="ID" defaultSort="name" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="name" label="氏名" defaultSort="name" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="kana" label="フリガナ" defaultSort="name" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="affiliation" label="所属団体・地域会等" defaultSort="name" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="staff_attribute" label="属性" defaultSort="name" class="px-3.5 py-2.5" />
                        <x-ui.sortable-th column="is_active" label="稼働状態" defaultSort="name" class="px-3.5 py-2.5" />
                        <th class="px-3.5 py-2.5 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($staffMembers as $staff)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-3.5 py-2.5 font-mono text-slate-400 whitespace-nowrap">#{{ $staff->id }}</td>
                        <td class="px-3.5 py-2.5 font-bold whitespace-nowrap">
                            <a href="{{ route('staff.show', $staff) }}" class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition">
                                {{ $staff->name }}
                            </a>
                        </td>
                        <td class="px-3.5 py-2.5 text-slate-500 font-mono text-[11px] whitespace-nowrap">{{ $staff->kana ?: '—' }}</td>
                        <td class="px-3.5 py-2.5 text-slate-700">{{ $staff->affiliation ?: '—' }}</td>
                        <td class="px-3.5 py-2.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $staff->presenter()->attributeBadgeClasses() }}">
                                {{ $staff->presenter()->attributeLabel() }}
                            </span>
                        </td>
                        <td class="px-3.5 py-2.5 whitespace-nowrap">
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
                        <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1.5">
                                @can('update', $staff)
                                <x-ui.button variant="edit" size="xs" :href="route('staff.edit', $staff)">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </x-ui.button>
                                @endcan

                                @can('delete', $staff)
                                <form action="{{ route('staff.destroy', $staff) }}" method="POST" class="inline"
                                    data-confirm="スタッフ「{{ $staff->name }}」を削除しますか？&#10;（関連するタスクのアサインも解除されます）">
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
                            <x-ui.empty-state icon="👷" title="登録されているスタッフは見つかりませんでした。" />
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

