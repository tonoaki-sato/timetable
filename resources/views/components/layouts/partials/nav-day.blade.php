<!-- 2. ⏱️ 当日運行 ▾ -->
<div class="relative nav-dropdown shrink-0">
    <button type="button"
        class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isDayActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
        <span>⏱️</span>
        <span>当日運行</span>
        <span class="text-[9px] opacity-75">▼</span>
    </button>
    <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-80 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
        <div class="px-2.5 py-1 text-[10px] font-bold text-indigo-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
            <span>⏱️ 進行表・当日運行</span>
            <span class="text-[9px] text-emerald-400 font-mono font-bold">当日</span>
        </div>
        <div class="py-1 space-y-0.5">
            <!-- 本部ダッシュボード -->
            <a href="{{ route('dashboard') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">📊</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center justify-between">
                        <span>本部ダッシュボード</span>
                        <span class="text-[9px] text-emerald-400 font-mono font-bold">司令塔</span>
                    </div>
                    <p class="text-[10px] {{ request()->routeIs('dashboard') ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">
                        Now/Nextタスク監視・交通規制・駐車場・要請
                    </p>
                </div>
            </a>

            <!-- 当日進行表 (各日) -->
            @forelse($navTimetables ?? [] as $tt)
            <a href="{{ route('timetable.index', ['timetable_id' => $tt->id]) }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ $nav->isCurrentTimetable($tt) ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">⏱️</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold flex items-center justify-between">
                        <span class="truncate">{{ $tt->target_date ? $tt->target_date->format('m/d') . ' ' : '' }}{{ $tt->name }}</span>
                        @if($tt->status === 'active')
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 shrink-0 ml-1">運行中</span>
                        @endif
                    </div>
                    <p class="text-[10px] {{ $nav->isCurrentTimetable($tt) ? 'text-indigo-100' : 'text-slate-400' }} truncate mt-0.5">
                        {{ substr($tt->start_time, 0, 5) }}〜{{ substr($tt->end_time, 0, 5) }} / 進行表
                    </p>
                </div>
            </a>
            @empty
            <div class="p-2 text-center text-xs text-slate-400">現在予定の運行日はありません</div>
            @endforelse

            <div class="border-t border-slate-800/80 my-1"></div>

            <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                <span>👥 タイムライン・動態調整</span>
                <span class="text-[9px] text-amber-300 font-mono font-bold">現場対応</span>
            </div>

            <!-- 班別タイムライン (担当班別ガントチャート) -->
            @if($nav->hasTimetables())
            <a href="{{ route('lane-timeline.index') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.lane-timeline') || request()->routeIs('lane-timeline.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">📊</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">班別タイムライン</div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">班別タスクガント（定常・スポット管理）</p>
                </div>
            </a>
            @else
            <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                <span class="text-base shrink-0 grayscale opacity-60">📊</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">班別タイムライン</div>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">班別タスクガント（定常・スポット管理）</p>
                </div>
            </div>
            @endif

            <!-- スタッフタイムライン (稼働タイムライン) -->
            @if($nav->targetPrintTimetable())
            <a href="{{ route('timetables.staff-timeline', $nav->targetPrintTimetable()) }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.staff-timeline') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">👥</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">スタッフタイムライン</div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">タイムライン・空き枠特定・動態調整</p>
                </div>
            </a>
            @endif

            <!-- 搬出入タイムライン -->
            @if($nav->hasTimetables())
            <a href="{{ route('logistics-timeline.index') }}"
                class="flex items-center space-x-2 p-2 rounded-xl transition {{ $nav->isLogisticsTimelineActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                <span class="text-base shrink-0">🚚</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">搬出入タイムライン</div>
                    <p class="text-[10px] text-slate-400 truncate mt-0.5">資材・機材の搬出入ガントチャート</p>
                </div>
            </a>
            @else
            <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                <span class="text-base shrink-0 grayscale opacity-60">🚚</span>
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold whitespace-nowrap truncate">搬出入タイムライン</div>
                    <p class="text-[10px] text-slate-500 truncate mt-0.5">資材・機材の搬出入ガントチャート</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
