<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3" id="dashboard-global-kpi-strip">
    <!-- 1. 年間行事・ToDo進捗 -->
    <a href="{{ route('schedules.index') }}" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-2.5 sm:p-3 hover:border-indigo-400 hover:shadow-xs transition group flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-500 flex items-center space-x-1 group-hover:text-indigo-600 transition">
                <span>📅</span>
                <span>年間行事・進捗</span>
            </span>
            <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-indigo-50 text-indigo-700">
                残 {{ $overview->schedulePendingCount }}件
            </span>
        </div>
        <div class="mt-1 flex items-baseline justify-between">
            <div class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                {{ $overview->scheduleCompletionRate }}<span class="text-xs font-bold text-slate-500 ml-0.5">%</span>
            </div>
            <div class="text-[11px] font-mono text-slate-500">
                {{ $overview->scheduleCompletedCount }} / {{ $overview->scheduleTotalCount }}件
            </div>
        </div>
        <div class="mt-1 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
            <div class="progress-bar-fill h-full bg-indigo-600 rounded-full transition-all duration-500"
                 data-percent="{{ $overview->scheduleCompletionRate }}"></div>
        </div>
    </a>

    <!-- 2. 本部スタッフ体制 -->
    <a href="{{ route('staff.index') }}" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-2.5 sm:p-3 hover:border-emerald-400 hover:shadow-xs transition group flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-500 flex items-center space-x-1 group-hover:text-emerald-600 transition">
                <span>👥</span>
                <span>本部スタッフ体制</span>
            </span>
            <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700">
                稼働中
            </span>
        </div>
        <div class="mt-1 flex items-baseline justify-between">
            <div class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                {{ $overview->staffTotalCount }}<span class="text-xs font-bold text-slate-500 ml-0.5">名</span>
            </div>
            <div class="text-[11px] font-mono text-slate-500">
                リーダー・専任: <strong class="text-slate-800">{{ $overview->staffLeaderCount }}名</strong>
            </div>
        </div>
        <div class="mt-1 text-[10px] text-emerald-600 font-bold flex items-center justify-between">
            <span>名簿・連絡網確認</span>
            <span>→</span>
        </div>
    </a>

    <!-- 3. 保有資機材・拠点アセット -->
    <a href="{{ route('equipments.index') }}" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-2.5 sm:p-3 hover:border-amber-400 hover:shadow-xs transition group flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-500 flex items-center space-x-1 group-hover:text-amber-600 transition">
                <span>📦</span>
                <span>資機材・拠点マスタ</span>
            </span>
            <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-amber-50 text-amber-700">
                マスタ
            </span>
        </div>
        <div class="mt-1 flex items-baseline justify-between">
            <div class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                {{ $overview->equipmentTotalCount }}<span class="text-xs font-bold text-slate-500 ml-0.5">品目</span>
            </div>
            <div class="text-[11px] font-mono text-slate-500">
                登録拠点: <strong class="text-slate-800">{{ $overview->locationTotalCount }}箇所</strong>
            </div>
        </div>
        <div class="mt-1 text-[10px] text-amber-600 font-bold flex items-center justify-between">
            <span>機材・倉庫一覧</span>
            <span>→</span>
        </div>
    </a>

    <!-- 4. 運行計画・日程 -->
    <a href="{{ route('timetables.index') }}" class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-2.5 sm:p-3 hover:border-sky-400 hover:shadow-xs transition group flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-slate-500 flex items-center space-x-1 group-hover:text-sky-600 transition">
                <span>⏱️</span>
                <span>運行日程・計画</span>
            </span>
            <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded {{ $overview->activeTimetablesCount > 0 ? 'bg-sky-100 text-sky-800 animate-pulse' : 'bg-slate-100 text-slate-600' }}">
                {{ $overview->activeTimetablesCount > 0 ? '運行中あり' : '準備段階' }}
            </span>
        </div>
        <div class="mt-1 flex items-baseline justify-between">
            <div class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                {{ $overview->timetablesCount }}<span class="text-xs font-bold text-slate-500 ml-0.5">日程</span>
            </div>
            <div class="text-[11px] font-mono text-slate-500 truncate max-w-[120px]">
                {{ $overview->nextEventName ?? '日程未定' }}
            </div>
        </div>
        <div class="mt-1 text-[10px] text-sky-600 font-bold flex items-center justify-between">
            <span>運行計画一覧</span>
            <span>→</span>
        </div>
    </a>
</div>

