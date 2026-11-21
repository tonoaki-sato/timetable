@props(['title' => 'まつり総合管理システム 2026'])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-50 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - まつり総合管理システム</title>
    <x-head-meta />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full flex flex-col font-sans antialiased bg-slate-100/70">

    <!-- 本部ナビゲーションヘッダー -->
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">

                <!-- 左側: システムロゴ & ナビゲーションメニュー -->
                <div class="flex items-center space-x-2 lg:space-x-3 min-w-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 group shrink-0">
                        <span class="text-xl transition group-hover:scale-110 shrink-0">🏮</span>
                        <div>
                            <div class="flex items-center space-x-1 whitespace-nowrap">
                                <span class="font-bold text-sm tracking-tight text-white group-hover:text-indigo-300 transition whitespace-nowrap">
                                    まつり総合管理システム
                                </span>
                            </div>
                        </div>
                    </a>

                    <!-- 主要リンク（5大別ドロップダウン: 当日運行・準備/計画・マスター管理・帳票/印刷・システム/ガイド） -->
                    <nav class="hidden md:flex items-center space-x-1 pl-2.5 border-l border-slate-800 shrink-0">
                        <!-- 1. ⏱️ 当日運行 ▾ -->
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
                                        <span>👥 スタッフタイムライン・動態調整</span>
                                        <span class="text-[9px] text-amber-300 font-mono font-bold">現場対応</span>
                                    </div>

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
                                            <a href="{{ route('lanes.index') }}"
                                                class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.lanes.*') || request()->routeIs('lanes.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
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
                                                    class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.layouts.*') || request()->routeIs('timetables.goza-stores.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
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

                        <!-- 3. 🗂️ マスター管理 ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isMasterActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>🗂️</span>
                                <span>マスター管理</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-76 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>👥 組織・人員マスター</span>
                                    <span class="text-[9px] text-indigo-300 font-mono font-bold">体制</span>
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('organizations.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('organizations.*') && !request()->routeIs('organizations.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🏛️</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">組織管理</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">実行委員会の部門・係・役職・担当者体制</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('staff.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('staff.*') && !request()->routeIs('staff.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👷</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">スタッフ名簿</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">ボランティア・幹事・個別QRコード</p>
                                        </div>
                                    </a>
                                </div>

                                <div class="border-t border-slate-800/80 my-1"></div>

                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>🎪 出展・出店者マスター</span>
                                    <span class="text-[9px] text-emerald-300 font-mono font-bold">出店</span>
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('vendors.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('vendors.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🏪</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">出展・出店者マスター</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">ござ市・企業ブース・特設企画の全出展者台帳</p>
                                        </div>
                                    </a>
                                </div>

                                <div class="border-t border-slate-800/80 my-1"></div>

                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>📦 資材・拠点マスター</span>
                                    <span class="text-[9px] text-amber-300 font-mono font-bold">リソース</span>
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('equipments.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('equipments.*') || request()->routeIs('timetables.equipments.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📦</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">機材・備品一覧</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">自前備品・レンタル機材・私物の一元台帳</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('locations.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('locations.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📍</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">拠点マスター</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">倉庫・駐車場・オープンスペース台帳</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('location-types.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('location-types.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🏷️</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">拠点タイプ管理</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">拠点種別・アイコン設定</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('equipment-patterns.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('equipment-patterns.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🧩</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">搬出入パターン</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">資材搬出入ステップ・テンプレート</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 4. 🖨️ 帳票・印刷 ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isPrintActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>🖨️</span>
                                <span>帳票・印刷</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-80 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>🌐 全体・マスター帳票 (区分A)</span>
                                    <span class="text-[9px] text-indigo-300 font-mono font-bold">年間・共通</span>
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <!-- 組織図PDF -->
                                    <a href="{{ route('organizations.print') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition text-slate-200 hover:bg-slate-800 hover:text-white"
                                        title="実行委員会組織体制のA4縦帳票PDFをダウンロード">
                                        <span class="text-base shrink-0">🏛️</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">組織図 (PDF)</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">実行委員会組織体制のA4縦帳票PDFダウンロード</p>
                                        </div>
                                    </a>

                                    <!-- スタッフカード一括印刷 (実装済み) -->
                                    <a href="{{ route('staff.print-cards') }}" target="_blank"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('staff.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🪪</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">名札カード一括印刷 (PDF)</span>
                                                <span class="text-[10px] text-slate-400">↗</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">全登録スタッフのA4面付け印刷</p>
                                        </div>
                                    </a>

                                    <!-- スタッフCSV出力 (実装済み) -->
                                    <a href="{{ route('staff.export-csv') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition text-slate-200 hover:bg-slate-800 hover:text-white">
                                        <span class="text-base shrink-0">📥</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">スタッフCSV出力</span>
                                                <span class="text-[10px] text-slate-400">↓</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">現場スタッフ台帳をCSVで保存・保管</p>
                                        </div>
                                    </a>

                                    <!-- 年間スケジュール表 (PDF) -->
                                    <a href="{{ route('schedules.pdf') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition group cursor-pointer"
                                        title="年間スケジュール一覧のA4縦帳票PDFをダウンロード">
                                        <span class="text-base shrink-0 group-hover:scale-110 transition-transform">📅</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">年間スケジュール表 (PDF)</span>
                                                <span class="text-[10px] text-slate-400">↓</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">年間工程・会議日程一覧表 (A4縦)</p>
                                        </div>
                                    </a>

                                    <!-- スタッフ一覧 (PDF) -->
                                    <a href="{{ route('staff.pdf') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition group cursor-pointer"
                                        title="全登録スタッフ一覧のA4縦・2段組帳票PDFをダウンロード">
                                        <span class="text-base shrink-0 group-hover:scale-110 transition-transform">👥</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">スタッフ名簿一覧 (PDF)</span>
                                                <span class="text-[10px] text-slate-400">↓</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">全スタッフ名簿一覧表 (A4縦・2段組)</p>
                                        </div>
                                    </a>

                                    <!-- レンタル機材一覧 (未実装: テキストのみ表示) -->
                                    <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-70 cursor-not-allowed">
                                        <span class="text-base shrink-0">📦</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center justify-between">
                                                <span class="whitespace-nowrap truncate">レンタル機材一覧 (PDF)</span>
                                                <span class="text-[9px] bg-slate-800 text-slate-400 px-1.5 py-0.2 rounded font-mono">準備中</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 truncate mt-0.5">発注レンタル機材の集計リスト</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-800/80 my-1"></div>

                                <!-- 区分Bへの案内フッター -->
                                <div class="px-2.5 py-2 text-[10px] text-slate-400 bg-slate-800/40 rounded-xl leading-relaxed">
                                    💡 運行日別（タイムテーブル・担当班・搬出入等）の現場帳票は、各「運行情報詳細」画面から出力できます。
                                </div>
                            </div>
                        </div>

                        <!-- 5. ⚙️ システム・ガイド ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isGuideActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>⚙️</span>
                                <span>システム・ガイド</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute right-0 sm:left-auto lg:left-0 mt-1.5 w-76 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>📖 ガイド・広報資料</span>
                                    <span class="text-[9px] text-indigo-300 font-mono font-bold">資料</span>
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('manual.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('manual.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📖</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">操作マニュアル</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">図解・画面付きシステム利用手順</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('presentation.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('presentation.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📽️</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">幹事会プレゼン</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">情報一元管理の協力要請スライド</p>
                                        </div>
                                    </a>
                                    @can('viewAny', App\Models\User::class)
                                    <a href="{{ route('users.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👥</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">ユーザー管理</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">本部アカウント・管理者権限設定</p>
                                        </div>
                                    </a>
                                    @endcan
                                    <a href="{{ route('staff.join') }}" target="_blank"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition text-slate-200 hover:bg-slate-800 hover:text-white">
                                        <span class="text-base shrink-0">🌐</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">セルフ登録フォーム</span>
                                                <span class="text-[10px] text-slate-400">↗</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">ボランティア公開用登録ページ</p>
                                        </div>
                                    </a>
                                </div>

                                <div class="border-t border-slate-800/80 my-1"></div>

                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>⚙️ システム設定</span>
                                    <span class="text-[9px] text-indigo-300 font-mono font-bold">設定</span>
                                </div>
                                <div class="py-1 space-y-1">
                                    <!-- Webセルフ登録機能 ON/OFF・受付管理カード -->
                                    @if(isset($selfRegistrationSetting) && isset($selfRegistrationStatus))
                                    <div class="p-2.5 bg-slate-800/90 border border-slate-700/70 rounded-xl space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-slate-200 flex items-center space-x-1.5">
                                                <span>🌐</span>
                                                <span>Webセルフ登録受付</span>
                                            </span>
                                            @if($selfRegistrationStatus['status'] === 'open')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center space-x-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                                <span>受付中</span>
                                            </span>
                                            @elseif($selfRegistrationStatus['status'] === 'upcoming')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                                受付前(予約)
                                            </span>
                                            @elseif($selfRegistrationStatus['status'] === 'closed')
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-600/30 text-slate-300 border border-slate-500/30">
                                                受付終了
                                            </span>
                                            @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-500/20 text-red-300 border border-red-400/30">
                                                停止中(手動)
                                            </span>
                                            @endif
                                        </div>

                                        <div class="flex items-center space-x-1.5 pt-1">
                                            @if($selfRegistrationStatus['status'] === 'closed')
                                            <button type="button" data-toggle-target="#registrationSettingModal"
                                                class="w-full py-1 px-2 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                                                <span>⚙️</span>
                                                <span>受付期間を再設定</span>
                                            </button>
                                            @else
                                            <form action="{{ route('feature-settings.toggle', 'staff_self_registration') }}" method="POST" class="flex-1">
                                                @csrf
                                                @method('PATCH')
                                                @if($selfRegistrationSetting->is_enabled)
                                                <button type="submit" class="w-full py-1 px-2 bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                                                    <span>⏹️</span>
                                                    <span>{{ $selfRegistrationStatus['status'] === 'upcoming' ? '受付予約を停止' : '即時停止' }}</span>
                                                </button>
                                                @else
                                                <button type="submit" class="w-full py-1 px-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                                                    <span>▶️</span>
                                                    <span>即時再開</span>
                                                </button>
                                                @endif
                                            </form>

                                            <button type="button" data-toggle-target="#registrationSettingModal"
                                                class="py-1 px-2 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded-lg text-[10px] font-bold transition flex items-center space-x-1 cursor-pointer whitespace-nowrap">
                                                <span>⚙️</span>
                                                <span>詳細設定</span>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- 右側: グローバル検索・通知ベル・ユーザー情報 & ログアウト -->
                <div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
                    @auth
                    <div class="flex items-center space-x-1.5 sm:space-x-2 shrink-0">
                        <!-- グローバルクイック検索トリガー (Ctrl+K) -->
                        <button type="button"
                            id="btn-open-quick-search"
                            data-action="open-quick-search"
                            aria-label="クイック検索 (Ctrl+K)"
                            title="システム横断検索 (Ctrl+K / Cmd+K)"
                            class="inline-flex items-center space-x-1.5 px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-medium transition cursor-pointer shrink-0">
                            <span>🔍</span>
                            <span class="hidden xl:inline">検索</span>
                            <kbd class="hidden xl:inline-block px-1 py-0.2 bg-slate-900 border border-slate-700 rounded text-[10px] font-mono text-slate-400">Ctrl K</kbd>
                        </button>

                        <!-- ヘッダー通知ベル (未対応現場要請) -->
                        <div class="nav-dropdown relative shrink-0">
                            <button type="button"
                                id="header-notification-bell"
                                aria-label="現場要請通知"
                                title="未対応の班長現場要請"
                                class="dropdown-trigger relative inline-flex items-center justify-center p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition cursor-pointer">
                                <span class="text-sm leading-none">🔔</span>
                                @if(($headerPendingRequestsCount ?? 0) > 0)
                                    <span id="header-notification-badge"
                                        class="absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white font-mono text-[10px] font-bold flex items-center justify-center shadow-xs">
                                        {{ $headerPendingRequestsCount > 99 ? '99+' : $headerPendingRequestsCount }}
                                    </span>
                                @endif
                            </button>

                            <div class="dropdown-menu hidden absolute right-0 mt-1.5 w-80 sm:w-96 max-h-[calc(100vh-4.5rem)] overflow-y-auto bg-slate-900 border border-slate-700/80 rounded-2xl shadow-xl p-2 z-50">
                                <div class="px-2.5 py-1.5 border-b border-slate-800 flex items-center justify-between">
                                    <span class="text-xs font-bold text-white flex items-center space-x-1.5">
                                        <span>🔔</span>
                                        <span>未対応の現場要請 ({{ $headerPendingRequestsCount ?? 0 }}件)</span>
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">全運行日</span>
                                </div>

                                @if(($headerPendingRequests ?? collect())->isNotEmpty())
                                    <div class="divide-y divide-slate-800/80 mt-1">
                                        @foreach($headerPendingRequests as $req)
                                            <div class="p-2.5 rounded-xl hover:bg-slate-800/70 transition space-y-1.5 text-xs">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-slate-100 truncate">
                                                            [{{ $req->lane?->name ?? '班' }}] {{ $req->type_label }}
                                                        </div>
                                                        <div class="text-[11px] text-slate-300 mt-0.5">
                                                            @if($req->request_type === 'equipment')
                                                                機材: <strong class="text-white">{{ $req->equipment_name }}</strong> × {{ $req->requested_count }}
                                                            @else
                                                                応援要請: <strong class="text-white">+{{ $req->requested_count }}名</strong>
                                                                @if($req->task)
                                                                    （{{ $req->task->title }}）
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $req->status === 'pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-400/30' : 'bg-sky-500/20 text-sky-300 border border-sky-400/30' }}">
                                                        {{ $req->status_label }}
                                                    </span>
                                                </div>

                                                @if($req->reason)
                                                    <p class="text-[11px] text-slate-400 line-clamp-2">{{ $req->reason }}</p>
                                                @endif

                                                <div class="flex items-center justify-between gap-2 pt-1">
                                                    <div class="text-[10px] text-slate-400 truncate">
                                                        申請: {{ $req->requestedByStaff?->name ?? '班長' }}
                                                        @if($req->timetable_id)
                                                            · <a href="{{ route('timetables.show', $req->timetable_id) }}" class="text-indigo-300 hover:underline">運行計画を開く</a>
                                                        @endif
                                                    </div>

                                                    @can('update', $req->lane ?? App\Models\Lane::class)
                                                    <div class="flex items-center space-x-1 shrink-0">
                                                        @if($req->status === 'pending')
                                                        <form action="{{ route('lane-requests.update-status', $req) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="px-2 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[10px] font-bold transition cursor-pointer">
                                                                手配
                                                            </button>
                                                        </form>
                                                        @endif
                                                        <form action="{{ route('lane-requests.update-status', $req) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="resolved">
                                                            <button type="submit" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[10px] font-bold transition cursor-pointer">
                                                                完了
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endcan
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-6 px-3 text-center text-xs text-slate-400">
                                        現在、未対応の現場要請はありません。
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-right hidden sm:block shrink-0">
                            <div class="text-xs font-bold text-white flex items-center justify-end space-x-1.5 whitespace-nowrap">
                                <span class="text-[11px] text-slate-300 truncate max-w-[100px]">{{ Auth::user()->name }}</span>
                                @if(Auth::user()->isAdmin())
                                <span class="bg-purple-900/80 text-purple-200 border border-purple-500/40 text-[9px] px-1.5 py-0.2 rounded font-bold whitespace-nowrap shrink-0">
                                    管理者
                                </span>
                                @else
                                <span class="bg-slate-800 text-slate-300 border border-slate-700 text-[9px] px-1.5 py-0.2 rounded font-medium whitespace-nowrap shrink-0">
                                    オペレーター
                                </span>
                                @endif
                            </div>
                        </div>

                        <!-- プロフィールショートカット -->
                        <a href="{{ route('profile') }}"
                            class="p-1.5 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition shrink-0"
                            title="プロフィール確認">
                            <span class="text-xs">👤</span>
                        </a>

                        <!-- ログアウトフォーム -->
                        <form action="{{ route('logout') }}" method="POST" class="inline shrink-0">
                            @csrf
                            <button type="submit"
                                data-confirm="ログアウトしますか？"
                                class="px-2 py-1 bg-slate-800 hover:bg-red-600 hover:text-white text-slate-300 text-xs font-medium rounded-lg border border-slate-700 hover:border-red-600 transition flex items-center space-x-1 cursor-pointer whitespace-nowrap shrink-0">
                                <span>🚪</span>
                                <span class="hidden lg:inline whitespace-nowrap">ログアウト</span>
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>

            </div>
        </div>

        <!-- モバイル用ナビゲーションタブバー（現場最適化4枠） -->
        <div class="md:hidden border-t border-slate-800 bg-slate-900/95 px-2 py-1 flex items-center justify-around text-xs">
            <a href="{{ route('timetable.index') }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('timetable.index') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">⏱️</span>
                <span class="text-[10px] mt-0.5">進行表</span>
            </a>
            @if($nav->targetPrintTimetable())
            <a href="{{ route('timetables.staff-timeline', $nav->targetPrintTimetable()) }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('timetables.staff-timeline') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">👥</span>
                <span class="text-[10px] mt-0.5">当日配置</span>
            </a>
            @else
            <a href="{{ route('staff.index') }}" class="flex flex-col items-center py-1 px-2 whitespace-nowrap {{ request()->routeIs('staff.*') ? 'text-indigo-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                <span class="text-base">👥</span>
                <span class="text-[10px] mt-0.5">スタッフ</span>
            </a>
            @endif
            @if($nav->targetPrintTimetable())
            <a href="{{ route('timetables.print-assignments', $nav->targetPrintTimetable()) }}" target="_blank" class="flex flex-col items-center py-1 px-2 whitespace-nowrap text-slate-400 hover:text-white">
                <span class="text-base">🖨️</span>
                <span class="text-[10px] mt-0.5">配置表</span>
            </a>
            @else
            <a href="{{ route('staff.print-cards') }}" target="_blank" class="flex flex-col items-center py-1 px-2 whitespace-nowrap text-slate-400 hover:text-white">
                <span class="text-base">🪪</span>
                <span class="text-[10px] mt-0.5">名札印刷</span>
            </a>
            @endif
            <button type="button" data-toggle="mobile-menu" class="flex flex-col items-center py-1 px-2 whitespace-nowrap text-slate-400 hover:text-white cursor-pointer">
                <span class="text-base">☰</span>
                <span class="text-[10px] mt-0.5">メニュー</span>
            </button>
        </div>

        <!-- モバイル用メニューモーダル -->
        <div id="mobileMenuModal" class="hidden md:hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="モバイルメニュー">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-toggle="mobile-menu"></div>
            <div class="fixed inset-x-0 bottom-0 bg-slate-900 border-t border-slate-800 rounded-t-3xl p-4 z-10 max-h-[85vh] overflow-y-auto space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                    <span class="text-sm font-bold text-white flex items-center space-x-2">
                        <span>🏮</span>
                        <span>すべてのメニュー</span>
                    </span>
                    <button type="button" data-toggle="mobile-menu" class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer">✕</button>
                </div>

                <div class="space-y-3.5">
                    <!-- 1. ⏱️ 当日運行 -->
                    <div>
                        <div class="text-[11px] font-bold text-indigo-400 mb-1.5 flex items-center justify-between">
                            <span>⏱️ 当日運行 (Operations)</span>
                            <span class="text-[9px] text-emerald-400 font-bold">当日</span>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 text-xs">
                            <a href="{{ route('dashboard') }}" class="col-span-2 p-2 bg-indigo-950/70 border border-indigo-700/60 rounded-xl flex items-center justify-between text-white">
                                <span class="flex items-center space-x-2 min-w-0">
                                    <span class="text-base shrink-0">📊</span>
                                    <span class="font-bold truncate">本部ダッシュボード</span>
                                </span>
                                <span class="text-[9px] text-emerald-400 font-mono font-bold shrink-0">司令塔</span>
                            </a>
                            <a href="{{ route('timetable.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-white min-w-0">
                                <span class="text-base shrink-0">⏱️</span>
                                <span class="font-bold truncate">当日進行表</span>
                            </a>
                            @if($nav->targetPrintTimetable())
                            <a href="{{ route('timetables.staff-timeline', $nav->targetPrintTimetable()) }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-white min-w-0">
                                <span class="text-base shrink-0">👥</span>
                                <span class="font-bold truncate">スタッフタイムライン</span>
                            </a>
                            @endif
                            @if($nav->hasTimetables())
                            <a href="{{ route('logistics-timeline.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🚚</span>
                                <span class="font-bold truncate">搬出入タイムライン</span>
                            </a>
                            @else
                            <div class="col-span-2 p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                                <span class="text-base shrink-0 grayscale opacity-60">🚚</span>
                                <span class="font-bold truncate">搬出入タイムライン</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- 2. 📋 準備・計画 -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 mb-1.5 flex items-center justify-between">
                            <span>📋 準備・計画 (Planning)</span>
                            <span class="text-[9px] text-slate-400 font-mono">事前準備</span>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 text-xs">
                            <a href="{{ route('preparation-navigator.index') }}" class="col-span-2 p-2 bg-indigo-950/80 border border-indigo-700/60 rounded-xl flex items-center justify-between text-white">
                                <span class="flex items-center space-x-2 min-w-0">
                                    <span class="text-base shrink-0">🚀</span>
                                    <span class="font-bold truncate">運行準備ナビ</span>
                                </span>
                                <span class="text-[10px] text-indigo-300 font-mono shrink-0">Step 1〜4 →</span>
                            </a>
                            <a href="{{ route('timetables.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📅</span>
                                <span class="font-bold truncate">運行日程管理</span>
                            </a>
                            <!-- 担当班編成 -->
                            @if($nav->hasTimetables())
                            <a href="{{ route('lanes.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🏷️</span>
                                <span class="font-bold truncate">担当班編成</span>
                            </a>
                            @else
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                                <span class="text-base shrink-0 grayscale opacity-60">🏷️</span>
                                <span class="font-bold truncate">担当班編成</span>
                            </div>
                            @endif

                            <!-- 作業タスク台帳 -->
                            @if($nav->hasTimetables())
                            <a href="{{ route('timetable-tasks.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📝</span>
                                <span class="font-bold truncate">作業タスク台帳</span>
                            </a>
                            @else
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                                <span class="text-base shrink-0 grayscale opacity-60">📝</span>
                                <span class="font-bold truncate">作業タスク台帳</span>
                            </div>
                            @endif

                            <!-- 人員事前配置 -->
                            @if($nav->hasTimetables())
                            <a href="{{ route('assignments.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">👥</span>
                                <span class="font-bold truncate">人員事前配置</span>
                            </a>
                            @else
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                                <span class="text-base shrink-0 grayscale opacity-60">👥</span>
                                <span class="font-bold truncate">人員事前配置</span>
                            </div>
                            @endif

                            <!-- 搬出入計画 -->
                            @if($nav->hasTimetables())
                            <a href="{{ route('logistics.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🚚</span>
                                <span class="font-bold truncate">搬出入計画</span>
                            </a>
                            <a href="{{ route('timetables.layouts.index', $nav->targetPrintTimetable() ?? 1) }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🗺️</span>
                                <span class="font-bold truncate">配置計画</span>
                            </a>
                            <a href="{{ route('timetables.site-maps.editor', $nav->targetPrintTimetable() ?? 1) }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🎨</span>
                                <span class="font-bold truncate">白地図エディタ</span>
                            </a>
                            @else
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                                <span class="text-base shrink-0 grayscale opacity-60">🚚</span>
                                <span class="font-bold truncate">搬出入計画</span>
                            </div>
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                                <span class="text-base shrink-0 grayscale opacity-60">🗺️</span>
                                <span class="font-bold truncate">配置計画</span>
                            </div>
                            @endif
                            <a href="{{ route('calendar.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📅</span>
                                <span class="font-bold truncate">総合カレンダー</span>
                            </a>
                            <a href="{{ route('schedules.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📋</span>
                                <span class="font-bold truncate">スケジュール管理</span>
                            </a>
                        </div>
                    </div>

                    <!-- 3. 🗂️ 台帳・マスター -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 mb-1.5">🗂️ 台帳・マスター (Master Data)</div>
                        <div class="grid grid-cols-2 gap-1.5 text-xs">
                            <a href="{{ route('organizations.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🏛️</span>
                                <span class="font-bold truncate">組織管理</span>
                            </a>
                            <a href="{{ route('staff.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">👷</span>
                                <span class="font-bold truncate">スタッフ名簿</span>
                            </a>
                            <a href="{{ route('vendors.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🎪</span>
                                <span class="font-bold truncate">出展・出店者マスター</span>
                            </a>
                            <a href="{{ route('equipments.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📦</span>
                                <span class="font-bold truncate">機材・備品一覧</span>
                            </a>
                            <a href="{{ route('locations.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📍</span>
                                <span class="font-bold truncate">拠点マスター</span>
                            </a>
                            <a href="{{ route('location-types.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🏷️</span>
                                <span class="font-bold truncate">拠点タイプ管理</span>
                            </a>
                            <a href="{{ route('equipment-patterns.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🧩</span>
                                <span class="font-bold truncate">搬出入パターン</span>
                            </a>
                        </div>
                    </div>

                    <!-- 4. 🖨️ 帳票・印刷 (区分A: 全体・マスター帳票) -->
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 mb-1.5 flex items-center justify-between">
                            <span>🖨️ 帳票・印刷 (区分A: 全体・マスター)</span>
                            <span class="text-[9px] text-indigo-300 font-mono">年間・共通</span>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 text-xs">
                            <a href="{{ route('organizations.print') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0 hover:bg-slate-700/80 transition">
                                <span class="text-base shrink-0">🏛️</span>
                                <span class="font-bold truncate">組織図 (PDF) ↓</span>
                            </a>
                            <a href="{{ route('staff.print-cards') }}" target="_blank" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🪪</span>
                                <span class="font-bold truncate">名札一括印刷 ↗</span>
                            </a>
                            <a href="{{ route('staff.export-csv') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📥</span>
                                <span class="font-bold truncate">スタッフCSV ↓</span>
                            </a>
                            <a href="{{ route('schedules.pdf') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0 hover:bg-slate-700/80 transition">
                                <span class="text-base shrink-0">📅</span>
                                <span class="font-bold truncate">年間スケジュール (PDF) ↓</span>
                            </a>
                            <a href="{{ route('staff.pdf') }}"
                                class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0 hover:bg-slate-700/80 transition"
                                title="全登録スタッフ一覧のA4縦・2段組帳票PDFをダウンロード">
                                <span class="text-base shrink-0">👥</span>
                                <span class="font-bold truncate">スタッフ名簿 (PDF) ↓</span>
                            </a>
                            <div class="col-span-2 p-2 bg-slate-800/40 rounded-xl flex items-center justify-between text-slate-500 min-w-0 opacity-70">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="text-base shrink-0">📦</span>
                                    <span class="truncate">レンタル機材一覧 (PDF)</span>
                                </div>
                                <span class="text-[9px] bg-slate-800 text-slate-400 px-1 rounded font-mono shrink-0">準備中</span>
                            </div>
                        </div>
                        <div class="mt-1.5 px-2 py-1 text-[10px] text-slate-400 bg-slate-800/30 rounded-lg">
                            💡 運行日別（タイムテーブル等）の帳票は各運行詳細から出力できます
                        </div>
                    </div>

                    <!-- 5. ⚙️ ガイド・設定 -->
                    <div class="space-y-2 pt-2 border-t border-slate-800">
                        <div class="text-[11px] font-bold text-slate-400 mb-1.5">⚙️ ガイド・設定</div>
                        <div class="grid grid-cols-2 gap-1.5 text-xs">
                            <a href="{{ route('manual.index') }}" class="p-2 bg-indigo-950/40 border border-indigo-700/50 rounded-xl flex items-center space-x-2 text-indigo-200 min-w-0">
                                <span class="text-base shrink-0">📖</span>
                                <span class="font-bold truncate">操作マニュアル</span>
                            </a>
                            <a href="{{ route('presentation.index') }}" class="p-2 bg-indigo-950/40 border border-indigo-700/50 rounded-xl flex items-center space-x-2 text-indigo-200 min-w-0">
                                <span class="text-base shrink-0">📽️</span>
                                <span class="font-bold truncate">幹事会プレゼン</span>
                            </a>
                            @can('viewAny', App\Models\User::class)
                            <a href="{{ route('users.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">👥</span>
                                <span class="font-bold truncate">ユーザー管理</span>
                            </a>
                            @endcan
                            <a href="{{ route('staff.join') }}" target="_blank" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🌐</span>
                                <span class="font-bold truncate">セルフ登録フォーム ↗</span>
                            </a>
                            <!-- Webセルフ登録機能 ON/OFF・受付管理カード（モバイル） -->
                            @if(isset($selfRegistrationSetting) && isset($selfRegistrationStatus))
                            <div class="col-span-2 p-2.5 bg-slate-800/90 border border-slate-700/70 rounded-xl space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-200 flex items-center space-x-1.5">
                                        <span>🌐</span>
                                        <span>Webセルフ登録機能</span>
                                    </span>
                                    @if($selfRegistrationStatus['status'] === 'open')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center space-x-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span>受付中</span>
                                    </span>
                                    @elseif($selfRegistrationStatus['status'] === 'upcoming')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                        受付前(予約)
                                    </span>
                                    @elseif($selfRegistrationStatus['status'] === 'closed')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-600/30 text-slate-300 border border-slate-500/30">
                                        受付終了
                                    </span>
                                    @else
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-500/20 text-red-300 border border-red-400/30">
                                        停止中(手動)
                                    </span>
                                    @endif
                                </div>

                                <div class="flex items-center space-x-1.5 pt-1">
                                    @if($selfRegistrationStatus['status'] === 'closed')
                                    <button type="button" data-toggle-target="#registrationSettingModal"
                                        class="w-full py-1 px-2 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                                        <span>⚙️</span>
                                        <span>受付期間を再設定</span>
                                    </button>
                                    @else
                                    <form action="{{ route('feature-settings.toggle', 'staff_self_registration') }}" method="POST" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        @if($selfRegistrationSetting->is_enabled)
                                        <button type="submit" class="w-full py-1 px-2 bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                                            <span>⏹️</span>
                                            <span>{{ $selfRegistrationStatus['status'] === 'upcoming' ? '受付予約を停止' : '即時停止' }}</span>
                                        </button>
                                        @else
                                        <button type="submit" class="w-full py-1 px-2 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
                                            <span>▶️</span>
                                            <span>即時再開</span>
                                        </button>
                                        @endif
                                    </form>

                                    <button type="button" data-toggle-target="#registrationSettingModal"
                                        class="py-1 px-2 bg-slate-700 hover:bg-slate-600 text-slate-200 border border-slate-600 rounded-lg text-[10px] font-bold transition flex items-center space-x-1 cursor-pointer whitespace-nowrap">
                                        <span>⚙️</span>
                                        <span>詳細設定</span>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="pt-2">
                            <form action="{{ route('logout') }}" method="POST" class="w-full">
                                @csrf
                                <button type="submit" data-confirm="ログアウトしますか？" class="w-full p-2 bg-red-950/40 hover:bg-red-900/60 border border-red-800/50 rounded-xl flex items-center justify-center space-x-2 text-red-200 cursor-pointer text-xs font-bold">
                                    <span>🚪</span>
                                    <span>ログアウト</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 共通: セルフ登録受付設定モーダル (全画面から起動可能) -->
        @if(isset($selfRegistrationSetting))
        <div id="registrationSettingModal" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="registrationSettingModal-title">
            <div data-close-target="#registrationSettingModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity cursor-pointer"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-xl transition-all sm:w-full sm:max-w-md border border-slate-200 z-10 text-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 id="registrationSettingModal-title" class="text-sm font-bold text-slate-800 flex items-center space-x-1.5">
                            <span>⚙️</span>
                            <span>スタッフセルフ登録 受付設定</span>
                        </h3>
                        <button type="button" data-close-target="#registrationSettingModal" aria-label="閉じる" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
                    </div>

                    <form action="{{ route('feature-settings.update', 'staff_self_registration') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- 有効/無効 スイッチ -->
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div>
                                <span class="block text-xs font-bold text-slate-800">機能の有効化</span>
                                <span class="block text-[11px] text-slate-500">オフにすると期間内でも即時停止されます</span>
                            </div>
                            <input type="hidden" name="is_enabled" value="0">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_enabled" value="1" class="sr-only peer"
                                    {{ old('is_enabled', $selfRegistrationSetting->is_enabled) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- 受付開始日時 -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="opened_at" class="block text-xs font-bold text-slate-700">
                                    受付開始日時（日本時間・未指定時は即時開始）
                                </label>
                                <button type="button" data-clear-target="opened_at" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer">
                                    ✕ クリア
                                </button>
                            </div>
                            <input type="datetime-local" id="opened_at" name="opened_at"
                                value="{{ old('opened_at', $selfRegistrationSetting->opened_at?->timezone('Asia/Tokyo')->format('Y-m-d\TH:i')) }}"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- 受付終了日時 -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="closed_at" class="block text-xs font-bold text-slate-700">
                                    受付終了日時（日本時間・未指定時は無期限）
                                </label>
                                <button type="button" data-clear-target="closed_at" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-medium cursor-pointer">
                                    ✕ クリア
                                </button>
                            </div>
                            <input type="datetime-local" id="closed_at" name="closed_at"
                                value="{{ old('closed_at', $selfRegistrationSetting->closed_at?->timezone('Asia/Tokyo')->format('Y-m-d\TH:i')) }}"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- 停止時・期間外メッセージ -->
                        <div>
                            <label for="notice_message" class="block text-xs font-bold text-slate-700 mb-1">
                                特記事項・案内メッセージ（任意）
                            </label>
                            <textarea id="notice_message" name="notice_message" rows="3"
                                placeholder="受付停止中や期間外に表示したい案内文があれば入力してください（未入力の場合は自動生成メッセージを表示）。"
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notice_message', $selfRegistrationSetting->notice_message ?? '') }}</textarea>
                        </div>

                        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                            <button type="button" data-close-target="#registrationSettingModal"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
                                キャンセル
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                                設定を保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </header>

    <!-- グローバルフラッシュメッセージ -->
    @if (session('success') || session('status') || session('error'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 space-y-2">
        @if (session('success'))
        <div data-alert-box role="status" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-bold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center space-x-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" data-dismiss-alert aria-label="メッセージを閉じる" class="text-emerald-600 hover:text-emerald-900 font-bold px-1 cursor-pointer">✕</button>
        </div>
        @endif

        @if (session('status'))
        <div data-alert-box role="status" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-bold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center space-x-2">
                <span>✅</span>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" data-dismiss-alert aria-label="メッセージを閉じる" class="text-emerald-600 hover:text-emerald-900 font-bold px-1 cursor-pointer">✕</button>
        </div>
        @endif

        @if (session('error'))
        <div data-alert-box role="alert" class="p-3 bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl font-bold flex items-center justify-between gap-2 shadow-2xs">
            <div class="flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" data-dismiss-alert aria-label="メッセージを閉じる" class="text-red-600 hover:text-red-900 font-bold px-1 cursor-pointer">✕</button>
        </div>
        @endif
    </div>
    @endif

    <!-- メインコンテンツスロット -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- フッター (スリム統一) -->
    <footer class="bg-white border-t border-slate-200 mt-8 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p>© {{ now()->year }} まつり実行委員会 運行本部</p>
        </div>
    </footer>

    @auth
    <!-- グローバル横断検索モーダル (Ctrl+K / Cmd+K) -->
    <div id="quickSearchModal"
        class="hidden fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="quick-search-title"
        data-search-url="{{ route('search.quick') }}">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-close-quick-search></div>
        <div class="flex min-h-full items-start justify-center p-4 sm:pt-16">
            <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 max-w-2xl w-full overflow-hidden">
                <div class="p-3.5 border-b border-slate-200 flex items-center space-x-2.5">
                    <span class="text-base text-slate-400 shrink-0">🔍</span>
                    <h2 id="quick-search-title" class="sr-only">システム横断クイック検索</h2>
                    <input type="text"
                        id="quick-search-input"
                        autocomplete="off"
                        placeholder="スタッフ名、出展者、機材、スケジュール、タスクを検索..."
                        class="flex-1 bg-transparent border-0 text-sm text-slate-900 placeholder-slate-400 focus:outline-none">
                    <button type="button" data-close-quick-search
                        class="px-2 py-1 text-[11px] font-mono text-slate-500 hover:text-slate-800 bg-slate-100 rounded-md cursor-pointer shrink-0">
                        ESC
                    </button>
                </div>

                <div id="quick-search-results" class="max-h-[60vh] overflow-y-auto divide-y divide-slate-100 p-2">
                    <div class="py-8 text-center text-xs text-slate-400">
                        検索キーワードを入力してください（スタッフ・出展者・機材・予定・タスク）
                    </div>
                </div>

                <div class="px-4 py-2 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500">
                    <span id="quick-search-status">↑↓ キーで移動 · Enter で開く</span>
                    <span class="font-mono">Ctrl + K / Esc</span>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <!-- アプリ共通カスタム確認モーダル (window.confirm置換) -->
    <div id="appConfirmModal"
        class="hidden fixed inset-0 z-[60] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="app-confirm-title">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" data-confirm-cancel></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center space-x-2">
                    <span id="app-confirm-icon" class="text-lg">⚠️</span>
                    <h3 id="app-confirm-title" class="text-sm font-bold text-slate-900">操作の確認</h3>
                </div>
                <div class="p-5">
                    <p id="app-confirm-message" class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line"></p>
                </div>
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
                    <button type="button"
                        id="app-confirm-cancel-btn"
                        data-confirm-cancel
                        class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer">
                        キャンセル
                    </button>
                    <button type="button"
                        id="app-confirm-ok-btn"
                        data-confirm-ok
                        class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition cursor-pointer shadow-xs">
                        実行する
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>