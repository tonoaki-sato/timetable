@props([
    'icon' => '📋',
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'px-4 py-10 text-center space-y-2']) }}>
    <div class="text-3xl leading-none">{{ $icon }}</div>
    <p class="text-xs font-bold text-slate-600">{{ $title }}</p>
    @if($description)
        <p class="text-[11px] text-slate-400">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="pt-2 flex items-center justify-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
