@props([
    'options' => [15, 30, 50, 100],
    'default' => 15,
    'id' => 'per_page',
])

@php
    $current = (int) request('per_page', $default);
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center space-x-1.5 text-xs shrink-0']) }}>
    <label for="{{ $id }}" class="text-[11px] font-bold text-slate-600 whitespace-nowrap">表示:</label>
    <select id="{{ $id }}"
        name="per_page"
        data-auto-submit
        class="px-2 py-1 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        @foreach($options as $opt)
            <option value="{{ $opt }}" {{ $current === (int) $opt ? 'selected' : '' }}>{{ $opt }}件</option>
        @endforeach
    </select>
</div>
