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
            @forelse($timetable->timetableLanes as $lane)
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
