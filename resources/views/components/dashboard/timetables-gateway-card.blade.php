<div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
            <span>⏱️</span>
            <span>まつり運行日程ゲートウェイ</span>
            <span class="text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 px-1.5 py-0.2 rounded border border-indigo-200/60">
                {{ $timetables->count() }}日程
            </span>
        </h3>
        <a href="{{ route('timetables.index') }}"
            class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center space-x-0.5">
            <span>全日程一覧</span>
            <span>→</span>
        </a>
    </div>

    @if($timetables->isNotEmpty())
    <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
        @foreach($timetables as $timetable)
        <div class="p-3 rounded-xl border transition space-y-2 {{ $timetable->is_active ? 'bg-indigo-50/40 border-indigo-300 ring-1 ring-indigo-200' : 'bg-slate-50/70 border-slate-200 hover:border-slate-300' }}">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <div class="text-center w-11 py-1 rounded-lg border shadow-2xs {{ $timetable->is_active ? 'bg-indigo-600 text-white border-indigo-700' : 'bg-white text-slate-900 border-slate-200' }}">
                        <span class="block text-xs font-black leading-none">
                            {{ $timetable->target_date?->format('n/j') ?? '未定' }}
                        </span>
                        <span class="block text-[10px] font-bold leading-tight mt-0.5 {{ $timetable->is_active ? 'text-indigo-200' : ($timetable->target_date?->isWeekend() ? 'text-rose-600' : 'text-slate-500') }}">
                            ({{ $timetable->target_date?->isoFormat('ddd') ?? '-' }})
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center space-x-1.5 flex-wrap">
                            <h4 class="text-xs font-bold text-slate-900">
                                {{ $timetable->name }}
                            </h4>
                            @if($timetable->is_today)
                            <span class="px-1.5 py-0.2 bg-rose-500 text-white text-[9px] font-bold rounded-full animate-pulse">
                                本日
                            </span>
                            @endif
                        </div>
                        <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                            🕒 {{ \Carbon\Carbon::parse($timetable->start_time)->format('H:i') }} 〜 {{ \Carbon\Carbon::parse($timetable->end_time)->format('H:i') }}
                        </div>
                    </div>
                </div>

                <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded {{ $timetable->is_active ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300' : ($timetable->is_completed ? 'bg-slate-200 text-slate-700' : 'bg-sky-100 text-sky-800') }}">
                    {{ $timetable->is_active ? '● 運行中' : ($timetable->is_completed ? '完了' : '計画中') }}
                </span>
            </div>

            @if($timetable->description)
            <p class="text-[11px] text-slate-600 line-clamp-1">
                {{ $timetable->description }}
            </p>
            @endif

            <!-- アクション動線 -->
            <div class="flex items-center gap-1.5 pt-1.5 border-t border-slate-200/60">
                <a href="{{ route('timetables.show', $timetable) }}"
                    class="flex-1 py-1.5 px-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center justify-center space-x-1 shadow-2xs">
                    <span>🎛️</span>
                    <span>運行詳細コックピットを開く</span>
                    <span>→</span>
                </a>
                @if(! $timetable->is_completed)
                <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
                    title="進行表マトリクス"
                    class="py-1.5 px-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition flex items-center space-x-1 shrink-0 shadow-2xs">
                    <span>📋</span>
                    <span class="hidden sm:inline">進行表</span>
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-6 text-xs text-slate-400 bg-slate-50/60 rounded-lg border border-dashed border-slate-200">
        登録されている運行計画はありません
    </div>
    @endif
</div>