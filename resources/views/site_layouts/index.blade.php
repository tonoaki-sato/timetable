<x-layouts.app :title="$timetable->name . ' 配置計画'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 space-y-3.5">

    <!-- ページヘッダー -->
    <x-page-header
        :title="$timetable->name . ' 配置計画'"
        icon="🗺️"
        :description="'ござ市、インフラ設備などの配置情報を閲覧できます'">
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
            <span class="text-slate-700 font-bold truncate">🗺️ 配置計画</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <a href="{{ route('timetables.layouts.booths', $timetable) }}"
                class="px-3 py-1.5 bg-white border border-slate-300 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-slate-700 rounded-lg text-xs font-bold shadow-2xs transition flex items-center space-x-1 whitespace-nowrap shrink-0">
                <span>📋</span>
                <span>配置一覧</span>
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

    <!-- 1段目: 区分別内訳（出展区分・設備区分 2列構成） -->
    <div class="space-y-2">
        <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-700">
            <span>📊</span>
            <span>区分別内訳</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- 1. 出展区分サマリー -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs">
                <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-1">
                        <span>🎪</span>
                        <span>出展区分 (全{{ $summary['total_booths'] }}件)</span>
                    </span>
                    <a href="{{ route('timetables.layouts.booths', [$timetable, 'item_type' => 'booth']) }}"
                        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center space-x-0.5 transition shrink-0">
                        <span>配置一覧</span>
                        <span>→</span>
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    @foreach($summary['categories'] as $key => $cat)
                        <a href="{{ route('timetables.layouts.booths', [$timetable, 'item_type' => 'booth', 'category' => $cat['key'] ?? $key]) }}"
                            class="flex items-center justify-between px-2 py-1 bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-200 rounded-lg transition group">
                            <span class="text-[11px] text-slate-600 group-hover:text-indigo-700 truncate flex items-center space-x-1 font-medium">
                                <span>{{ $cat['icon'] }}</span>
                                <span class="truncate">{{ $cat['label'] }}</span>
                            </span>
                            <span class="font-bold font-mono ml-1 text-slate-900 group-hover:text-indigo-700">{{ $cat['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- 2. 設備区分サマリー -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-2xs">
                <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center space-x-1">
                        <span>🚰</span>
                        <span>設備区分 (全{{ $summary['total_facilities'] }}箇所)</span>
                    </span>
                    <a href="{{ route('timetables.layouts.booths', [$timetable, 'item_type' => 'facility']) }}"
                        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center space-x-0.5 transition shrink-0">
                        <span>配置一覧</span>
                        <span>→</span>
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    @foreach($summary['facilities'] as $key => $fac)
                        <a href="{{ route('timetables.layouts.booths', [$timetable, 'item_type' => 'facility', 'category' => $fac['key'] ?? $key]) }}"
                            class="flex items-center justify-between px-2 py-1 bg-slate-50 hover:bg-cyan-50 border border-slate-100 hover:border-cyan-200 rounded-lg transition group">
                            <span class="text-[11px] text-slate-600 group-hover:text-cyan-800 flex items-center space-x-1 truncate font-medium">
                                <span>{{ $fac['icon'] }}</span>
                                <span class="truncate">{{ $fac['label'] }}</span>
                            </span>
                            <span class="font-bold font-mono ml-1 text-slate-900 group-hover:text-cyan-800">{{ $fac['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 2段目: エリア別 ブース形態内訳 -->
    <div class="space-y-2">
        <div class="flex items-center space-x-1.5 text-xs font-bold text-slate-700">
            <span>⛺</span>
            <span>エリア別 ブース形態内訳</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($summary['area_booth_types'] as $abt)
                <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-900 truncate">{{ $abt['area_name'] }}</span>
                        <div class="flex items-center space-x-2 shrink-0">
                            <span class="font-mono text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                全{{ $abt['total_count'] }}件
                            </span>
                            <a href="{{ route('timetables.layouts.booths', [$timetable, 'area_id' => $abt['area_id']]) }}"
                                class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center space-x-0.5 transition">
                                <span>配置一覧</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                    <div class="mt-2 space-y-1">
                        @forelse($abt['types'] as $type)
                            <div class="flex items-center justify-between text-xs px-2 py-1 bg-slate-50 rounded-lg">
                                <span class="text-slate-600 font-medium">{{ $type['label'] }}</span>
                                <span class="font-mono font-bold text-slate-900">{{ $type['count'] }}</span>
                            </div>
                        @empty
                            <div class="text-[11px] text-slate-400 py-1 text-center">
                                （配置アイテムなし）
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- エリア別配置一覧（全エリア展開） -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs p-4">
        <h2 class="text-sm sm:text-base font-bold text-slate-900 flex items-center space-x-1.5 mb-3">
            <span>🗺️</span>
            <span>エリア別配置一覧 (全{{ $map->areas->count() }}エリア)</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($map->areas as $area)
                @php
                    $areaBooths = $area->items->filter(fn ($item) => $item->isBooth());
                    $areaFacilities = $area->items->filter(fn ($item) => $item->isFacility());
                @endphp
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2 flex flex-col justify-between">
                    <div>
                        <!-- エリアヘッダー -->
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                            <span class="font-bold text-xs text-slate-900 truncate">{{ $area->name }}</span>
                            <div class="flex items-center space-x-1 shrink-0">
                                <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-[9px] font-bold">
                                    🎪 {{ $areaBooths->count() }}件
                                </span>
                                <span class="px-1.5 py-0.5 bg-sky-50 text-sky-700 border border-sky-200 rounded text-[9px] font-bold">
                                    🚰 {{ $areaFacilities->count() }}件
                                </span>
                            </div>
                        </div>

                        <!-- アイテム一覧コンテナ -->
                        <div class="mt-2 space-y-2 max-h-72 overflow-y-auto pr-1">
                            <!-- 出展ブース -->
                            @if($areaBooths->isNotEmpty())
                                <div class="space-y-1">
                                    <div class="text-[10px] font-bold text-slate-500 flex items-center space-x-1">
                                        <span>🎪</span>
                                        <span>出展ブース ({{ $areaBooths->count() }})</span>
                                    </div>
                                    <div class="space-y-1">
                                        @foreach($areaBooths as $pb)
                                            <div class="p-1.5 bg-white border border-slate-200 rounded-lg text-[11px] flex items-center justify-between gap-1.5">
                                                <span class="truncate font-medium text-slate-800 flex items-center space-x-1 min-w-0">
                                                    <strong class="font-mono text-slate-600 shrink-0">{{ $pb->code }}</strong>
                                                    <span class="truncate">{{ $pb->displayName }}</span>
                                                </span>
                                                <div class="flex items-center space-x-1 shrink-0 text-[9px]">
                                                    <span class="px-1 py-0.2 rounded {{ $pb->boothCategory()?->badgeClasses() ?? 'bg-slate-100 text-slate-700' }}">
                                                        {{ $pb->boothCategory()?->label() ?? '出店' }}
                                                    </span>
                                                    @if($pb->boothType())
                                                        <span class="px-1 py-0.2 bg-slate-100 text-slate-600 rounded">
                                                            {{ $pb->boothType()->label() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- 現場設備 -->
                            @if($areaFacilities->isNotEmpty())
                                <div class="space-y-1">
                                    <div class="text-[10px] font-bold text-slate-500 flex items-center space-x-1">
                                        <span>🚰</span>
                                        <span>現場設備 ({{ $areaFacilities->count() }})</span>
                                    </div>
                                    <div class="space-y-1">
                                        @foreach($areaFacilities as $pf)
                                            <div class="p-1.5 bg-white border border-slate-200 rounded-lg text-[11px] flex items-center justify-between gap-1.5">
                                                <span class="truncate font-medium text-slate-800 flex items-center space-x-1 min-w-0">
                                                    <span class="shrink-0">{{ $pf->typeIcon() }}</span>
                                                    <strong class="font-mono text-slate-600 shrink-0">{{ $pf->code }}</strong>
                                                    <span class="truncate">{{ $pf->displayName }}</span>
                                                </span>
                                                <div class="flex items-center space-x-1 shrink-0 text-[9px]">
                                                    <span class="px-1 py-0.2 rounded {{ $pf->statusBadgeClass }}">
                                                        {{ $pf->status->label() }}
                                                    </span>
                                                    @if($pf->boothType())
                                                        <span class="px-1 py-0.2 bg-slate-100 text-slate-600 rounded">
                                                            {{ $pf->boothType()->label() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- アイテムが0件の場合 -->
                            @if($areaBooths->isEmpty() && $areaFacilities->isEmpty())
                                <div class="py-4 text-center text-xs text-slate-400">
                                    （配置アイテムは登録されていません）
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
</x-layouts.app>