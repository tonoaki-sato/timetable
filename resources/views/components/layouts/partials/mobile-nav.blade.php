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
            <!-- 1. 📋 準備・計画 -->
            <div>
                <div class="text-[11px] font-bold text-slate-400 mb-1.5 flex items-center justify-between">
                    <span>📋 準備・計画 (Planning)</span>
                    <span class="text-[9px] text-slate-400 font-mono">事前準備</span>
                </div>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    <a href="{{ route('timetables.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">📅</span>
                        <span class="font-bold truncate">運行日程管理</span>
                    </a>
                    <!-- 担当班編成 -->
                    @if($nav->hasTimetables())
                    <a href="{{ route('timetable-lanes.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🏷️</span>
                        <span class="font-bold truncate">担当班編成</span>
                    </a>
                    @else
                    <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">🏷️</span>
                        <span class="font-bold truncate">担当班編成</span>
                    </div>
                    @endif

                    <!-- タスク計画 -->
                    @if($nav->hasTimetables())
                    <a href="{{ route('timetable-tasks.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">📝</span>
                        <span class="font-bold truncate">タスク計画</span>
                    </a>
                    @else
                    <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">📝</span>
                        <span class="font-bold truncate">タスク計画</span>
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
                    <a href="{{ route('timetables.cone-maps.editor', $nav->targetPrintTimetable() ?? 1) }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🔺</span>
                        <span class="font-bold truncate">カラーコーン配置エディタ</span>
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
                    <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">🔺</span>
                        <span class="font-bold truncate">カラーコーン配置エディタ</span>
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

            <!-- 2. ⏱️ 当日運行 -->
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
                    @if($nav->hasTimetables())
                    <a href="{{ route('lane-timeline.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-white min-w-0">
                        <span class="text-base shrink-0">📊</span>
                        <span class="font-bold truncate">班別タイムライン</span>
                    </a>
                    @else
                    <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">📊</span>
                        <span class="font-bold truncate">班別タイムライン</span>
                    </div>
                    @endif
                    @if($nav->targetPrintTimetable())
                    <a href="{{ route('timetables.staff-timeline', $nav->targetPrintTimetable()) }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-white min-w-0">
                        <span class="text-base shrink-0">👥</span>
                        <span class="font-bold truncate">スタッフタイムライン</span>
                    </a>
                    @endif
                    @if($nav->hasTimetables())
                    <a href="{{ route('logistics-timeline.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🚚</span>
                        <span class="font-bold truncate">搬出入タイムライン</span>
                    </a>
                    @else
                    <div class="p-2 bg-slate-800/40 rounded-xl flex items-center space-x-2 text-slate-500 opacity-50 cursor-not-allowed select-none min-w-0" title="運行日程の登録が必要です">
                        <span class="text-base shrink-0 grayscale opacity-60">🚚</span>
                        <span class="font-bold truncate">搬出入タイムライン</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 3. 🗂️ マスター管理 -->
            <div>
                <div class="text-[11px] font-bold text-slate-400 mb-1.5">🗂️ マスター管理 (Master Data)</div>
                <div class="grid grid-cols-2 gap-1.5 text-xs">
                    <a href="{{ route('organizations.index') }}" class="col-span-2 p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🏛️</span>
                        <span class="font-bold truncate">組織管理</span>
                    </a>
                    <a href="{{ route('staff.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">👷</span>
                        <span class="font-bold truncate">スタッフ名簿</span>
                    </a>
                    <a href="{{ route('lanes.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🏷️</span>
                        <span class="font-bold truncate">担当班マスター</span>
                    </a>
                    <a href="{{ route('vendors.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🎪</span>
                        <span class="font-bold truncate">出展・出店者マスター</span>
                    </a>
                    <a href="{{ route('site-base-maps.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🗺️</span>
                        <span class="font-bold truncate">白地図マスター</span>
                    </a>
                    <a href="{{ route('layout-areas.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">📐</span>
                        <span class="font-bold truncate">会場エリアマスター</span>
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
                    <a href="{{ route('equipment-patterns.index') }}" class="p-2 bg-slate-800/80 rounded-xl flex items-center space-x-2 text-slate-200 min-w-0">
                        <span class="text-base shrink-0">🧩</span>
                        <span class="font-bold truncate">搬出入パターン</span>
                    </a>
                </div>
            </div>

            <!-- 4. ⚙️ システム・ガイド -->
            <div class="space-y-2 pt-2 border-t border-slate-800">
                <div class="text-[11px] font-bold text-slate-400 mb-1.5">⚙️ システム・ガイド</div>
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
                    @include('components.layouts.partials.self-registration-card', [
                        'cardTitle' => 'Webセルフ登録機能',
                        'wrapperClass' => 'col-span-2',
                    ])
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
