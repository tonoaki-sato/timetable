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
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                    <span>🏮</span>
                    <span>本部</span>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-500">🗂️ マスター管理</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-bold truncate">📦 機材・備品一覧</span>
            @endif
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if($isTimetableScoped)
                <a href="{{ route('timetables.logistics.print', $timetable) }}"
                    class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition whitespace-nowrap shrink-0"
                    title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                    <span>📄</span>
                    <span>指示書PDF</span>
                </a>
            @endif
            <a href="{{ $createUrl }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規備品を追加</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- タイムテーブルスコープ時のみコンテキストバーを表示 -->
    @if($isTimetableScoped)
        <x-timetable-context-bar :timetable="$timetable" active="logistics" />
    @endif

    <!-- アラートメッセージ -->
    @if (session('status'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- 2. 検索・絞り込みフィルターバー -->
    <div class="bg-white p-3 sm:p-3.5 rounded-xl border border-slate-200 shadow-2xs space-y-3">
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
                           class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48 sm:w-60">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    検索
                </button>

                @if($typeFilter || $keyword)
                    <a href="{{ $indexUrl }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 underline">
                        クリア
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- 3. 備品一覧テーブル（ドラッグ＆ドロップ対応） -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">備品名</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">区分</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">持ち出しルール</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">総数量</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap">定常保管場所 / 調達</th>
                        <th scope="col" class="px-4 py-3 whitespace-nowrap text-right">操作</th>
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
                            <td class="px-4 py-3 whitespace-nowrap text-right space-x-1">
                                <a href="{{ $item->editUrl($scopedTimetable ?? null) }}"
                                    class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1">
                                    <span>✏️</span>
                                    <span>編集</span>
                                </a>
                                @can('delete', $item)
                                    <form method="POST" action="{{ $item->destroyUrl($scopedTimetable ?? null) }}" class="inline"
                                        data-confirm="備品「{{ $item->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-2.5 py-1 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 hover:border-rose-200 rounded-lg text-xs font-bold transition cursor-pointer inline-flex items-center space-x-1">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 text-xs">
                                備品がまだ登録されていません。ヘッダーの「➕ 新規備品を追加」から登録してください。
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
