<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5">
    <!-- 1. タスク進捗 KPI -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 flex flex-col justify-between">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 flex items-center space-x-1">
                <span>⏱️</span>
                <span>タスク進捗</span>
            </span>
            <span class="font-mono font-bold text-xs {{ $overview->taskCompletionRate === 100 && $overview->totalTasks > 0 ? 'text-emerald-600' : 'text-indigo-600' }}">
                {{ $overview->completedTasks }}/{{ $overview->totalTasks }} <span class="text-[10px]">完了</span> ({{ $overview->taskCompletionRate }}%)
            </span>
        </div>
        <!-- プログレスバー (Strict CSP: data-percent 経由で JS 描画) -->
        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden my-1.5">
            <div class="progress-bar-fill bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
                 data-percent="{{ $overview->taskCompletionRate }}"></div>
        </div>
        <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
            <span>進行中: <strong class="text-indigo-700">{{ $overview->inProgressTasks }}</strong></span>
            <span>未着手: <strong class="text-slate-700">{{ $overview->notStartedTasks }}</strong></span>
            @if($overview->criticalTasksCount > 0)
                <span class="text-rose-600 font-bold text-[10px] bg-rose-50 px-1 rounded">最優先: {{ $overview->criticalTasksCount }}</span>
            @endif
        </div>
    </div>

    <!-- 2. 人員・充足 KPI -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 flex flex-col justify-between">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 flex items-center space-x-1">
                <span>👥</span>
                <span>人員充足</span>
            </span>
            <span class="font-mono font-bold text-xs {{ $overview->shortageTasks === 0 && $overview->totalTasks > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                {{ $overview->satisfiedTasks }}/{{ $overview->totalTasks }} <span class="text-[10px]">充足</span>
            </span>
        </div>
        <!-- プログレスバー -->
        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden my-1.5">
            <div class="progress-bar-fill {{ $overview->shortageTasks === 0 ? 'bg-emerald-500' : 'bg-amber-500' }} h-1.5 rounded-full transition-all duration-300"
                 data-percent="{{ $overview->fulfillmentRate }}"></div>
        </div>
        <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
            <span>必要: <strong class="text-slate-800">{{ $overview->totalRequiredPeople }}人</strong></span>
            <span>配置: <strong class="{{ $overview->totalAssignedPeople >= $overview->totalRequiredPeople ? 'text-emerald-700' : 'text-amber-700' }}">{{ $overview->totalAssignedPeople }}人</strong></span>
            @if($overview->shortageTasks > 0)
                <span class="text-rose-600 font-bold text-[10px] bg-rose-50 px-1 rounded">不足: {{ $overview->shortageTasks }}</span>
            @endif
        </div>
    </div>

    <!-- 3. 資材・物流配備 KPI -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 flex flex-col justify-between">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 flex items-center space-x-1">
                <span>🚚</span>
                <span>資材物流配備</span>
            </span>
            <span class="font-mono font-bold text-xs text-sky-700">
                {{ $overview->completedLogisticsPlans }}/{{ $overview->totalLogisticsPlans }} <span class="text-[10px]">完了</span> ({{ $overview->logisticsProgressPercent }}%)
            </span>
        </div>
        <!-- プログレスバー -->
        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden my-1.5">
            <div class="progress-bar-fill bg-sky-500 h-1.5 rounded-full transition-all duration-300"
                 data-percent="{{ $overview->logisticsProgressPercent }}"></div>
        </div>
        <div class="flex items-center justify-between text-[11px] text-slate-500 font-mono">
            <span>配備資材: <strong class="text-slate-800">{{ $overview->uniqueEquipmentCount }}種</strong></span>
            @if($overview->inTransitLogisticsPlans > 0)
                <span>移動中: <strong class="text-sky-600">{{ $overview->inTransitLogisticsPlans }}</strong></span>
            @endif
            @if($overview->witnessRequiredPlans > 0)
                <span class="text-rose-600 font-bold text-[10px] bg-rose-50 px-1 rounded">立会必須: {{ $overview->witnessRequiredPlans }}</span>
            @endif
        </div>
    </div>

    <!-- 4. 道路規制 ＆ 現場要請 KPI -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 flex flex-col justify-between">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700 flex items-center space-x-1">
                <span>🚧</span>
                <span>規制 ＆ 現場要請</span>
            </span>
            @if($overview->hasActiveRoadClosure)
                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800 animate-pulse">
                    車両通行止め中
                </span>
            @elseif($overview->roadPeriodsCount > 0)
                <span class="text-[10px] font-mono font-bold text-rose-700 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">
                    {{ $overview->roadPeriodsCount }}件規制予定
                </span>
            @else
                <span class="text-[10px] font-mono text-slate-500 bg-slate-50 px-1.5 py-0.2 rounded">
                    規制なし
                </span>
            @endif
        </div>
        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden my-1.5">
            <div class="progress-bar-fill {{ $overview->pendingLaneRequestsCount > 0 ? 'bg-amber-500' : 'bg-emerald-500' }} h-1.5 rounded-full transition-all duration-300"
                 data-percent="{{ $overview->pendingLaneRequestsCount > 0 ? 100 : 0 }}"></div>
        </div>
        <div class="flex items-center justify-between text-[11px] text-slate-600 font-mono">
            <span>現場要請: <strong class="{{ $overview->pendingLaneRequestsCount > 0 ? 'text-amber-600 font-bold' : 'text-slate-500' }}">{{ $overview->pendingLaneRequestsCount }}件未対応</strong></span>
            <span>まつり進行: <strong class="text-emerald-700">{{ $overview->festivalPeriodsCount }}枠</strong></span>
        </div>
    </div>
</div>

