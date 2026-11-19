@props([
    'column',
    'label',
    'defaultSort' => null,
    'defaultDirection' => 'asc',
    'align' => 'left',
])

@php
    $currentSort = request('sort') ?: $defaultSort;
    $currentDirection = strtolower((string) (request('direction') ?: $defaultDirection)) === 'desc' ? 'desc' : 'asc';
    $isActive = $currentSort === $column;
    $nextDirection = ($isActive && $currentDirection === 'asc') ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDirection,
        'page' => 1,
    ]);
    $alignClass = match ($align) {
        'right' => 'text-right justify-end',
        'center' => 'text-center justify-center',
        default => 'text-left justify-start',
    };
@endphp

<th scope="col"
    aria-sort="{{ $isActive ? ($currentDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    {{ $attributes->merge(['class' => 'whitespace-nowrap select-none']) }}>
    <a href="{{ $url }}"
        class="inline-flex items-center gap-1 {{ $alignClass }} hover:text-indigo-600 transition group">
        <span>{{ $label }}</span>
        <span class="text-[10px] leading-none {{ $isActive ? 'text-indigo-600 font-bold' : 'text-slate-300 group-hover:text-slate-500' }}" aria-hidden="true">
            {{ $isActive ? ($currentDirection === 'asc' ? '▲' : '▼') : '⇅' }}
        </span>
    </a>
</th>
