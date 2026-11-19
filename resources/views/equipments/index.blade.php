<x-layouts.app :title="$indexTitle">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- 1. ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$indexTitle"
        icon="📦"
        :description="$indexDesc">
        <x-slot:breadcrumbs>
            @if($isTimetableScoped)
                <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>⏱️</span>
                    <span>運行情報一覧</span>
                </a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('timetables.show', $timetable) }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>📅</span>
                    <span>{{ $timetable->name }}</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">📦 機材・備品一覧</span>
            @else
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">📦 機材・備品一覧</span>
            @endif
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if($isTimetableScoped)
                <x-ui.button variant="header-action" :href="route('timetables.logistics.print', $timetable)" title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                    <span>📄</span>
                    <span>指示書PDF</span>
                </x-ui.button>
            @endif
            <x-ui.button variant="header-action" :href="$createUrl">
                <span>➕</span>
                <span>新規備品を追加</span>
            </x-ui.button>
        </x-slot:actions>
    </x-page-header>

    <!-- タイムテーブルスコープ時のみコンテキストバーを表示 -->
    @if($isTimetableScoped)
        <x-timetable-context-bar :timetable="$timetable" active="logistics" />
    @endif

    <!-- 2. 検索・絞り込みフィルターバー -->
    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 shadow-2xs space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <!-- 区分フィルター -->
            <div class="flex items-center space-x-1.5 overflow-x-auto scrollbar-none flex-nowrap py-0.5">
                <span class="text-xs font-bold text-slate-500 mr-1 whitespace-nowrap shrink-0">区分:</span>
                <a href="{{ $indexUrl . '?' . http_build_query(array_filter(['keyword' => $keyword])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ !$typeFilter ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    すべて ({{ $totalCount }})
                </a>
                <a href="{{ $indexUrl . '?' . http_build_query(array_filter(['type' => 'committee', 'keyword' => $keyword])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'committee' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                    🏛️ 実行委員会 ({{ $committeeCount }})
                </a>
                <a href="{{ $indexUrl . '?' . http_build_query(array_filter(['type' => 'rental', 'keyword' => $keyword])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'rental' ? 'bg-sky-600 text-white shadow-2xs' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200' }}">
                    🏢 イベント会社 ({{ $rentalCount }})
                </a>
                <a href="{{ $indexUrl . '?' . http_build_query(array_filter(['type' => 'personal', 'keyword' => $keyword])) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'personal' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                    🎒 私物 ({{ $personalCount }})
                </a>
            </div>

            <!-- キーワード検索フォーム -->
            <form method="GET" action="{{ $indexUrl }}" class="flex items-center space-x-2 flex-wrap gap-2">
                @if($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif

                <!-- キーワード入力 -->
                <div class="relative">
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="品名・保管場所・備考検索..."
                           class="pl-8 pr-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48 sm:w-60">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    検索
                </button>

                @if($typeFilter || $keyword)
                    <a href="{{ $indexUrl }}" class="px-2.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-600 rounded-lg text-xs font-medium transition">
                        クリア
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- 3. 備品一覧テーブル（ドラッグ＆ドロップ対応） -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">備品名</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">区分</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">持ち出しルール</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">総数量</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap">定常保管場所 / 調達</th>
                        <th scope="col" class="px-4 py-2.5 whitespace-nowrap text-right">操作</th>
                    </tr>
                </thead>
                <tbody id="equipments-container"
                    data-reorder-url="{{ $reorderUrl }}"
                    class="divide-y divide-slate-100 bg-white">
                    @forelse($equipments as $item)
                        <tr data-equipment-id="{{ $item->id }}" draggable="true"
                            class="hover:bg-slate-50/80 transition cursor-default">
                            <!-- 備品名 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-indigo-600 px-1 select-none font-bold text-sm" title="ドラッグして並び替え">⋮⋮</span>
                                    <a href="{{ $item->showUrl($scopedTimetable ?? null) }}"
                                        class="font-bold text-slate-800 hover:text-indigo-600 hover:underline transition"
                                        title="クリックして詳細を表示">
                                        {{ $item->name }}
                                    </a>
                                </div>
                            </td>

                            <!-- 区分 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $item->type_enum->badgeClasses() }}">
                                    {{ $item->type_enum->icon() }} {{ $item->type_enum->shortLabel() }}
                                </span>
                            </td>

                            <!-- 持ち出しルール -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $item->checkout_rule_enum->badgeClasses() }}">
                                    @if($item->checkout_rule_enum->icon())
                                        <span class="mr-1">{{ $item->checkout_rule_enum->icon() }}</span>
                                    @endif
                                    <span>{{ $item->checkout_rule_enum->shortLabel() }}</span>
                                </span>
                            </td>

                            <!-- 数量・単位 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold font-mono text-slate-900 text-sm">{{ $item->formatted_quantity }}</span>
                            </td>

                            <!-- 保管場所 / 調達区分 -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($item->isRental())
                                    <span class="text-slate-700 font-medium">
                                        🏢 イベント会社手配
                                    </span>
                                @else
                                    <div class="font-medium text-slate-800 flex items-center space-x-1">
                                        <span class="text-slate-400">📍</span>
                                        <span>{{ $item->storage_location ?: ($item->isPersonal() ? '自宅（個人保管）' : '番所地下倉庫') }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- 操作 -->
                            <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <x-ui.button variant="edit" size="xs" :href="$item->editUrl($scopedTimetable ?? null)">
                                        <span>✏️</span>
                                        <span>編集</span>
                                    </x-ui.button>
                                    @can('delete', $item)
                                        <form method="POST" action="{{ $item->destroyUrl($scopedTimetable ?? null) }}" class="inline"
                                            data-confirm="備品「{{ $item->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
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
                            <td colspan="6">
                                <x-ui.empty-state icon="📦" title="備品がまだ登録されていません。" description="右上の「新規備品を追加」から登録してください。" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($equipments->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $equipments->links() }}
            </div>
        @endif
    </div>

</div>

@vite(['resources/js/equipments.js'])
</x-layouts.app>
