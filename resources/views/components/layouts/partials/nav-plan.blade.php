<!-- 2. 📋 準備・計画 ▾ -->
<div class="relative nav-dropdown shrink-0">
    <button type="button"
        class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isPlanActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
        <span>📋</span>
        <span>準備・計画</span>
        <span class="text-[9px] opacity-75">▼</span>
    </button>
    <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-[620px] lg:w-[650px] max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-2 z-50">
        <!-- 運行準備ナビ (上部全幅) -->
        <a href="{{ route('preparation-navigator.index') }}"
            class="flex items-center space-x-2.5 p-2 rounded-xl transition mb-2 {{ request()->routeIs('preparation-navigator.*') ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-950/70 border border-indigo-700/50 text-slate-200 hover:bg-indigo-900/80 hover:text-white' }}">
            <span class="text-lg shrink-0">🚀</span>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold flex items-center justify-between gap-1">
                    <span class="whitespace-nowrap truncate">運行準備ナビ</span>
                    <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded bg-indigo-500/30 text-indigo-200 shrink-0">Step 1〜4 手順ガイド →</span>
                </div>
                <p class="text-[10px] text-indigo-200/80 truncate mt-0.5">日程・班・タスク・人員配置を順番に進めるステップナビゲーター</p>
            </div>
        </a>

        <!-- 2カラムレイアウト -->
        <div class="grid grid-cols-2 gap-2">
            <!-- 左カラム: 📋 運行・人員編成 (Step 1〜4) -->
            <div class="space-y-1">
                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                    <span>📋 運行・人員編成</span>
                    <span class="text-[9px] text-indigo-300 font-mono font-bold">Step 1〜4</span>
                </div>
                <div class="space-y-0.5">
                    <!-- 運行日程管理 (Step 1) -->
                    <a href="{{ route('timetables.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.index') || request()->routeIs('timetables.show') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">📅</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">運行日程管理</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">各日の運行日程・基本情報台帳</p>
                        </div>
                    </a>

                    <!-- 担当班編成 (Step 2) -->
                    @if($nav->hasTimetables())
                    <a href="{{ route('timetable-lanes.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.lanes.*') || request()->routeIs('timetable-lanes.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">🏷️</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">担当班編成</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">各日の班編成・現場リーダー指名</p>
                        </div>
                    </a>
                    @else
                    <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">🏷️</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">担当班編成</div>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">各日の班編成・現場リーダー指名</p>
                        </div>
                    </div>
                    @endif

                    <!-- 作業タスク台帳 (Step 3) -->
                    @if($nav->hasTimetables())
                    <a href="{{ route('timetable-tasks.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetable-tasks.*') || request()->routeIs('timetables.tasks.index') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">📝</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">作業タスク台帳</div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">全タスク登録・必要人数設定</p>
                        </div>
                    </a>
                    @else
                    <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">📝</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold whitespace-nowrap truncate">作業タスク台帳</div>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">全タスク登録・必要人数設定</p>
                        </div>
                    </div>
                    @endif

                    <!-- 人員事前配置 (Step 4) -->
                    @if($nav->hasTimetables())
                    <a href="{{ route('assignments.index') }}"
                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.assignments') || request()->routeIs('assignments.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                        <span class="text-base shrink-0">👥</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold flex items-center justify-between gap-1">
                                <span class="whitespace-nowrap truncate">人員事前配置</span>
                                <span class="text-[9px] font-mono font-bold px-1.5 rounded bg-indigo-500/20 text-indigo-300 shrink-0">事前計画</span>
                            </div>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5">タスク定員充足率100%に向けた計画割当</p>
                        </div>
                    </a>
                    @else
                    <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">👥</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold flex items-center justify-between gap-1">
                                <span class="whitespace-nowrap truncate">人員事前配置</span>
                                <span class="text-[9px] font-mono font-bold px-1.5 rounded bg-slate-800 text-slate-500 shrink-0">事前計画</span>
                            </div>
                            <p class="text-[10px] text-slate-500 truncate mt-0.5">タスク定員充足率100%に向けた計画割当</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 右カラム: 🗺️ 会場・物流 & 📅 全体日程 -->
            <div class="space-y-2">
                <!-- 会場・物流計画 -->
                <div class="space-y-1">
                    <div class="px-2.5 py-1 text-[10px] font-bold text-slate-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                        <span>🗺️ 会場・物流計画</span>
                        <span class="text-[9px] text-emerald-300 font-mono font-bold">現場</span>
                    </div>
                    <div class="space-y-0.5">
                        <!-- 搬出入計画 -->
                        @if($nav->hasTimetables())
                        <a href="{{ route('logistics.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ $nav->isLogisticsPlansActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🚚</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">搬出入計画</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">6大フェーズ運行・資材物流計画</p>
                            </div>
                        </a>
                        <!-- 配置計画 -->
                        <a href="{{ route('timetables.layouts.index', $nav->targetPrintTimetable() ?? 1) }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.layouts.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🗺️</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">配置計画</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">ござ市コマ割り・現場設備配置</p>
                            </div>
                        </a>
                        <!-- 白地図エディタ -->
                        <a href="{{ route('timetables.site-maps.editor', $nav->targetPrintTimetable() ?? 1) }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.site-maps.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">🎨</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">白地図エディタ</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">A3 1/400 白地図ブース配置</p>
                            </div>
                        </a>
                        @else
                        <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                            <span class="text-base shrink-0 grayscale opacity-60">🚚</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">搬出入計画</div>
                                <p class="text-[10px] text-slate-500 truncate mt-0.5">6大フェーズ運行・資材物流計画</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-50 cursor-not-allowed select-none" title="運行日程の登録が必要です">
                            <span class="text-base shrink-0 grayscale opacity-60">🗺️</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">配置計画</div>
                                <p class="text-[10px] text-slate-500 truncate mt-0.5">ござ市コマ割り・現場設備配置</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- 全体日程・会議 -->
                <div class="space-y-1">
                    <div class="px-2.5 py-1 text-[10px] font-bold text-slate-300 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                        <span>📅 全体日程・会議</span>
                        <span class="text-[9px] text-amber-300 font-mono font-bold">全体工程</span>
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('calendar.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('calendar.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">📅</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">総合カレンダー</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">月間グリッドで予定・会議を俯瞰</p>
                            </div>
                        </a>
                        <a href="{{ route('schedules.index') }}"
                            class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('schedules.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span class="text-base shrink-0">📋</span>
                            <div class="min-w-0 flex-1">
                                <div class="text-xs font-bold whitespace-nowrap truncate">スケジュール管理</div>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">会議・協議の時系列管理・LINE文生成</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
