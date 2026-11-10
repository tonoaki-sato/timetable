<x-layouts.app title="本部総合統括ポータル">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-3">

    <!-- 1. 最上部: 本部統括ポータルヘッダー (1行コンパクトヘッダー) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-xl px-4 py-2.5 text-white shadow-md border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-2.5">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <h1 class="text-base sm:text-lg font-bold tracking-tight flex items-center space-x-2">
                <span>🏮</span>
                <span>本部運行総合ダッシュボード</span>
            </h1>
            <span class="inline-flex items-center space-x-1.5 text-[11px] text-emerald-400 font-mono bg-emerald-950/60 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>システム正常稼働中</span>
            </span>
            <span class="text-[11px] text-slate-300">
                ログイン: <strong class="text-white">{{ Auth::user()->name ?? '本部スタッフ' }}</strong>
                ({{ Auth::user()?->isAdmin() ? '管理者' : 'オペレーター' }})
            </span>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
            <div class="bg-slate-800/80 backdrop-blur px-2.5 py-1 rounded-lg border border-slate-700/80 flex items-center space-x-2 text-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] text-slate-400 font-mono">現在日時:</span>
                <span id="realtime-clock"
                      data-server-time="{{ now('Asia/Tokyo')->valueOf() }}"
                      class="font-bold font-mono text-white tracking-wide text-xs">
                    {{ $nowDatetime ?? now('Asia/Tokyo')->translatedFormat('n月j日 (D) H:i:s') }}
                </span>
            </div>
        </div>
    </div>

    <!-- 2. 年間フェーズ & まつり本番カウントダウン バナー -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-stretch">
        <div class="md:col-span-2">
            <x-dashboard.phase-card :phase-context="$overview->phaseContext" />
        </div>

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] text-amber-800 font-bold block leading-tight">
                    🏮 {{ $overview->nextEventName ?? '本祭' }}まで
                </span>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold">
                    カウントダウン
                </span>
            </div>
            <div class="my-1">
                @if($overview->daysUntilMainEvent !== null)
                <span class="text-2xl font-black text-amber-900 font-mono">
                    あと {{ $overview->daysUntilMainEvent }}日
                </span>
                @else
                <span class="text-xs text-slate-500 font-bold">日程未定</span>
                @endif
            </div>
            <a href="{{ route('schedules.index') }}" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold flex items-center justify-between pt-1 border-t border-slate-100">
                <span>年間行事進捗: {{ $overview->scheduleCompletedCount }}/{{ $overview->scheduleTotalCount }}件</span>
                <span>→</span>
            </a>
        </div>
    </div>

    <!-- 3. 薄型 4大全体運営KPIストリップ -->
    <x-dashboard.global-kpi-strip :overview="$overview" />

    <!-- 4. 高密度 3カラム メインコックピット (ゼロスクロール最適化・カード内独立スクロール) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-start">

        <!-- 左カラム (lg:col-span-4): 会議 & 許認可・申請スケジュール -->
        <div class="lg:col-span-4 space-y-3">
            <!-- 直近の会議・打合せ -->
            <x-dashboard.upcoming-meetings-card :limit="3" />

            <!-- 直近の許認可・申請・説明会 -->
            <x-dashboard.upcoming-permits-card :permits="$overview->upcomingPermits" />
        </div>

        <!-- 中央カラム (lg:col-span-4): 運行日程（運行計画）ゲートウェイ -->
        <div class="lg:col-span-4 space-y-3">
            <x-dashboard.timetables-gateway-card :timetables="$overview->timetables" />
        </div>

        <!-- 右カラム (lg:col-span-4): 組織・資機材・拠点マスタ & クイックランチャー -->
        <div class="lg:col-span-4 space-y-3">
            <x-dashboard.resources-summary-card :overview="$overview" />
        </div>

    </div>

</div>
</x-layouts.app>
