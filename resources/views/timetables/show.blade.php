<x-layouts.app :title="$timetable->name . ' - 運行基本情報'">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-3">

    <!-- 1. ナビゲーション & ヘッダー (コンパクト1行化) -->
    <x-page-header
        :title="$timetable->name"
        icon="⏱️"
        :description="$timetable->description ?: '「' . $timetable->name . '」（' . $timetable->target_date?->format('Y年m月d日') . '）の運行基本情報・規制時間帯および担当班サマリーです。'">
        <x-slot:breadcrumbs>
            <a href="{{ route('timetables.index') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>⏱️</span>
                <span>運行情報一覧</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate">📅 {{ $timetable->name }}</span>
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex items-center space-x-2 shrink-0">
                @can('update', $timetable)
                <a href="{{ route('timetables.edit', $timetable) }}"
                    class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0">
                    <span>✏️</span>
                    <span>基本情報を編集</span>
                </a>
                @endcan

                <a href="{{ route('timetables.index') }}"
                    class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shadow-2xs whitespace-nowrap shrink-0">
                    <span>←</span>
                    <span>運行情報一覧に戻る</span>
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- 2. 運行日コンテキストバー (日程切替・サブナビ) -->
    <x-timetable-context-bar :timetable="$timetable" active="show" />

    <!-- 3. 当運行計画 4大統合オペレーションKPIストリップ (薄型) -->
    <x-timetable.show-kpi-strip :overview="$overview" />

    <!-- 4. 高密度 3カラム コックピット (カード内独立スクロールでゼロスクロール最適化) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-start">

        <!-- 左カラム (lg:col-span-4): 担当班編成 ＆ タスク進捗 -->
        <div class="lg:col-span-4 space-y-3">
            <!-- 担当班（10レーン）編成状況 -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                        <span>🏷️</span>
                        <span>担当班（10レーン）編成状況</span>
                    </h3>
                    <a href="{{ route('timetables.lanes.index', $timetable) }}"
                        class="px-2 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition flex items-center space-x-0.5">
                        <span>編成を開く</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-1.5 max-h-48 overflow-y-auto pr-0.5">
                    @forelse($timetable->lanes as $lane)
                    <div class="p-2 rounded-lg border border-slate-100 bg-slate-50/70 space-y-0.5">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center text-xs font-bold text-slate-800 truncate">
                                <span class="mr-1 shrink-0">{{ $lane->icon ?: '📋' }}</span>
                                <span class="truncate">{{ $lane->name }}</span>
                            </span>
                            <span class="text-[9px] font-mono text-slate-500 shrink-0">
                                {{ $lane->fixedStaff->count() }}名
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-600 truncate flex items-center space-x-1">
                            <span class="text-slate-400">班長:</span>
                            <strong class="font-medium truncate {{ $lane->leadStaff ? 'text-indigo-700' : 'text-slate-400' }}">
                                {{ $lane->leadStaff ? $lane->leadStaff->name : '未設定' }}
                            </strong>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-2 py-4 text-center text-slate-400 text-xs">
                        担当班が登録されていません。
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- 作業タスク・人員充足サマリー -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                        <span>📋</span>
                        <span>作業タスク・人員充足サマリー</span>
                    </h3>
                    <a href="{{ route('timetables.tasks.index', $timetable) }}"
                        class="px-2 py-0.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-bold transition flex items-center space-x-0.5 shadow-2xs">
                        <span>タスク管理へ</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">登録タスク総数</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono text-slate-900">{{ $overview->totalTasks }}</strong>
                            <span class="text-[10px] text-slate-500">件</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">人員充足タスク</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono {{ $overview->shortageTasks === 0 && $overview->totalTasks > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $overview->satisfiedTasks }} / {{ $overview->totalTasks }}
                            </strong>
                            <span class="text-[10px] text-slate-500">充足</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">必要人員数 (延べ)</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono text-slate-800">{{ $overview->totalRequiredPeople }}</strong>
                            <span class="text-[10px] text-slate-500">人</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">アサイン済人員数 (延べ)</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono text-indigo-700">{{ $overview->totalAssignedPeople }}</strong>
                            <span class="text-[10px] text-slate-500">人</span>
                        </div>
                    </div>
                </div>

                @if($overview->shortageTasks > 0)
                <div class="p-2 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                    <span class="font-bold flex items-center space-x-1 text-[11px]">
                        <span>⚠️</span>
                        <span>{{ $overview->shortageTasks }}件のタスクで人員が不足しています</span>
                    </span>
                    <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}" class="text-indigo-700 font-bold underline text-[11px] shrink-0 ml-1">
                        配置 →
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- 中央カラム (lg:col-span-4): 運行基本情報 ＆ 規制状況 ＆ 重点タスク -->
        <div class="lg:col-span-4 space-y-3">
            <!-- 運行基本情報カード -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-base">📅</span>
                        <h2 class="text-xs font-bold text-slate-900">運行基本情報</h2>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $timetable->status_badge_class }}">
                        <span class="w-1.5 h-1.5 rounded-full mr-1 {{ $timetable->status === 'active' ? 'bg-emerald-500 animate-pulse' : ($timetable->status === 'completed' ? 'bg-slate-400' : 'bg-blue-500') }}"></span>
                        {{ $timetable->status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 space-y-0.5">
                        <span class="text-[10px] font-bold text-slate-500 block">開催年月日</span>
                        <div class="flex items-baseline space-x-1 font-mono font-bold text-slate-900 text-xs">
                            <span>{{ $timetable->target_date?->format('Y年m月d日') }}</span>
                            <span class="{{ $overview->isWeekend ? 'text-rose-600' : 'text-slate-600' }}">({{ $overview->weekdayLabel }})</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 space-y-0.5">
                        <span class="text-[10px] font-bold text-slate-500 block">運行時間帯 (予定)</span>
                        <div class="font-mono font-bold text-slate-900 text-xs truncate">
                            {{ $overview->startFormatted }} 〜 {{ $overview->endFormatted }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono">({{ $overview->durationLabel }})</div>
                    </div>
                </div>

                @if($timetable->description)
                <div class="pt-1">
                    <div class="px-2.5 py-1.5 bg-slate-50 border border-slate-200/80 rounded-lg text-[11px] text-slate-700 whitespace-pre-wrap leading-relaxed max-h-16 overflow-y-auto">
                        {{ $timetable->description }}
                    </div>
                </div>
                @endif
            </div>

            <!-- 道路の状況（交通規制・通行止め）＆ まつりの状況（イベント進行状況） -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
                <div class="space-y-2">
                    <!-- 道路の状況 -->
                    <div class="bg-slate-50/70 rounded-lg border border-slate-200/80 p-2.5 space-y-1.5">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-200/50">
                            <h3 class="font-bold text-[11px] text-slate-900 flex items-center space-x-1">
                                <span>🚧</span>
                                <span>道路の状況（交通規制・通行止め）</span>
                            </h3>
                            <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold {{ !empty($timetable->road_periods) ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-500' }}">
                                {{ !empty($timetable->road_periods) ? count($timetable->road_periods) . '件' : '終日規制なし' }}
                            </span>
                        </div>

                        @if(!empty($timetable->road_periods))
                        <div class="space-y-1 max-h-24 overflow-y-auto pr-0.5">
                            @foreach($timetable->road_periods as $rp)
                            <div class="flex items-center justify-between p-1.5 bg-white rounded border border-rose-100 text-xs">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="px-1 py-0.2 rounded font-mono font-bold bg-rose-100 text-rose-800 text-[9px] shrink-0">
                                        {{ substr($rp['start_time'] ?? '', 0, 5) }}〜{{ substr($rp['end_time'] ?? '', 0, 5) }}
                                    </span>
                                    <strong class="text-rose-950 font-bold text-[11px] truncate">{{ $rp['label'] ?? '通行止め' }}</strong>
                                </div>
                                <span class="text-[9px] text-rose-600 font-bold bg-rose-50 px-1 py-0.2 rounded border border-rose-200 shrink-0 ml-1">
                                    {{ ($rp['status'] ?? 'closed') === 'closed' ? '車両通行止め' : $rp['status'] }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="py-1 text-center text-slate-400 text-[11px]">
                            終日、車両通行止め規制はありません（通常交通）。
                        </div>
                        @endif
                    </div>

                    <!-- まつりの状況 -->
                    <div class="bg-slate-50/70 rounded-lg border border-slate-200/80 p-2.5 space-y-1.5">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-200/50">
                            <h3 class="font-bold text-[11px] text-slate-900 flex items-center space-x-1">
                                <span>🏮</span>
                                <span>まつりの状況（イベント進行状況）</span>
                            </h3>
                            <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold {{ !empty($timetable->festival_periods) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                {{ !empty($timetable->festival_periods) ? count($timetable->festival_periods) . '件' : '終日準備・待機' }}
                            </span>
                        </div>

                        @if(!empty($timetable->festival_periods))
                        <div class="space-y-1 max-h-24 overflow-y-auto pr-0.5">
                            @foreach($timetable->festival_periods as $fp)
                            <div class="flex items-center justify-between p-1.5 bg-white rounded border border-emerald-100 text-xs">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="px-1 py-0.2 rounded font-mono font-bold bg-emerald-100 text-emerald-800 text-[9px] shrink-0">
                                        {{ substr($fp['start_time'] ?? '', 0, 5) }}〜{{ substr($fp['end_time'] ?? '', 0, 5) }}
                                    </span>
                                    <strong class="text-emerald-950 font-bold text-[11px] truncate">{{ $fp['label'] ?? '開催中' }}</strong>
                                </div>
                                <span class="text-[9px] text-emerald-700 font-bold bg-emerald-50 px-1 py-0.2 rounded border border-emerald-200 shrink-0 ml-1">
                                    {{ ($fp['status'] ?? 'active') === 'active' ? 'イベント進行中' : ($fp['status'] === 'active' ? '本祭開催中' : $fp['status']) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="py-1 text-center text-slate-400 text-[11px]">
                            終日、まつり本祭運行はありません（準備・片付け日等）。
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 重点管理タスク (クリティカル・最優先) Spotlight -->
            @if($overview->criticalTasksCount > 0)
            <div class="bg-white rounded-xl border border-rose-200/90 shadow-2xs p-3 space-y-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-rose-100">
                    <h3 class="font-bold text-xs text-rose-900 flex items-center space-x-1">
                        <span>🎯</span>
                        <span>重点管理タスク（最優先・通行止め連動）</span>
                    </h3>
                    <span class="text-[10px] font-mono font-bold bg-rose-100 text-rose-800 px-1.5 py-0.2 rounded">
                        {{ $overview->criticalTasksCount }}件
                    </span>
                </div>
                <div class="space-y-1 max-h-28 overflow-y-auto pr-0.5">
                    @foreach($overview->criticalTasks as $critTask)
                    <div class="p-1.5 bg-rose-50/60 rounded border border-rose-100 flex items-center justify-between text-xs">
                        <div class="min-w-0 flex-1 pr-2">
                            <div class="font-bold text-slate-900 text-[11px] truncate">
                                [{{ $critTask->laneRelation?->name ?? '班' }}] {{ $critTask->title }}
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono">
                                {{ substr($critTask->start_time, 0, 5) }}〜{{ substr($critTask->end_time, 0, 5) }} / 必要: {{ $critTask->required_people_count }}名
                            </div>
                        </div>
                        <span class="text-[9px] px-1.5 py-0.2 rounded font-bold shrink-0 {{ $critTask->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($critTask->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-600') }}">
                            {{ $critTask->status_label }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- 右カラム (lg:col-span-4): 物流サマリー ＆ 現場要請 ＆ 削除アクション -->
        <div class="lg:col-span-4 space-y-3">
            <!-- 資材搬出入・物流配備サマリー -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
                            <span>🚚</span>
                            <span>資材搬出入・物流配備サマリー</span>
                        </h3>
                    </div>
                    <a href="{{ route('timetables.logistics.index', $timetable) }}"
                        class="px-2 py-0.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-bold transition flex items-center space-x-0.5 shadow-2xs">
                        <span>搬出入計画へ</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">配備資材数</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono text-slate-900">{{ $overview->uniqueEquipmentCount }}</strong>
                            <span class="text-[10px] text-slate-500">種</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">計画ステップ総数</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono text-slate-900">{{ $overview->totalLogisticsPlans }}</strong>
                            <span class="text-[10px] text-slate-500">件</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">完了ステップ</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono {{ $overview->completedLogisticsPlans === $overview->totalLogisticsPlans && $overview->totalLogisticsPlans > 0 ? 'text-emerald-600' : 'text-indigo-600' }}">
                                {{ $overview->completedLogisticsPlans }} / {{ $overview->totalLogisticsPlans }}
                            </strong>
                            <span class="text-[9px] text-slate-500 font-mono">({{ $overview->logisticsProgressPercent }}%)</span>
                        </div>
                    </div>

                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-500 block">幹事立会必須</span>
                        <div class="flex items-baseline space-x-1">
                            <strong class="text-base font-bold font-mono {{ $overview->witnessRequiredPlans > 0 ? 'text-rose-600' : 'text-slate-700' }}">{{ $overview->witnessRequiredPlans }}</strong>
                            <span class="text-[10px] text-slate-500">件</span>
                        </div>
                    </div>
                </div>

                @if($overview->totalLogisticsPlans === 0)
                <div class="p-2 bg-indigo-50/70 rounded-lg border border-indigo-100 text-[11px] text-indigo-900 flex items-center justify-between">
                    <span>💡 搬出入計画ステップが未作成です。</span>
                    <a href="{{ route('timetables.logistics.index', $timetable) }}" class="text-indigo-700 font-bold underline shrink-0 ml-1">
                        計画作成 →
                    </a>
                </div>
                @endif
            </div>

            <!-- 当運行計画の現場要請 (班長からの応援要請・機材申請) -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                    <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1">
                        <span>👑</span>
                        <span>当計画の現場要請（人員・機材申請）</span>
                    </h3>
                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded {{ $overview->pendingLaneRequestsCount > 0 ? 'bg-rose-100 text-rose-700 font-mono' : 'bg-emerald-50 text-emerald-700' }}">
                        未対応 {{ $overview->pendingLaneRequestsCount }}件
                    </span>
                </div>

                @if(!empty($overview->pendingLaneRequests))
                <div class="space-y-1.5 max-h-36 overflow-y-auto pr-0.5 text-xs">
                    @foreach($overview->pendingLaneRequests as $req)
                    <div class="p-2 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <div class="flex items-center justify-between gap-1">
                            <span class="font-bold text-slate-900 text-[11px] truncate">
                                [{{ $req->lane?->name ?? '班' }}] {{ $req->type_label }}
                            </span>
                            <span class="text-[9px] px-1 py-0.2 rounded font-bold shrink-0 {{ $req->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-sky-100 text-sky-800' }}">
                                {{ $req->status_label }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-700">
                            @if($req->request_type === 'equipment')
                                機材: <strong>{{ $req->equipment_name }}</strong> × {{ $req->requested_count }}
                            @else
                                応援要請: <strong>+{{ $req->requested_count }}名</strong>
                                @if($req->task)
                                    （{{ $req->task->title }}）
                                @endif
                            @endif
                        </div>
                        @if($req->reason)
                        <div class="text-[10px] text-slate-500">{{ $req->reason }}</div>
                        @endif
                        <div class="flex items-center justify-between pt-0.5">
                            <span class="text-[9px] text-slate-400">
                                申請者: {{ $req->requestedByStaff?->name ?? '-' }}
                            </span>
                            <div class="flex items-center gap-1">
                                @if($req->status === 'pending')
                                <form action="{{ route('lane-requests.update-status', $req) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="px-1.5 py-0.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[9px] font-bold cursor-pointer">
                                        手配
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('lane-requests.update-status', $req) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="resolved">
                                    <button type="submit" class="px-1.5 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[9px] font-bold cursor-pointer">
                                        完了
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-[11px] text-slate-400 py-1 text-center">現在、未対応の人員要請・機材申請はありません。</p>
                @endif
            </div>

            <!-- タイムテーブル削除（管理者操作） -->
            @can('delete', $timetable)
            <div class="pt-2 border-t border-slate-200/80 flex items-center justify-end">
                <form action="{{ route('timetables.destroy', $timetable) }}" method="POST"
                    data-confirm="タイムテーブル「{{ $timetable->name }}」を削除しますか？&#10;（関連するすべての作業タスク、アサイン、レーンも削除されます）">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-2.5 py-1 bg-white hover:bg-rose-50 border border-rose-200 text-rose-600 rounded-lg text-xs font-bold transition cursor-pointer flex items-center space-x-1">
                        <span>🗑️</span>
                        <span>このタイムテーブルを削除する</span>
                    </button>
                </form>
            </div>
            @endcan
        </div>

    </div>

</div>
</x-layouts.app>
