@props([
    'title',
    'icon' => null,
    'description' => null,
    'variant' => 'default',
])

@if($variant === 'dark')
    <div {{ $attributes->merge(['class' => 'bg-gradient-to-r from-slate-900 to-indigo-950 rounded-xl px-4 sm:px-5 py-3 text-white shadow-md border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3']) }}>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 min-w-0">
            @if($icon)
                <span class="text-xl sm:text-2xl shrink-0">{{ $icon }}</span>
            @endif
            <h1 class="text-lg sm:text-xl font-bold tracking-tight truncate flex items-center space-x-2">
                <span>{{ $title }}</span>
            </h1>
            {{ $badges ?? '' }}
            @if($description)
                <p class="text-xs text-slate-300 mt-0.5 w-full">{{ $description }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="flex items-center space-x-2 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
@else
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        @if(isset($breadcrumbs))
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                {{ $breadcrumbs }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 flex items-center space-x-2">
                    @if($icon)
                        <span class="shrink-0">{{ $icon }}</span>
                    @endif
                    <span class="truncate">{{ $title }}</span>
                </h1>
                @if($description)
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        {{ $description }}
                    </p>
                @endif
                @if(isset($badges))
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        {{ $badges }}
                    </div>
                @endif
            </div>

            @if(isset($actions))
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    {{ $actions }}
                </div>
            @endif
        </div>

        {{ $slot ?? '' }}
    </div>
@endif
