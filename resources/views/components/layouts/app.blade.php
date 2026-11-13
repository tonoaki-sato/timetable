@props(['title' => 'まつり総合管理システム 2026'])
<!DOCTYPE html>
<html lang="ja" class="h-full bg-slate-50 text-slate-800">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - まつり総合管理システム</title>
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
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-80 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
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
                                        <span>👥 人員当日配置・動態調整</span>
                                        <span class="text-[9px] text-amber-300 font-mono font-bold">現場対応</span>
                                    </div>

                                    <!-- 人員当日配置 (稼働タイムライン) -->
                                    @if($nav->targetPrintTimetable())
                                    <a href="{{ route('timetables.staff-timeline', $nav->targetPrintTimetable()) }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.staff-timeline') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👥</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">人員当日配置</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">タイムライン・空き枠特定・動態調整</p>
                                        </div>
                                    </a>
                                    @endif

                                    <!-- 当日スタッフ登録（紹介・飛び入り） -->
                                    <a href="{{ route('staff.create') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('staff.create') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👷</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center justify-between gap-1">
                                                <span class="whitespace-nowrap truncate">当日スタッフ登録</span>
                                                <span class="text-[9px] font-mono font-bold px-1.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 shrink-0">即時受付</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">紹介者同班配属 / 不足タスク充当 / 名札発行</p>
                                        </div>
                                    </a>

                                    <!-- 搬出入チェック -->
                                    <a href="{{ route('logistics.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ $nav->isLogisticsPlansActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🚚</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">搬出入チェック</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">設営・本番・撤収の資材追跡チェック</p>
                                        </div>
                                    </a>
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
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-80 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
                                <!-- 運行準備ナビ -->
                                <a href="{{ route('preparation-navigator.index') }}"
                                    class="flex items-center space-x-2 p-2 rounded-xl transition mb-1 {{ request()->routeIs('preparation-navigator.*') ? 'bg-indigo-600 text-white shadow-xs' : 'bg-indigo-950/70 border border-indigo-700/50 text-slate-200 hover:bg-indigo-900/80 hover:text-white' }}">
                                    <span class="text-base shrink-0">🚀</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold flex items-center justify-between gap-1">
                                            <span class="whitespace-nowrap truncate">運行準備ナビ</span>
                                            <span class="text-[9px] font-mono font-bold px-1.5 rounded bg-indigo-500/30 text-indigo-200 shrink-0">Step 1〜4</span>
                                        </div>
                                        <p class="text-[10px] text-indigo-200/80 truncate mt-0.5">日程・班・タスク・人員配置の手順ガイド</p>
                                    </div>
                                </a>

                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg">
                                    📋 編成・業務計画
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <a href="{{ route('timetables.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.index') || request()->routeIs('timetables.show') || request()->routeIs('timetables.create') || request()->routeIs('timetables.edit') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📅</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">運行日程管理</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">各日の運行日程・基本情報台帳</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('lanes.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('lanes.*') || request()->routeIs('timetables.lanes.*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🏷️</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">担当班編成</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">各日の班編成・現場リーダー指名</p>
                                        </div>
                                    </a>
                                    <a href="{{ route('timetable-tasks.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetable-tasks.*') || request()->routeIs('timetables.tasks.index') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">📝</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">作業タスク台帳</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">全タスク登録・必要人数設定</p>
                                        </div>
                                    </a>
                                    @if($nav->targetPrintTimetable())
                                    <a href="{{ route('timetables.assignments', $nav->targetPrintTimetable()) }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('timetables.assignments') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">👥</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center justify-between gap-1">
                                                <span class="whitespace-nowrap truncate">人員事前配置</span>
                                                <span class="text-[9px] font-mono font-bold px-1.5 rounded bg-indigo-500/20 text-indigo-300 shrink-0">事前計画</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">タスク定員充足率100%に向けた計画割当</p>
                                        </div>
                                    </a>
                                    @endif
                                    <a href="{{ route('logistics.index') }}"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ $nav->isLogisticsPlansActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🚚</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold whitespace-nowrap truncate">搬出入計画</div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">6大フェーズ運行・資材物流計画</p>
                                        </div>
                                    </a>
                                </div>

                                <div class="border-t border-slate-800/80 my-1"></div>

                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg">
                                    📅 日程・スケジュール
                                </div>
                                <div class="py-1 space-y-0.5">
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

                        <!-- 3. 🗂️ マスター管理 ▾ -->
                        <div class="relative nav-dropdown shrink-0">
                            <button type="button"
                                class="dropdown-trigger px-2 py-1 lg:px-2.5 lg:py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 whitespace-nowrap cursor-pointer shrink-0 {{ $nav->isMasterActive() ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}">
                                <span>🗂️</span>
                                <span>マスター管理</span>
                                <span class="text-[9px] opacity-75">▼</span>
                            </button>
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-76 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
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
                            <div class="dropdown-menu hidden absolute left-0 mt-1.5 w-80 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
                                <div class="px-2.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-800/80 rounded-lg flex items-center justify-between">
                                    <span>🌐 全体・マスター帳票 (区分A)</span>
                                    <span class="text-[9px] text-indigo-300 font-mono font-bold">年間・共通</span>
                                </div>
                                <div class="py-1 space-y-0.5">
                                    <!-- 組織図印刷 -->
                                    <a href="{{ route('organizations.print') }}" target="_blank"
                                        class="flex items-center space-x-2 p-2 rounded-xl transition {{ request()->routeIs('organizations.print*') ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                        <span class="text-base shrink-0">🏛️</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center space-x-1">
                                                <span class="whitespace-nowrap truncate">組織図印刷 (PDF)</span>
                                                <span class="text-[10px] text-slate-400">↗</span>
                                            </div>
                                            <p class="text-[10px] text-slate-400 truncate mt-0.5">実行委員会組織体制の横向き帳票印刷</p>
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

                                    <!-- 年間スケジュール表 (未実装: テキストのみ表示) -->
                                    <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-70 cursor-not-allowed">
                                        <span class="text-base shrink-0">📅</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center justify-between">
                                                <span class="whitespace-nowrap truncate">年間スケジュール表 (PDF)</span>
                                                <span class="text-[9px] bg-slate-800 text-slate-400 px-1.5 py-0.2 rounded font-mono">準備中</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 truncate mt-0.5">年間工程・会議日程一覧表</p>
                                        </div>
                                    </div>

                                    <!-- スタッフ一覧 (未実装: テキストのみ表示) -->
                                    <div class="flex items-center space-x-2 p-2 rounded-xl text-slate-500 opacity-70 cursor-not-allowed">
                                        <span class="text-base shrink-0">👥</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold flex items-center justify-between">
                                                <span class="whitespace-nowrap truncate">スタッフ名簿一覧 (PDF)</span>
                                                <span class="text-[9px] bg-slate-800 text-slate-400 px-1.5 py-0.2 rounded font-mono">準備中</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 truncate mt-0.5">ボランティア・幹事の一覧印刷帳票</p>
                                        </div>
                                    </div>

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
                            <div class="dropdown-menu hidden absolute right-0 sm:left-auto lg:left-0 mt-1.5 w-76 bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl p-1.5 z-50">
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
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-500/20 text-rose-300 border border-rose-400/30">
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
                                                        <button type="submit" class="w-full py-1 px-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
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

                <!-- 右側: ログイン中ユーザー情報 & ログアウト -->
                <div class="flex items-center space-x-2 lg:space-x-3 shrink-0">
                    @auth
                    <div class="flex items-center space-x-2 shrink-0">
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
        <div id="mobileMenuModal" class="hidden md:hidden fixed inset-0 z-50">
            <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" data-toggle="mobile-menu"></div>
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
                                <span class="font-bold truncate">人員当日配置</span>
                            </a>
                            @endif
                            <a href="{{ route('staff.create') }}" class="col-span-2 p-2 bg-emerald-950/40 border border-emerald-600/40 rounded-xl flex items-center justify-between text-emerald-200">
                                <span class="flex items-center space-x-2 min-w-0">
                                    <span class="text-base shrink-0">👷</span>
                                    <span class="font-bold truncate">当日スタッフ登録</span>
                                </span>
                                <span class="text-[9px] font-mono text-emerald-300 shrink-0">名札手渡し直通 →</span>
                            </a>
                            <a href="{{ route('logistics.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🚚</span>
                                <span class="font-bold truncate">搬出入チェック</span>
                            </a>
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
                            <a href="{{ route('lanes.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🏷️</span>
                                <span class="font-bold truncate">担当班編成</span>
                            </a>
                            <a href="{{ route('timetable-tasks.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📝</span>
                                <span class="font-bold truncate">作業タスク台帳</span>
                            </a>
                            @if($nav->targetPrintTimetable())
                            <a href="{{ route('timetables.assignments', $nav->targetPrintTimetable()) }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">👥</span>
                                <span class="font-bold truncate">人員事前配置</span>
                            </a>
                            @endif
                            <a href="{{ route('logistics.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🚚</span>
                                <span class="font-bold truncate">搬出入計画</span>
                            </a>
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
                            <a href="{{ route('equipments.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📦</span>
                                <span class="font-bold truncate">機材・備品一覧</span>
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
                            <a href="{{ route('staff.print-cards') }}" target="_blank" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">🪪</span>
                                <span class="font-bold truncate">名札一括印刷 ↗</span>
                            </a>
                            <a href="{{ route('staff.export-csv') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                                <span class="text-base shrink-0">📥</span>
                                <span class="font-bold truncate">スタッフCSV ↓</span>
                            </a>
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center justify-between text-slate-500 min-w-0 opacity-70">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="text-base shrink-0">📅</span>
                                    <span class="truncate">年間スケジュール</span>
                                </div>
                                <span class="text-[9px] bg-slate-800 text-slate-400 px-1 rounded font-mono shrink-0">準備中</span>
                            </div>
                            <div class="p-2 bg-slate-800/40 rounded-xl flex items-center justify-between text-slate-500 min-w-0 opacity-70">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="text-base shrink-0">👥</span>
                                    <span class="truncate">スタッフ名簿</span>
                                </div>
                                <span class="text-[9px] bg-slate-800 text-slate-400 px-1 rounded font-mono shrink-0">準備中</span>
                            </div>
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
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-500/20 text-rose-300 border border-rose-400/30">
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
                                                <button type="submit" class="w-full py-1 px-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 rounded-lg text-[10px] font-bold transition flex items-center justify-center space-x-1 cursor-pointer">
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
        <div id="registrationSettingModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
            <div data-close-target="#registrationSettingModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity cursor-pointer"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all sm:w-full sm:max-w-md border border-slate-200 z-10 text-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center space-x-1.5">
                            <span>⚙️</span>
                            <span>スタッフセルフ登録 受付設定</span>
                        </h3>
                        <button type="button" data-close-target="#registrationSettingModal" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3">
        @if (session('success'))
        <div class="p-3 mb-2 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl font-medium flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <span>✔</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if (session('status'))
        <div class="p-3 mb-2 bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs rounded-xl font-medium flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <span>ℹ️</span>
                <span>{{ session('status') }}</span>
            </div>
        </div>
        @endif

        @if (session('error'))
        <div class="p-3 mb-2 bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl font-medium flex items-center justify-between shadow-2xs">
            <div class="flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- メインコンテンツスロット -->
    <main class="flex-1">
        {{ $slot }}
    </main>

    <!-- フッター (スリム統一) -->
    <footer class="bg-white border-t border-slate-200 mt-8 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p>© 2026 まつり実行委員会 運行本部総合システム</p>
        </div>
    </footer>

</body>

</html>
