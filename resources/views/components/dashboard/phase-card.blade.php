<a href="{{ $actionUrl }}"
   {{ $attributes->merge(['class' => 'bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between hover:border-indigo-300 hover:shadow-sm transition group']) }}>
    <div class="min-w-0 pr-2">
        <div class="flex items-center space-x-1.5">
            <p class="text-xs font-medium text-slate-500">
                {{ $typeLabel }}
            </p>
            @if ($shouldShowPendingBadge)
                <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono font-medium">
                    残{{ $pendingTaskCount }}件
                </span>
            @endif
        </div>
        <h3 class="text-xl font-bold text-slate-900 mt-1 truncate group-hover:text-indigo-600 transition">
            {{ $displayName }}
        </h3>
        <span class="text-[11px] {{ $subStatusColorClass }} font-medium line-clamp-1 mt-0.5">
            {{ $subStatus }}
        </span>
    </div>
    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl shrink-0 group-hover:scale-105 transition">
        {{ $icon }}
    </div>
</a>