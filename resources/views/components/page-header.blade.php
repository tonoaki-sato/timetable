@props([
    'title',
    'icon' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if(isset($breadcrumbs))
        <div class="flex items-center space-x-1.5 text-[11px] text-slate-500 overflow-hidden text-ellipsis whitespace-nowrap">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition flex items-center space-x-1 shrink-0">
                <span>🏮</span><span>ホーム</span>
            </a>
            <span class="text-slate-300">/</span>
            {{ $breadcrumbs }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200">
        <div class="flex flex-wrap items-center gap-2 min-w-0">
            <h1 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900 flex items-center space-x-2 break-words">
                @if($icon)
                    <span class="text-xl shrink-0">{{ $icon }}</span>
                @endif
                <span>{{ $title }}</span>
            </h1>
            @if(isset($badges))
                <div class="flex flex-wrap items-center gap-1.5">
                    {{ $badges }}
                </div>
            @endif
            @if($description)
                <span class="hidden lg:inline-block text-xs text-slate-500 ml-2 truncate max-w-md" title="{{ $description }}">
                    {{ $description }}
                </span>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-1.5 shrink-0 self-start sm:self-center">
                {{ $actions }}
            </div>
        @endif
    </div>

    {{ $slot ?? '' }}
</div>
