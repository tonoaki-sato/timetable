<!-- 中央カラム (lg:col-span-4): 運行基本情報 ＆ 規制状況 ＆ 重点タスク -->
<div class="lg:col-span-4 space-y-3">
    <!-- 運行基本情報カード -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div class="flex items-center space-x-1.5">
                <span class="text-base">📅</span>
                <h2 class="text-xs font-bold text-slate-900">運行基本情報</h2>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $timetable->presenter()->statusBadgeClass() }}">
                <span class="w-1.5 h-1.5 rounded-full mr-1 {{ $timetable->status === 'active' ? 'bg-emerald-500 animate-pulse' : ($timetable->status === 'completed' ? 'bg-slate-400' : 'bg-blue-500') }}"></span>
                {{ $timetable->presenter()->statusLabel() }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="p-2 bg-slate-50 rounded-lg border border-slate-100 space-y-0.5">
                <span class="text-[10px] font-bold text-slate-500 block">開催年月日</span>
                <div class="flex items-baseline space-x-1 font-mono font-bold text-slate-900 text-xs">
                    <span>{{ $timetable->target_date?->format('Y年m月d日') }}</span>
                    <span class="{{ $overview->isWeekend ? 'text-red-600' : 'text-slate-600' }}">({{ $overview->weekdayLabel }})</span>
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
                    <span class="text-[9px] px-1.5 py-0.2 rounded font-mono font-bold {{ !empty($timetable->road_periods) ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-slate-100 text-slate-500' }}">
                        {{ !empty($timetable->road_periods) ? count($timetable->road_periods) . '件' : '終日規制なし' }}
                    </span>
                </div>

                @if(!empty($timetable->road_periods))
                <div class="space-y-1 max-h-24 overflow-y-auto pr-0.5">
                    @foreach($timetable->road_periods as $rp)
                    <div class="flex items-center justify-between p-1.5 bg-white rounded border border-red-100 text-xs">
                        <div class="flex items-center space-x-1.5 truncate">
                            <span class="px-1 py-0.2 rounded font-mono font-bold bg-red-100 text-red-800 text-[9px] shrink-0">
                                {{ substr($rp['start_time'] ?? '', 0, 5) }}〜{{ substr($rp['end_time'] ?? '', 0, 5) }}
                            </span>
                            <strong class="text-red-950 font-bold text-[11px] truncate">{{ $rp['label'] ?? '通行止め' }}</strong>
                        </div>
                        <span class="text-[9px] text-red-600 font-bold bg-red-50 px-1 py-0.2 rounded border border-red-200 shrink-0 ml-1">
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
    <div class="bg-white rounded-xl border border-red-200/90 shadow-2xs p-3 space-y-2">
        <div class="flex items-center justify-between pb-1.5 border-b border-red-100">
            <h3 class="font-bold text-xs text-red-900 flex items-center space-x-1">
                <span>🎯</span>
                <span>重点管理タスク（最優先・通行止め連動）</span>
            </h3>
            <span class="text-[10px] font-mono font-bold bg-red-100 text-red-800 px-1.5 py-0.2 rounded">
                {{ $overview->criticalTasksCount }}件
            </span>
        </div>
        <div class="space-y-1 max-h-28 overflow-y-auto pr-0.5">
            @foreach($overview->criticalTasks as $critTask)
            <div class="p-1.5 bg-red-50/60 rounded border border-red-100 flex items-center justify-between text-xs">
                <div class="min-w-0 flex-1 pr-2">
                    <div class="font-bold text-slate-900 text-[11px] truncate">
                        [{{ $critTask->timetableLane?->name ?? '班' }}] {{ $critTask->title }}
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono">
                        {{ substr($critTask->start_time, 0, 5) }}〜{{ substr($critTask->end_time, 0, 5) }} / 必要: {{ $critTask->required_people_count }}名
                    </div>
                </div>
                <span class="text-[9px] px-1.5 py-0.2 rounded font-bold shrink-0 {{ $critTask->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($critTask->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-600') }}">
                    {{ $critTask->presenter()->statusLabel() }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
