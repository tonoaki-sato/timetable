<x-layouts.app title="本部運行ダッシュボード">
<div id="dashboard-realtime-root"
     data-realtime-url="{{ route('dashboard.realtime') }}"
     class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    <!-- 1. 最上部: 本部運行サマリーヘッダー (1行幅コンパクトレイアウト) -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 rounded-xl px-4 sm:px-5 py-3 text-white shadow-md border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <h1 class="text-lg sm:text-xl font-bold tracking-tight flex items-center space-x-2">
                <span>📊</span>
                <span>本部運行総合ダッシュボード</span>
            </h1>
            <span class="inline-flex items-center space-x-1.5 text-xs text-emerald-400 font-mono bg-emerald-950/60 border border-emerald-500/30 px-2.5 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>システム正常稼働中</span>
            </span>
            <span class="text-xs text-slate-300">
                ログイン中: <strong class="text-white">{{ Auth::user()->name ?? '本部スタッフ' }}</strong>
                ({{ Auth::user()?->isAdmin() ? '管理者' : 'オペレーター' }})
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <div class="bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-lg border border-slate-700/80 flex items-center space-x-2 text-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] text-slate-400 font-mono">現在日時:</span>
                <span id="realtime-clock"
                      data-server-time="{{ now('Asia/Tokyo')->valueOf() }}"
                      class="font-bold font-mono text-white tracking-wide">
                    {{ $nowDatetime ?? now('Asia/Tokyo')->translatedFormat('n月j日 (D) H:i:s') }}
                </span>
            </div>

            <button type="button"
                    id="dashboard-manual-refresh"
                    title="交通規制・進行中タスク・次期タスクを今すぐ最新状態に更新"
                    class="bg-slate-800/80 hover:bg-indigo-900/80 text-slate-200 hover:text-white px-2.5 py-1.5 rounded-lg border border-slate-700/80 hover:border-indigo-500/60 transition flex items-center space-x-1.5 text-xs cursor-pointer">
                <span id="dashboard-refresh-icon" class="inline-block text-xs leading-none">🔄</span>
                <span id="dashboard-realtime-status" class="text-[11px] font-mono text-slate-300">自動同期中</span>
            </button>
        </div>
    </div>

    <!-- 2. 主要KPIステータスカード群 (2カラム) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- フェーズ -->
        <x-dashboard.phase-card />

        <!-- 道路交通規制 (リアルタイム更新対象) -->
        <div id="dashboard-card-traffic-regulation">
            <x-dashboard.traffic-regulation-card :traffic-regulation-context="$trafficRegulationContext ?? null" />
        </div>
    </div>

    <!-- 3. メイングリッド (2カラム: Now & Next / アラート監視) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 左側2カラム: Now & Next ビュー (F-106) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Now: 現在進行中のタスク (リアルタイム更新対象) -->
            <div id="dashboard-card-now-tasks">
                <x-dashboard.now-tasks-card />
            </div>

            <!-- Next: 次に控えるタスク (リアルタイム更新対象) -->
            <div id="dashboard-card-next-tasks">
                <x-dashboard.next-tasks-card />
            </div>

        </div>

        <!-- 右側1カラム: 会議日程 & アラート監視 -->
        <div class="space-y-6">

            <!-- 直近の会議・打合せ (最大3件) -->
            <x-dashboard.upcoming-meetings-card :limit="3" />

            <!-- 1. 駐車場利用時間制約監視 (F-405) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                        <span>🅿️</span>
                        <span>駐車場利用時間制約監視</span>
                    </h3>
                    <span class="text-[10px] text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded font-bold">違反なし</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">北川駐車場</div>
                            <div class="text-[10px] text-slate-500 font-mono">Day 0 13:00 〜 Day 3 12:00</div>
                        </div>
                        <span class="text-[11px] font-bold text-emerald-700">利用中 (適正)</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800">駅前南駐車場</div>
                            <div class="text-[10px] text-slate-500 font-mono">Day 1 09:00 〜 Day 1 18:00</div>
                        </div>
                        <span class="text-[11px] font-bold text-amber-700">本日18:00返却必須</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
</x-layouts.app>
