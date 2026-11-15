<x-layouts.app :title="$timetable->name . ' 現場配置計画'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        :title="'現場配置計画: ' . $map->name"
        icon="🗺️"
        :description="'「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）におけるメイン通り沿いのござ市コマ割り（A列・B列）や特設エリア、現場設備の配置状況を管理します。'">
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
            <span class="text-slate-700 font-bold truncate">🗺️ 現場配置計画</span>
        </x-slot:breadcrumbs>

        <x-slot:badges>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ $map->version_tag }}
            </span>
        </x-slot:badges>

        <x-slot:actions>
            <a href="{{ route('timetables.layouts.booths', $timetable) }}"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>📋</span>
                <span>コマ割り一覧・編集</span>
            </a>
            <a href="{{ route('timetables.layouts.facilities', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>🚰</span>
                <span>インフラ設備一覧</span>
            </a>
            <a href="{{ route('timetables.vendors.index', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>🏪</span>
                <span>出店者マスター管理</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- 運行日コンテキストバー -->
    <x-timetable-context-bar :timetable="$timetable" active="layouts" />

    <!-- フラッシュメッセージ -->
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-xl flex items-center space-x-2">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- 主要サマリー指標 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <!-- 1. ござ市・出店コマ数 -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">ござ市・出店コマ割</span>
                    <span class="text-base">🏮</span>
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-slate-900 font-mono">{{ $summary['assigned_booths'] }}</span>
                    <span class="text-xs text-slate-500 font-mono">/ {{ $summary['total_booths'] }} コマ 割当</span>
                </div>
                <div class="mt-2 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    @php
                        $rate = $summary['total_booths'] > 0 ? round(($summary['assigned_booths'] / $summary['total_booths']) * 100) : 0;
                    @endphp
                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $rate }}%;"></div>
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span>未割当: <strong class="text-slate-800 font-mono">{{ $summary['unassigned_booths'] }}</strong> コマ</span>
                <a href="{{ route('timetables.layouts.booths', $timetable) }}" class="text-indigo-600 hover:underline font-bold">
                    詳細一覧 →
                </a>
            </div>
        </div>

        <!-- 2. 出店区分サマリー -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">出店区分 (全{{ $summary['total_booths'] }}コマ)</span>
                <span class="text-base">🏷️</span>
            </div>
            <div class="grid grid-cols-2 gap-1.5 text-xs">
                @foreach($summary['categories'] as $cat)
                    <div class="flex items-center justify-between px-2 py-1 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[11px] text-slate-600 truncate">{{ $cat['label'] }}</span>
                        <span class="font-bold font-mono ml-1 text-slate-900">{{ $cat['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 3. 現場設備インフラ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">現場設備インフラ (全{{ $map->facilities->count() }}箇所)</span>
                    <span class="text-base">🚰</span>
                </div>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    @foreach($summary['facilities'] as $fac)
                        @if($fac['count'] > 0)
                        <div class="flex items-center justify-between px-2 py-1 bg-slate-50 rounded-lg border border-slate-100">
                            <span class="text-[11px] text-slate-600 flex items-center space-x-1 truncate">
                                <span>{{ $fac['icon'] }}</span>
                                <span class="truncate">{{ $fac['label'] }}</span>
                            </span>
                            <span class="font-bold font-mono ml-1 text-slate-900">{{ $fac['count'] }}</span>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span>給水設備（手洗い）: <strong class="text-slate-800 font-mono">{{ $summary['facilities']['water_supply']['count'] ?? 0 }}</strong> ヵ所</span>
                <a href="{{ route('timetables.layouts.facilities', $timetable) }}" class="text-indigo-600 hover:underline font-bold">
                    インフラ設備一覧 →
                </a>
            </div>
        </div>
    </div>

    <!-- メイン通り配置マップビュー -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        <div class="p-3.5 sm:p-4 border-b border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-1.5">
                    <span>🛣️</span>
                    <span>メイン通り配置マップビュー（南北配置）</span>
                </h2>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    商店街メイン通りの西側歩道（B列）と東側歩道（A列）の出店コマ並び、および給水設備等の配置位置です。
                </p>
            </div>
            <!-- 凡例 -->
            <div class="flex flex-wrap items-center gap-1.5 text-[10px]">
                <span class="px-1.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-300 font-bold rounded">🔴 調理(赤枠)</span>
                <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-300 font-bold rounded">🟢 非調理(緑枠)</span>
                <span class="px-1.5 py-0.5 bg-sky-50 text-sky-700 border border-sky-300 rounded">🛍️ 物販</span>
                <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-300 rounded">🎪 特設</span>
                <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-300 rounded">🚰 給水設備</span>
                <span class="px-1.5 py-0.5 bg-rose-50 text-rose-800 border border-rose-300 rounded">🏥 救護所</span>
                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 border border-slate-300 rounded">🏛️ 本陣</span>
            </div>
        </div>

        <div class="p-3 sm:p-4 max-h-[600px] overflow-y-auto space-y-2">
            <div class="text-center py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600 flex items-center justify-center space-x-1">
                <span>▲ 北側（保土ヶ谷駅西口・国道1号方面）</span>
            </div>

            <!-- 通り沿いのコマ並び -->
            <div class="space-y-1.5">
                @foreach($streetMapViewData['street_pairs'] as $pair)
                    <!-- 近接設備がある場合は設備帯を挿入 -->
                    @if(!empty($pair['facilities_near']))
                        <div class="my-2 p-2 bg-sky-50/80 border border-sky-200 rounded-xl flex flex-wrap items-center justify-center gap-2 text-xs font-bold text-sky-900 shadow-2xs">
                            @foreach($pair['facilities_near'] as $fac)
                                <span class="inline-flex items-center space-x-1 px-2 py-0.5 bg-white border border-sky-300 rounded-lg shadow-2xs">
                                    <span>{{ $fac->facility_type->icon() }}</span>
                                    <span>【{{ $fac->name }}】</span>
                                    <span class="text-[10px] text-sky-700 font-normal">({{ $fac->location_description }})</span>
                                    <span class="px-1 rounded text-[9px] {{ $fac->statusBadgeClass }}">
                                        {{ $fac->status }}
                                    </span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <!-- 西側歩道: B列 -->
                        <div class="min-w-0">
                            @if($pair['b'])
                                @php $b = $pair['b']; @endphp
                                <div class="p-2 rounded-xl {{ $b->category->borderClasses() }} transition hover:shadow-xs flex items-center justify-between text-xs gap-1.5">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-mono font-bold text-slate-800 text-[11px] px-1 bg-white/80 rounded border border-slate-200">
                                                {{ $b->booth_code }}
                                            </span>
                                            <span class="font-bold truncate text-slate-900">
                                                {{ $b->displayName }}
                                            </span>
                                        </div>
                                        @if($b->adjacent_landmark)
                                            <div class="text-[10px] text-slate-500 truncate mt-0.5">
                                                📍 {{ $b->adjacent_landmark }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] shrink-0 {{ $b->category->badgeClasses() }}">
                                        {{ $b->category->label() }}
                                    </span>
                                </div>
                            @else
                                <div class="h-full border border-dashed border-slate-200 rounded-xl p-2 flex items-center justify-center text-[10px] text-slate-400 bg-slate-50/40">
                                    （通り区間）
                                </div>
                            @endif
                        </div>

                        <!-- 東側歩道: A列 -->
                        <div class="min-w-0">
                            @if($pair['a'])
                                @php $a = $pair['a']; @endphp
                                <div class="p-2 rounded-xl {{ $a->category->borderClasses() }} transition hover:shadow-xs flex items-center justify-between text-xs gap-1.5">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-mono font-bold text-slate-800 text-[11px] px-1 bg-white/80 rounded border border-slate-200">
                                                {{ $a->booth_code }}
                                            </span>
                                            <span class="font-bold truncate text-slate-900">
                                                {{ $a->displayName }}
                                            </span>
                                        </div>
                                        @if($a->adjacent_landmark)
                                            <div class="text-[10px] text-slate-500 truncate mt-0.5">
                                                📍 {{ $a->adjacent_landmark }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] shrink-0 {{ $a->category->badgeClasses() }}">
                                        {{ $a->category->label() }}
                                    </span>
                                </div>
                            @else
                                <div class="h-full border border-dashed border-slate-200 rounded-xl p-2 flex items-center justify-center text-[10px] text-slate-400 bg-slate-50/40">
                                    （通り区間）
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600 flex items-center justify-center space-x-1">
                <span>▼ 南側（保土ヶ谷宿本陣跡・環状1号方面）</span>
            </div>
        </div>
    </div>

    <!-- 特設エリア・駐車場利用計画 -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs p-4">
        <h2 class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-1.5 mb-3">
            <span>🅿️</span>
            <span>特設エリア・駐車場利用計画</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($streetMapViewData['parking_areas'] as $pa)
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-slate-900">{{ $pa['area']->name }}</span>
                            <span class="px-1.5 py-0.5 bg-slate-200 text-slate-700 rounded text-[9px] font-mono">
                                {{ $pa['area']->area_type }}
                            </span>
                        </div>
                        <!-- 出店ブース -->
                        @if($pa['booths']->isNotEmpty())
                            <div class="mt-2 space-y-1">
                                <div class="text-[10px] font-bold text-slate-500">出店・企画ブース ({{ $pa['booths']->count() }})</div>
                                <div class="space-y-1">
                                    @foreach($pa['booths'] as $pb)
                                        <div class="p-1.5 bg-white border border-slate-200 rounded-lg text-[11px] flex items-center justify-between">
                                            <span class="truncate font-medium text-slate-800">
                                                <strong class="font-mono text-slate-600 mr-1">{{ $pb->booth_code }}</strong>
                                                {{ $pb->displayName }}
                                            </span>
                                            <span class="px-1 py-0.2 rounded text-[9px] shrink-0 {{ $pb->category->badgeClasses() }}">
                                                {{ $pb->category->label() }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 設備 -->
                        @if($pa['facilities']->isNotEmpty())
                            <div class="mt-2 space-y-1">
                                <div class="text-[10px] font-bold text-slate-500">配備設備 ({{ $pa['facilities']->count() }})</div>
                                <div class="space-y-1">
                                    @foreach($pa['facilities'] as $pf)
                                        <div class="p-1.5 bg-white border border-slate-200 rounded-lg text-[11px] flex items-center justify-between">
                                            <span class="truncate font-medium text-slate-800 flex items-center space-x-1">
                                                <span>{{ $pf->facility_type->icon() }}</span>
                                                <span>{{ $pf->name }}</span>
                                            </span>
                                            <span class="px-1 rounded text-[9px] {{ $pf->statusBadgeClass }}">
                                                {{ $pf->status }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
</x-layouts.app>
