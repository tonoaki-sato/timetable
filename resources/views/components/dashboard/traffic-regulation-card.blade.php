<a href="{{ $actionUrl }}"
    {{ $attributes->merge(['class' => 'bg-white p-5 rounded-xl border ' . $context->status->borderClasses() . ' shadow-xs flex items-center justify-between relative overflow-hidden hover:shadow-sm transition group']) }}>
    <div class="absolute top-0 right-0 w-2 h-full {{ $context->status->accentBarClasses() }}"></div>
    <div class="min-w-0 pr-2">
        <p class="text-xs {{ $context->status->badgeClasses() }} flex items-center space-x-1.5">
            @if ($context->isAlertActive)
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
            @endif
            <span>{{ $context->badgeText }}</span>
        </p>
        <h3 class="text-xl font-bold text-slate-900 mt-1 truncate group-hover:text-indigo-600 transition">
            {{ $context->headline }}
        </h3>
        <span class="text-[11px] text-slate-500 font-mono line-clamp-1 mt-0.5">
            {{ $context->subStatus }}
        </span>
    </div>
    <div class="w-12 h-12 {{ $context->isRoadClosed ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }} rounded-xl flex items-center justify-center text-2xl shrink-0 group-hover:scale-105 transition">
        {{ $context->status->icon() }}
    </div>
</a>

