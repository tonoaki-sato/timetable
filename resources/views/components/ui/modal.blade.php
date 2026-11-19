@props([
    'id',
    'title' => null,
    'icon' => null,
    'maxWidth' => 'lg',
    'closeTarget' => null,
])

@php
    $maxWidths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];
    $widthClass = $maxWidths[$maxWidth] ?? $maxWidths['lg'];
    $closeSelector = $closeTarget ?? ('#' . $id);
@endphp

<div id="{{ $id }}"
    class="hidden fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    @if($title) aria-labelledby="{{ $id }}-title" @endif>
    <div data-close-target="{{ $closeSelector }}"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity cursor-pointer"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div {{ $attributes->merge(['class' => "relative w-full {$widthClass} transform overflow-hidden rounded-2xl bg-white text-left shadow-xl border border-slate-200 transition-all z-10"]) }}>
            @if($title)
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 id="{{ $id }}-title" class="text-sm font-bold text-slate-800 flex items-center space-x-1.5">
                        @if($icon)
                            <span>{{ $icon }}</span>
                        @endif
                        <span>{{ $title }}</span>
                    </h3>
                    <button type="button"
                        data-close-target="{{ $closeSelector }}"
                        aria-label="閉じる"
                        class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">✕</button>
                </div>
            @endif

            <div class="p-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
