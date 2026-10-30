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

            <!-- 班長からの人員応援要請・必要機材申請カード -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-1.5">
                        <span>👑</span>
                        <span>班長からの人員要請・機材申請</span>
                    </h3>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ isset($laneRequests) && $laneRequests->where('status', 'pending')->isNotEmpty() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                        未対応 {{ isset($laneRequests) ? $laneRequests->where('status', 'pending')->count() : 0 }} 件
                    </span>
                </div>

                @if(isset($laneRequests) && $laneRequests->isNotEmpty())
                <div class="space-y-2 text-xs">
                    @foreach($laneRequests as $laneRequest)
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-slate-900">
                                [{{ $laneRequest->lane?->name ?? '班' }}] {{ $laneRequest->type_label }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $laneRequest->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                                {{ $laneRequest->status_label }}
                            </span>
                        </div>
                        <div class="text-slate-700">
                            @if($laneRequest->request_type === 'equipment')
                                機材: <strong>{{ $laneRequest->equipment_name }}</strong> × {{ $laneRequest->requested_count }}
                            @else
                                応援要請: <strong>+{{ $laneRequest->requested_count }}名</strong>
                                @if($laneRequest->task)
                                    （{{ $laneRequest->task->title }}）
                                @endif
                            @endif
                        </div>
                        @if($laneRequest->reason)
                        <div class="text-[11px] text-slate-500">{{ $laneRequest->reason }}</div>
                        @endif
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[10px] text-slate-400">
                                班長: {{ $laneRequest->requestedByStaff?->name ?? '-' }}
                            </span>
                            <div class="flex items-center gap-1">
                                @if($laneRequest->status === 'pending')
                                <form action="{{ route('lane-requests.update-status', $laneRequest) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-bold cursor-pointer">
                                        承認・手配
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('lane-requests.update-status', $laneRequest) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold cursor-pointer">
                                        完了
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-xs text-slate-400">現在、未対応の人員要請・機材申請はありません。</p>
                @endif
            </div>

            <!-- 1. 駐車場利用時間制約監視 (F-405) (リアルタイム更新対象) -->
            <div id="dashboard-card-parking-monitoring">
                <x-dashboard.parking-monitoring-card :context="$parkingMonitoringContext ?? null" />
            </div>

        </div>

    </div>

</div>
</x-layouts.app>
