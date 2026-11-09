<x-layouts.app :title="$timetable->name . ' 資機材管理'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- 1. ナビゲーション & ヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 資機材・資材管理'"
        icon="📦"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）運行に使用する自前保有備品・提携レンタル機材・個人持ち込み私物の台帳および配備状況を管理します。'">
        <x-slot:breadcrumbs>
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
            <span class="text-slate-700 font-bold truncate">📦 資機材・資材管理</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.logistics.print', $timetable) }}"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition whitespace-nowrap shrink-0"
                title="搬出入指示書兼現場チェックリストをPDFでダウンロード">
                <span>📄</span>
                <span>指示書PDF</span>
                <span class="text-[10px] opacity-60">↓</span>
            </a>
            <a href="{{ route('timetables.logistics.index', $timetable) }}"
                class="inline-flex items-center space-x-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold transition whitespace-nowrap shrink-0">
                <span>🚚</span>
                <span>搬出入計画</span>
            </a>
            <a href="{{ route('timetables.equipments.create', $timetable) }}"
                class="inline-flex items-center space-x-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition whitespace-nowrap shrink-0">
                <span>➕</span>
                <span>新規資機材を追加</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="equipments" />

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
                <a href="{{ route('timetables.equipments.index', array_merge(['timetable' => $timetable], array_filter(['lane_id' => $laneFilter, 'keyword' => $keyword]))) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ !$typeFilter ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    すべて ({{ $totalCount }})
                </a>
                <a href="{{ route('timetables.equipments.index', array_merge(['timetable' => $timetable], array_filter(['type' => 'committee', 'lane_id' => $laneFilter, 'keyword' => $keyword]))) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'committee' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200' }}">
                    🏛️ 実行委員会 ({{ $committeeCount }})
                </a>
                <a href="{{ route('timetables.equipments.index', array_merge(['timetable' => $timetable], array_filter(['type' => 'rental', 'lane_id' => $laneFilter, 'keyword' => $keyword]))) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'rental' ? 'bg-sky-600 text-white shadow-2xs' : 'bg-sky-50 text-sky-800 hover:bg-sky-100 border border-sky-200' }}">
                    🏢 イベント会社 ({{ $rentalCount }})
                </a>
                <a href="{{ route('timetables.equipments.index', array_merge(['timetable' => $timetable], array_filter(['type' => 'personal', 'lane_id' => $laneFilter, 'keyword' => $keyword]))) }}"
                   class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap shrink-0 transition {{ $typeFilter === 'personal' ? 'bg-amber-600 text-white shadow-2xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200' }}">
                    🎒 私物 ({{ $personalCount }})
                </a>
            </div>

            <!-- 所管班 & キーワード検索フォーム -->
            <form method="GET" action="{{ route('timetables.equipments.index', $timetable) }}" class="flex items-center space-x-2 flex-wrap gap-2">
                @if($typeFilter)
                    <input type="hidden" name="type" value="{{ $typeFilter }}">
                @endif

                <!-- 所管班 -->
                <select name="lane_id" data-auto-submit
                        class="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 pr-8">
                    <option value="">全班表示</option>
                    @foreach($lanes as $lane)
                        <option value="{{ $lane->id }}" {{ $laneFilter == $lane->id ? 'selected' : '' }}>
                            {{ $lane->icon ?: '🏷️' }} {{ $lane->name }}
                        </option>
                    @endforeach
                </select>

                <!-- キーワード入力 -->
                <div class="relative">
                    <input type="text" name="keyword" value="{{ $keyword }}" placeholder="品名・保管場所・備考検索..."
                           class="pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48 sm:w-60">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-bold transition cursor-pointer">
                    検索
                </button>

                @if($typeFilter || $laneFilter || $keyword)
                    <a href="{{ route('timetables.equipments.index', $timetable) }}" class="text-xs font-bold text-slate-500 hover:text-slate-800 underline">
                        クリア
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- 3. 資機材台帳一覧テーブル (班担当管理風カード) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-base text-slate-900 flex items-center space-x-2">
                    <span>📋</span>
                    <span>資機材台帳一覧</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono font-normal">
                        全 {{ $equipments->total() }} 品目
                    </span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    「{{ $timetable->name }}」における配備ステップおよび定常保管場所の一覧です。
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th scope="col" class="py-3.5 px-4 whitespace-nowrap">資機材名 / 仕様メモ</th>
                        <th scope="col" class="py-3.5 px-3 whitespace-nowrap">区分</th>
                        <th scope="col" class="py-3.5 px-3 whitespace-nowrap">総数量</th>
                        <th scope="col" class="py-3.5 px-4 whitespace-nowrap">定常保管場所 / 調達</th>
                        <th scope="col" class="py-3.5 px-3 whitespace-nowrap">主担当班</th>
                        <th scope="col" class="py-3.5 px-3 whitespace-nowrap">本日配備動態</th>
                        <th scope="col" class="py-3.5 px-4 text-right whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($equipments as $item)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- 資機材名 -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-1.5 flex-wrap">
                                    <a href="{{ route('timetables.equipments.show', [$timetable, $item]) }}" class="font-bold text-slate-900 text-sm hover:text-indigo-600 transition">
                                        {{ $item->name }}
                                    </a>
                                    @if($item->checkout_rule === 'permission_required')
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">⚠️ 本陣許可制</span>
                                    @elseif($item->checkout_rule === 'leader_only')
                                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">👑 班長限定</span>
                                    @endif
                                </div>
                                @if($item->description)
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                                        {{ $item->description }}
                                    </div>
                                @endif
                            </td>

                            <!-- 区分 -->
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $item->type_enum->badgeClasses() }}">
                                    {{ $item->type_enum->icon() }} {{ $item->type_enum->shortLabel() }}
                                </span>
                            </td>

                            <!-- 数量・単位 -->
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                <span class="font-bold font-mono text-slate-900 text-sm">{{ $item->formatted_quantity }}</span>
                            </td>

                            <!-- 保管場所 / 調達区分 -->
                            <td class="py-3.5 px-4">
                                @if($item->isRental())
                                    <div class="text-slate-700 font-medium">
                                        🏢 イベント会社手配
                                    </div>
                                @else
                                    <div class="font-medium text-slate-800 flex items-center space-x-1">
                                        <span class="text-slate-400">📍</span>
                                        <span>{{ $item->storage_location ?: ($item->isPersonal() ? '自宅（個人保管）' : '番所地下倉庫') }}</span>
                                    </div>
                                    @if($item->storage_detail)
                                        <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">
                                            {{ $item->storage_detail }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- 所管班 -->
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                @if($item->lane)
                                    @php $laneClasses = $item->lane->color_classes; @endphp
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[11px] font-bold border {{ $laneClasses['badge'] ?? 'bg-slate-100 text-slate-800 border-slate-200' }}">
                                        <span>{{ $item->lane->icon ?: '🏷️' }}</span>
                                        <span>{{ $item->lane->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">未設定</span>
                                @endif
                            </td>

                            <!-- 搬出入計画配備状況 (本日スコープ) -->
                            <td class="py-3.5 px-3 whitespace-nowrap">
                                @php $planCount = $item->plans->count(); @endphp
                                @if($planCount > 0)
                                    <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $item]) }}"
                                       class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition"
                                       title="この資機材の搬出入ステップを確認・設定">
                                        <span>🚚</span>
                                        <span>{{ $planCount }} ステップ</span>
                                    </a>
                                @else
                                    <a href="{{ route('timetables.logistics.equipments.show', [$timetable, $item]) }}"
                                       class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition"
                                       title="搬出入ステップを追加">
                                        未設定
                                    </a>
                                @endif
                            </td>

                            <!-- 操作列 (班担当管理統一スタイル) -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                <a href="{{ route('timetables.equipments.show', [$timetable, $item]) }}"
                                   class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition inline-flex items-center"
                                   title="詳細表示">
                                    詳細
                                </a>
                                <a href="{{ route('timetables.equipments.edit', [$timetable, $item]) }}"
                                   class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition inline-flex items-center"
                                   title="編集">
                                    編集
                                </a>
                                @can('delete', $item)
                                    <form method="POST" action="{{ route('timetables.equipments.destroy', [$timetable, $item]) }}" class="inline"
                                          data-confirm="資機材「{{ $item->name }}」を削除しますか？&#10;紐づくすべての搬出入計画ステップも削除されます。">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition inline-flex items-center space-x-0.5 cursor-pointer"
                                                title="削除">
                                            <span>🗑️</span>
                                            <span>削除</span>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="font-bold text-slate-700">登録されている資材がありません</div>
                                <div class="text-xs text-slate-400 mt-1">「新規資機材を追加」ボタンから自前備品や提携レンタル機材、私物を登録してください。</div>
                                <div class="mt-4">
                                    <a href="{{ route('timetables.equipments.create', $timetable) }}" class="inline-block px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 transition">
                                        ＋ 新規資機材を追加
                                    </a>
                                </div>
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
</x-layouts.app>
