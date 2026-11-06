@props([
    'timetable',
    'active' => 'matrix',
])

@php
    $timetablesList = $navTimetables ?? \App\Models\Timetable::orderBy('target_date')->get();
    $targetRouteForTimetable = function($tt) use ($active) {
        return match($active) {
            'lanes' => route('timetables.lanes.index', $tt),
            'tasks' => route('timetables.tasks.index', $tt),
            'assignments' => route('timetables.assignments', $tt),
            'timeline' => route('timetables.staff-timeline', $tt),
            'show' => route('timetables.show', $tt),
            'print' => route('timetables.print-assignments', $tt),
            default => route('timetable.index', ['timetable_id' => $tt->id]),
        };
    };
@endphp

@if($timetable)
<div class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 overflow-x-auto scrollbar-none">
    <!-- 左側: 運行日切り替えセレクト -->
    <div class="flex items-center space-x-2 shrink-0">
        <span class="text-xs font-bold text-slate-700 whitespace-nowrap flex items-center space-x-1">
            <span>📅</span>
            <span>対象運行日:</span>
        </span>
        <select data-action="navigate-on-change"
            class="bg-slate-50 border border-slate-300 rounded-lg text-xs font-bold py-1 pl-2 pr-7 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer whitespace-nowrap">
            @foreach($timetablesList as $tt)
                <option value="{{ $targetRouteForTimetable($tt) }}" {{ $tt->id === $timetable->id ? 'selected' : '' }}>
                    {{ $tt->target_date ? $tt->target_date->format('m/d') . ' ' : '' }}{{ $tt->name }}
                    {{ $tt->status === 'active' ? '(運行中)' : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- 右側: 5大機能タブ (横スクロール可能・各タブ whitespace-nowrap) -->
    <div class="flex items-center space-x-1 shrink-0 overflow-x-auto scrollbar-none flex-nowrap py-0.5">
        <a href="{{ route('timetables.show', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'show' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>ℹ️</span>
            <span>運行情報</span>
        </a>
        <a href="{{ route('timetables.lanes.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'lanes' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>🏷️</span>
            <span>担当班</span>
            @if(isset($timetable->lanes_count) || $timetable->lanes->isNotEmpty())
                <span class="text-[10px] opacity-75 font-mono">({{ $timetable->lanes->count() }})</span>
            @endif
        </a>
        <a href="{{ route('timetables.tasks.index', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'tasks' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>📋</span>
            <span>タスク台帳</span>
            @if(isset($timetable->tasks_count) || $timetable->tasks->isNotEmpty())
                <span class="text-[10px] opacity-75 font-mono">({{ $timetable->tasks->count() }})</span>
            @endif
        </a>
        <a href="{{ route('timetables.assignments', $timetable) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ in_array($active, ['assignments', 'timeline'], true) ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>👥</span>
            <span>人員配置</span>
        </a>
        <a href="{{ route('timetable.index', ['timetable_id' => $timetable->id]) }}"
            class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition flex items-center space-x-1 {{ $active === 'matrix' ? 'bg-indigo-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>⏱️</span>
            <span>進行表</span>
        </a>
        <a href="{{ route('timetables.print-assignments', $timetable) }}" target="_blank"
            class="px-2 py-1 rounded-lg text-xs font-bold whitespace-nowrap transition text-slate-600 hover:text-slate-900 hover:bg-slate-100 flex items-center space-x-1">
            <span>🖨️</span>
            <span>配置表印刷</span>
            <span class="text-[10px] opacity-60">↗</span>
        </a>
    </div>
</div>
@endif

