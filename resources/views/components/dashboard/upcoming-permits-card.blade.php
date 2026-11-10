<div class="bg-white rounded-xl border border-slate-200/90 shadow-2xs p-3 space-y-2.5">
    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
        <h3 class="font-bold text-xs text-slate-900 flex items-center space-x-1.5">
            <span>📋</span>
            <span>直近の許認可・申請・説明会</span>
            @if($permits->isNotEmpty())
            <span class="text-[10px] font-mono font-bold bg-amber-50 text-amber-700 px-1.5 py-0.2 rounded border border-amber-200/60">
                {{ $permits->count() }}件
            </span>
            @endif
        </h3>
        <a href="{{ route('schedules.index', ['category' => 'permit']) }}"
           class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center space-x-0.5">
            <span>一覧</span>
            <span>→</span>
        </a>
    </div>

    @if($permits->isNotEmpty())
    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
        @foreach($permits as $permit)
        <div class="p-2.5 bg-slate-50/80 hover:bg-slate-100/80 rounded-lg border border-slate-200/70 transition space-y-1.5">
            <div class="flex items-center justify-between gap-1.5">
                <a href="{{ route('schedules.show', $permit) }}" class="text-xs font-bold text-slate-900 hover:text-indigo-600 transition truncate flex items-center space-x-1">
                    <span>{{ $permit->category_icon }}</span>
                    <span class="truncate">{{ $permit->title }}</span>
                </a>
                <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.2 rounded {{ $permit->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($permit->status === 'in_progress' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700') }}">
                    {{ $permit->status_label }}
                </span>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span class="font-mono">
                    📅 {{ $permit->start_date?->format('n/j') }} ({{ $permit->start_date?->isoFormat('ddd') }})
                    @if($permit->formatted_time_range)
                    {{ $permit->formatted_time_range }}
                    @endif
                </span>
                <span class="truncate max-w-[120px]">
                    👤 {{ $permit->assignee?->name ?? '担当未定' }}
                </span>
            </div>
            @if($permit->location)
            <div class="text-[10px] text-slate-400 truncate">
                📍 {{ $permit->location }}
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-4 text-xs text-slate-400 bg-slate-50/60 rounded-lg border border-dashed border-slate-200">
        直近の許認可・申請・説明会予定はありません
    </div>
    @endif
</div>

