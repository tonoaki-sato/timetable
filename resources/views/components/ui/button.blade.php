@props([
    'variant' => 'header-action',
    'size' => 'sm',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'header-action' => 'bg-white border border-slate-300 text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 shadow-2xs',
        'primary' => 'bg-indigo-600 border border-indigo-600 text-white hover:bg-indigo-700 shadow-xs',
        'secondary' => 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 shadow-2xs',
        'edit' => 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200',
        'delete' => 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200',
    ];
    $sizes = [
        'xs' => 'px-2.5 py-1 text-xs rounded-lg',
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2 text-xs rounded-xl',
    ];
    $classes = 'inline-flex items-center justify-center space-x-1 font-bold transition whitespace-nowrap shrink-0 cursor-pointer '
        . ($variants[$variant] ?? $variants['header-action']) . ' '
        . ($sizes[$size] ?? $sizes['sm']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
